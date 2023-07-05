<?php

namespace Fearless\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DB_Export extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export {table_name?*} {--clear} {--tables-list-name=} {--all-tables} {--ultra-speed} {--force} {--with-time} {--sample-only=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export database data into json , table_name = table in database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      if( $this->option('tables-list-name') ){
        $tableListName = $this->option('tables-list-name');
        $tables = config("fearlessforever.db_export.table_list_category.{$tableListName}" , [] );
        
      }else if( $this->option('all-tables') ){
        $tables = $this->___getAllTables()->toArray();

      }else{
        $tables = $this->argument('table_name');
      }

      if( empty($tables) )
      {
        $this->newLine();
        $this->warn('Nothing to be exported, check your parameter');
        $this->newLine();
        die;
      }

      $folderPath = env('DB_DATABASE') .'-'. date('Y-m-d');
      $folderPath = storage_path("app/db/{$folderPath}");
      if( $this->option('with-time') ){
        $folderPath = env('DB_DATABASE') .'-'. date('Y-m-d_H-i-s') ;
        $folderPath = storage_path("app/db/{$folderPath}");
      }

      if( !File::exists( $folderPath ) ){
        File::makeDirectory( $folderPath  , 0776 , true , true );
      }
      
      $allFiles = File::allFiles($folderPath);

      /**
       * jika sebelum dumping pilih untuk clear semua isi folder
      */
      if( $this->option('clear') || $this->option('with-time') )
      {
        $this->newLine();
        $this->warn('Clearing all file before exporting database into json');
        $this->newLine();

        File::delete($allFiles);
        $allFiles=[];
      }

      $fileExists = [];
      foreach($allFiles as $file){
        $currentFileName = $file->getFilename();
        $currentTable = explode('---', $currentFileName);
        if( !isset($currentTable[1]) )continue;
        
        $fileExists[$currentTable[1]] = $currentTable[0];
        
      }
      $totalFilesCurrentFolder = count($allFiles);
      
      $bar = $this->output->createProgressBar(count($tables));

      $tableDone =[];
      $tableRows= array_map( fn($val) => ['table_name' => $val] , $tables );
      $this->info( "Start exporting tablesinto json file..." );
      $this->table(['Table Name'],$tableRows);

      $takeSampleDataOnly = (int) $this->option('sample-only');

      foreach($tables as $table)
      {
        $bar->advance();

        if(isset($tableDone[$table]))
        {
          continue;
        }
        $tableDone[$table] = true;

        $data = DB::table( $table )->get();
        $file_name = "{$table}.json";

        if( isset($fileExists[$file_name]) ){
          $file_number = $fileExists[$file_name];
        }else{
          $totalFilesCurrentFolder +=1;
          $file_number = str_pad( "{$totalFilesCurrentFolder}" , 4 , '0' , STR_PAD_LEFT);
        }

        $countRowExported = 0;
        
        foreach($data as $row){
          file_put_contents( "{$folderPath}/{$file_number}---{$file_name}" , json_encode($row) . PHP_EOL , FILE_APPEND );

          if( $takeSampleDataOnly > 0 && $countRowExported > $takeSampleDataOnly )break;
          $countRowExported += 1;
        }

        if( !$this->option('ultra-speed') )
          usleep(300000);

      }

      $bar->finish();

      $this->newLine(2);
      $this->info('Save into : '. $folderPath );
      $this->info('Done '. memory_get_usage() );
      $this->newLine();

      return 0;

    }

    private function ___getAllTables()
    {
      $schemas = config('fearlessforever.db_export.schemas' , ['public'] );
      $schemasBinds = [];
        foreach($schemas as $bindParam)$schemasBinds[]='?';
      $schemasBinds = implode(',' , $schemasBinds );
      
      $queryGetTables = 
      <<<START
        with recursive fk_tree as (
          -- All tables not referencing anything else
          select t.oid as reloid, 
                 t.relname as table_name, 
                 s.nspname as schema_name,
                 null::text  collate "C" as referenced_table_name,
                 null::text collate "C" as referenced_schema_name ,
                 1 as level
          from pg_class t
            join pg_namespace s on s.oid = t.relnamespace
          where relkind = 'r'
            and not exists (select *
                            from pg_constraint
                            where contype = 'f'
                              and conrelid = t.oid)
            and s.nspname IN ( {$schemasBinds} )
        
          union all 
        
          select ref.oid, 
                 ref.relname, 
                 rs.nspname,
                 p.table_name,
                 p.schema_name,
                 p.level + 1
          from pg_class ref
            join pg_namespace rs on rs.oid = ref.relnamespace
            join pg_constraint c on c.contype = 'f' and c.conrelid = ref.oid
            join fk_tree p on p.reloid = c.confrelid
          where ref.oid != p.reloid  -- do not enter to tables referencing theirselves.
        ), all_tables as (
          -- this picks the highest level for each table
          select schema_name, table_name,
                 level, 
                 row_number() over (partition by schema_name, table_name order by level desc) as last_table_row
          from fk_tree
        )
        select schema_name, table_name, level
        from all_tables at
        where last_table_row = 1
        order by level;
        
        START;

      $check = DB::select( $queryGetTables , $schemas );
            // file_put_contents('test.json' , json_encode($check, JSON_PRETTY_PRINT));
      $tables = collect($check);
      $data = $tables->map( fn($val) => (array)$val );
      $tableName = $tables->map( fn($val) => $val->schema_name == 'public' ? $val->table_name : "{$val->schema_name}.{$val->table_name}" );

      $this->table( array_keys( (array)$check[0] ) , $data );

      if( $this->option('force') ){
        return $tableName; 
      }

      if( $this->confirm('Do you want to continue ?') )
      {
        return $tableName;
      }else{
        die;
      }

      return $tableName;
    }
}
