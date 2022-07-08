<?php

/**
 * @package Topic Solved
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

global $smcFunc;

if (!isset($smcFunc['db_create_table']))
	db_extend('packages');

// Add a column to the topics table
$smcFunc['db_add_column'](
	'{db_prefix}topics', 
	[
		'name' => 'is_solved',
		'type' => 'tinyint',
		'default' => 0,
		'unsigned' => true,
		'not_null' => true,
	]
);

// Add a column to the boards table
$smcFunc['db_add_column'](
	'{db_prefix}boards', 
	[
		'name' => 'can_solve',
		'type' => 'tinyint',
		'default' => 0,
		'unsigned' => true,
		'not_null' => true,
	]
);