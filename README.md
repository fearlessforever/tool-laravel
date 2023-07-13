<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Tools
  - `export` database data to json
  - `import` json file data into database


## What is it for ?
Well, you can export and import data programmatically . and you can export and import the data into another database type

## Configs File
  - `app/config/fearlessforever.php`


### **Get started**
  - `git clone https://github.com/fearlessforever/tool-laravel.git`
  - `cd tool-laravel`
  - `composer install`
  - **Congratulation** you are good to go. now you can export / import db to json


## How To
### `export` : **php artisan db:export {table_name?*} [--all-tables] [--tables-list-group=] [--clear] [--with-time] [--sample-only=]**
  - ex: `php artisan db:export table1 table2 table3`

  - --all-tables  
    
    this option will try to get all available tables in database and ignore `table_name*`
    note: default only tables in schema `public` for postgresql , `dbo` for mssql , if you want to include others schemas. please set the list in configs file
    `["db_export"]["schemas"]` example:
    ```php
    'schemas' => [
      'public' , 'mapping' , 'filelist' , 'twitter' , 'facebook' , 'tiktok'
    ]
    ```

  - --clear
    
    this option will delete existing backup with the same folder / file name , this is important if you want to make sure the table exported in order of their foreign key

  - --tables-list-group
    this option will work if you have set the list of tables in the group in configs file , and ***ONLY*** export tables in this group
    `["db_export"]["schemas"]["table_list_groups"]` example: `php artisan db:export --tables-list-group=my_app_main_tables` 
    ```php
    'table_list_groups' => [
      'my_app_main_tables' => ["users","users_data","users_product"]
    ]
    ```

  - --sample-only=
    
    if you want to take only a few numbers of rows each exported tables . example : `php artisan db:export --all-tables --sample-only=5`

  - --with-time

    if you want to export data to the folder with date & time format . example if you want to export data at the same date but different time

  - --use-custom-export

    if you want to modify the data of current data while exporting.
    You need to set the callback in **configs file** -> `["db_export"]["schemas"]["custom_export"]`

    example below is to modify the data of sample table

    ```php
    'sample' => function( $currentRow ){
        $currentRow['new_field_id'] = $currentRow['id'];
        $currentRow['updated_at'] = date('Y-m-d H:i:s' , time() );
        unset($currentRow['id']);
        return $currentRow;
      },
    ```

## How To
### `import` : **php artisan db:import {table_name?*} [--all-tables] [--exclude=] [--ls-path] [--path=]**
  - ex: `php artisan db:import table1 table2 table3`


  - --ls-path

    this will print out all available data ( exported data )

  - --path=

    this is required , you can get the path list by `--ls-path` option

    example: `php artisan db:import --all-tables --path=my_database-2023-07-13`

  - --all-tables

    this option will try to import all available json files into database

  - --exclude=

    this option will exclude importing json data into database , any tables matches in this list . table list name separate by comma
    
    example: `php artisan db:import --all-tables --path=db_sample --exclude=migrations,log_errors`