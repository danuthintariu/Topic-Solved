<?php

/**
 * @package Topic Solved
 * @version 1.2
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2025, SMF Tricks
 */

global $smcFunc;

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

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
// Add a column for choosing which status to use for auto-moving.
$smcFunc['db_add_column'](
	'{db_prefix}boards', 
	[
		'name' => 'solve_automove',
		'type' => 'tinyint',
		'default' => 0,
		'unsigned' => true,
		'not_null' => true,
	]
);
// Add a column for the destination board when auto-moving
$smcFunc['db_add_column'](
	'{db_prefix}boards', 
	[
		'name' => 'solved_destination',
		'type' => 'smallint',
		'default' => 0,
		'unsigned' => true,
		'not_null' => true,
	]
);