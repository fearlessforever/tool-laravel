<?php

return [
  // these are configurations for db:export feature

  'db_export' => [
    // Schema , leave it empty if none
    // ex: [ 'public' , 'logs' ]
    'schemas' => [
      'public'
    ],

    // List tables list category name , for custom list export certain table only
    // ex: main_tables  in db:export command will use like this -> php artisan db:export --tables-list-group=main_tables
    'table_list_groups' => [
      'main_tables' => ['users'],
    
    ],
    
    // this feature is to custom the data of current row
    'custom_export' => [
      // table => callBack -> return new formated data
      'sample_table' => function( $currentRow ){
        $currentRow['new_field_id'] = $currentRow['id'];
        $currentRow['updated_at'] = date('Y-m-d H:i:s' , time() );
        unset($currentRow['id']);
        return $currentRow;
      },
    ]
    
  ],

  //==============================================================================================
  // these are configurations for db:db_import feature

  'db_import' => [

    'custom_import' => [
      'twitter.raw_response' => function( $currentRow ){
        $currentRow['new_field_id'] = $currentRow['id'];
        return $currentRow;
      },
    ],
  ],

];