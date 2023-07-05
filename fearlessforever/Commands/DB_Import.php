<?php

namespace Fearless\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DB_Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import {table_name?*} {--all} {--exclude=} {--path=} {--ls-path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import json file ( export from command: db:export ) into database , table_name = table in database';

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
      if( $this->option('ls-path') )
      {
        $this->__lsPath();
        die;
      }

      $folderPath = storage_path('app/db');
      $certainPath = $this->option('path');
      $isCertainPath = false;
      if( !empty($certainPath) ){
        $folderPath = storage_path("app/db/{$certainPath}");
        $isCertainPath = true;
      }
      
      if( !File::exists( $folderPath ) ){
        $this->warn('Folder path is not found');
        return;
      }
      $tablesExclude=[];
      $fileExists = $this->__getFilesName( $folderPath ) ;

      if($this->option('all')){
        $tablesExclude = $this->__getExcludeTables();

        $tables = array_keys( $fileExists );
      }else{
        $tables = $this->argument('table_name');
      }

      if( empty($tables) )
      {
        $this->newLine();
        $this->warn('Nothing to be imported, check your parameter');
        $this->newLine();
        die;
      }

      else

      {
        $tableDone =[];
        foreach($tables as $table)
        {
          if(isset($tableDone[$table]) || !isset($fileExists[$table]) )
          {
            $this->newLine();
            $this->info("Skipping table name:{$table} ....");
            $this->info("Exported file maybe not found or duplicate table name ....");
            $this->newLine();
            continue;
          }
          $tableDone[$table] = true;

          if( in_array( $table , $tablesExclude ) )
          {
            $this->newLine();
            $this->info("Skipping excluded table name:{$table} ....");
            $this->newLine();
            continue;
          }

          if( !File::exists("{$folderPath}/{$fileExists[$table]}") )
          {
            $this->warn("File not found:{$fileExists[$table]} ");
            continue;
          }

          $config = (object)[];
          $config->ms_sql_exclude_tables_identity = config("fearlessforever.db_import.exclude_identity_set_on" , [] );
          $config->ms_sql_isIdentityOn = false;
          
          $importedCount=0;
          $handleFile = fopen( "{$folderPath}/{$fileExists[$table]}" , "r");
          if ($handleFile) {
            $this->info(PHP_EOL ."Start importing into table {$table} ..." . PHP_EOL);

            $bar = $this->output->createProgressBar(100);

            $bar->start();
            try{
              DB::beginTransaction();

              if( !in_array( $table , $config->ms_sql_exclude_tables_identity ) ){
                DB::unprepared("SET IDENTITY_INSERT {$table} ON");
                $config->ms_sql_isIdentityOn = true ;
              }

              $query = DB::table( $table );
              while (!feof($handleFile)) {
                  $line = fgets($handleFile);

                  $record = json_decode($line , TRUE);
                  if( $record ){
                    $query->insert($record);
                  }
                  $importedCount+=1;
                  if( $importedCount > 100 ){
                    $bar->setMaxSteps($importedCount + 1);
                  }
                  $bar->advance();
                  // usleep(50000);
              }
              fclose($handleFile);
              
              if($config->ms_sql_isIdentityOn){
                DB::unprepared("SET IDENTITY_INSERT {$table} OFF");
                $config->ms_sql_isIdentityOn = false ;
              }
              
              DB::commit();
            }catch(\Exception $e){
              DB::rollBack();

              $this->error( $e->getMessage() );
              $this->info(PHP_EOL .'Fatal error , stopping command execution' . PHP_EOL);
              die;
            }

            $bar->finish();

          }

          $this->info( PHP_EOL ."Imported data: {$importedCount} record into {$table}" . PHP_EOL);
        }

        if( env( 'DB_DATABASE' , '' ) == 'pgsql' )
        {
          DB::unprepared(
            <<<HEREDOC
              SELECT substring(column_default, '''(.*)''') , column_name , table_schema || '.' || table_name 
              ,reset_sequence(table_schema || '.' || table_name , column_name, substring(column_default, '''(.*)''') ) 
              FROM information_schema.columns where column_default like 'nextval%'
            HEREDOC
          );
        }

        $this->info("Memory Usage: ". memory_get_usage());
      }

      return 0;
    }

    private function __getExcludeTables():array
    {
      $exclude=[];
      $_exclude = $this->option('exclude');
      if(!empty($_exclude)){
        $_exclude = explode(',',$_exclude);

        $exclude = $_exclude;
      }

      return $exclude;
    }

    private function __getFilesName( string $folderPath ):array
    {
      $allFiles = File::allFiles($folderPath);
          
      $fileExists = [];
      foreach($allFiles as $file){
        $currentFileName = $file->getFilename();
        $currentTable = explode('---', $currentFileName);
        if( !isset($currentTable[1]) )continue;
        $currentTable = explode('.json', $currentTable[1] );
        
        $fileExists[$currentTable[0]] = $currentFileName;
        
      }

      return $fileExists;
    }

    private function __lsPath()
    {
      $folderPath = storage_path('app/db');
      if( !File::exists( $folderPath ) ){
        $this->warn('Folder path is not found');
        return;
      }

      $folders = File::directories( $folderPath );
      $pathList = [];
      foreach( $folders as $folder ){
        $folder = explode( 'app/db/', $folder);
        if( isset($folder[1]) )
          $pathList[]= [
            'Path' => $folder[1] ,
          ];
      }

      $this->table( array_keys($pathList[0]) , $pathList );
    }

}
