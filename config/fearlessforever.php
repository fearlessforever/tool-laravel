<?php

return [
  // these are configurations for db:export fitur

  'db_export' => [
    // Schema , leave it empty if none
    // ex: [ 'public' , 'logs' ]
    'schemas' => [
      'public' , 'mapping' , 'filelist' , 'twitter'
    ],

    // List tables list category name , for custom list export certain table only
    // ex: main_tables  in db:export command will use like this -> php artisan db:export --tables-list-name=main_tables
    'table_list_category' => [
      'main_tables' => ['users'],
    ]
    
  ],

  'db_import' => [
    'exclude_identity_set_on' => [
      'autocode' , 'mapping.data_path_name' , 'twitter.mapping_source_file'
    ],
  ],

];