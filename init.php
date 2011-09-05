<?php defined('SYSPATH') or die('No direct script access.');


Route::set('solrcli', 'solrcli/<model>(/<method>)')
	->defaults(array(
		'controller' => 'solrcli',
		'action' => 'index',
        'method' => NULL,
	));
