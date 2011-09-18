<?php defined('SYSPATH') or die('No direct script access.');

return array
(
    /*
     * Class used for the conversion of document ids into a database object of models
     */
    'driver' => 'Solr_Driver_Sprig',
    /*
     * This is where files will be created for each index/core
     */
    'solr_home' => '/usr/share/solr/',

    /*
     * Need to make sure folders we create are writable by what program controls the data files.
     * In my case its jetty.
     */
    'solr_user' => 'jetty',

    'client_options' => array
    (
        'hostname'  => 'localhost',
        'port'      => '8080',
        'path'      => 'solr'
    ),
);
