<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'current' => [
            'driver' => 'local',
            'root' => env('CURRENT_PATH', getcwd()),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'template' => [
            'driver' => 'local',
            'root' => base_path('template'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

    ],

];
