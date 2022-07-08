<?php

/**
 * @package Topic Solved
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 */

class TopicSolved
{
	public static function hooks()
	{
		global $sourcedir, $modSettings;

		// Create the setting
		$modSettings = array_merge(['TopicSolved_boards_can_solve' => ''], $modSettings);

		// Action
		add_integration_function('integrate_actions', __CLASS__ . '::actions#', false, $sourcedir . '/Class-TopicSolved.php');

		// MessageIndex
		add_integration_function('integrate_message_index', __CLASS__ . '::message_index#', false, $sourcedir . '/Class-TopicSolved.php');
		add_integration_function('integrate_messageindex_buttons', __CLASS__ . '::topic_class#', false, $sourcedir . '/Class-TopicSolved.php');

		// Display
		add_integration_function('integrate_display_topic', __CLASS__ . '::display_topic#', false, $sourcedir . '/Class-TopicSolved.php');
		add_integration_function('integrate_display_buttons', __CLASS__ . '::display_buttons#', false, $sourcedir . '/Class-TopicSolved.php');

		// Manage Boards
		add_integration_function('integrate_admin_areas', __CLASS__ . '::language#', false, $sourcedir . '/Class-TopicSolved.php');
		add_integration_function('integrate_pre_boardtree', __CLASS__ . '::pre_boardtree#', false, $sourcedir . '/Class-TopicSolved.php');
		add_integration_function('integrate_boardtree_board', __CLASS__ . '::boardtree_board#', false, $sourcedir . '/Class-TopicSolved.php');
		add_integration_function('integrate_modify_board', __CLASS__ . '::modify_boards#', false, $sourcedir . '/Class-TopicSolved.php');
		add_integration_function('integrate_edit_board', __CLASS__ . '::edit_board#', false, $sourcedir . '/Class-TopicSolved.php');

		// Permissions
		add_integration_function('integrate_load_permissions', __CLASS__ . '::permissions#', false, $sourcedir . '/Class-TopicSolved.php');
		add_integration_function('integrate_helpadmin', __CLASS__ . '::language#', false, $sourcedir . '/Class-TopicSolved.php');

		// Settings
		add_integration_function('integrate_general_mod_settings', __CLASS__ . '::settings#', false, $sourcedir . '/Class-TopicSolved.php');

		// Best Answer
		add_integration_function('integrate_sycho_best_answer', __CLASS__ . '::best_answer#', false, $sourcedir . '/Class-TopicSolved.php');
	}

	/**
	 * Mod Settings. A cheat setting to select boards.
	 * 
	 * @return void
	 */
	public static function settings(&$config_vars)
	{
		global $txt;

		if (!empty($config_vars))
			$config_vars[] = '';

		$config_vars[] = ['title', 'TopicSolved_settings'];
		$config_vars[] = ['boards', 'TopicSolved_boards_can_solve', 'label' => $txt['TopicSolved_boards_select']];
	}

	/**
	 * Add the action to mark a topic as solved or unsolved
	 * 
	 * @return void
	 */
	public function actions(&$actions) : void
	{
		$actions['topicsolve'] = ['Class-TopicSolved.php', __CLASS__ . '::solve#'];
	}

	/**
	 * Load the language when needed
	 */
	public function language() : void
	{
		loadLanguage('TopicSolved/');
	}

	/**
	 * Mark a topic as solved or unsolved
	 * 
	 * @return void
	 */
	public function solve() : void
	{
		global $smcFunc, $user_info, $modSettings;

		// Load the language file
		$this->language();
		
		// We need a topic id
		if (!isset($_REQUEST['topic']) || empty($_REQUEST['topic']))
			fatal_lang_error('TopicSolved_error_no_topic', false);

		checkSession('get');

		// Get the topic
		$request = $smcFunc['db_query']('', '
			SELECT t.id_topic, t.is_solved, t.id_first_msg, t.id_board, m.id_member
			FROM {db_prefix}topics AS t
				LEFT JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
			WHERE t.id_topic = {int:topic}',
			[
				'topic' => (int) $_REQUEST['topic'],
			]
		);
		$topic_info = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		// Check if the topic exists
		if (empty($topic_info))
			fatal_lang_error('TopicSolved_error_no_topic', false);

		// Can they solve in this board?
		if (!in_array($topic_info['id_board'], explode(',', $modSettings['TopicSolved_boards_can_solve'])))
			fatal_lang_error('TopicSolved_error_no_board', false);

		// Do you have permission to solve this topic?
		$user_solve = !allowedTo('solve_topics_any');
		if ($user_solve && $topic_info['id_member'] == $user_info['id'])
			isAllowedTo('solve_topics_own');
		else
			isAllowedTo('solve_topics_any');

		// Solve or unsolve...
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}topics
			SET is_solved = {int:is_solved}
			WHERE id_topic = {int:topic}',
			[
				'is_solved' => $topic_info['is_solved'] ? 0 : 1,
				'topic' => $topic_info['id_topic'],
			]
		);

		// We are done here
		redirectexit('topic=' . $_REQUEST['topic'] . '.0');
	}

	/**
	 * Add the solved column to the message index
	 * 
	 * @return void
	 */
	public function message_index(&$message_index_selects) : void
	{
		$message_index_selects[] = 't.is_solved';
	}

	/**
	 * Add the class to the solved topics
	 * 
	 * @return void
	 */
	public function topic_class() : void
	{
		global $context, $board, $modSettings;

		// No topics, no fun.
		if (empty($context['topics']))
			return;

		// Can we solve in this board?
		if (!in_array($board, explode(',', $modSettings['TopicSolved_boards_can_solve'])))
			return;

		// Load css file for this
		loadCSSFile('topicsolved.css', ['default_theme' => true, 'minimize' => true], 'smf_topic_solved');

		foreach ($context['topics'] as $topic)
			// Is the topic solved?
			if ($topic['is_solved'])
				$context['topics'][$topic['id']]['css_class'] .= ' solved';
	}

	/**
	 * Add the solved column to the topic
	 * 
	 * @return void
	 */
	public function display_topic(&$topic_selects) : void
	{
		$topic_selects[] = 't.is_solved';
	}

	/**
	 * Add the button to the topic buttons
	 * 
	 * @return void
	 */
	public function display_buttons(&$buttons) : void
	{
		global $context, $scripturl, $modSettings, $board, $user_info;

		// Check if it's available
		if (!isset($context['topicinfo']['is_solved']))
			return;

		// Can we solve in this board?
		if (!in_array($board, explode(',', $modSettings['TopicSolved_boards_can_solve'])))
			return;

		// Can you solve topics?
		if ((!allowedTo('solve_topics_any') && !allowedTo('solve_topics_own')) || (!allowedTo('solve_topics_any') && allowedTo('solve_topics_own') && $user_info['id'] != $context['topicinfo']['id_member_started']))
			return;

		// Language
		$this->language();

		// Load the css
		loadCSSFile('topicsolved.css', ['default_theme' => true, 'minimize' => true], 'smf_topic_solved');

		// Solving button
		$buttons['solve'] = ['text' => !empty($context['topicinfo']['is_solved']) ? 'TopicSolved_mark_unsolved' : 'TopicSolved_mark_solved', 'url' => $scripturl . '?action=topicsolve;topic=' . $context['current_topic'] . ';' . $context['session_var'] . '=' . $context['session_id'], 'class' => !empty($context['topicinfo']['is_solved']) ? 'topic_unsolve' : 'topic_solve'];
	}

	/**
	 * Pre Board Tree
	 * 
	 * @return void
	 */
	public function pre_boardtree(&$boardColumns) : void
	{
		$boardColumns[] = 'b.can_solve';
	}

	/**
	 * Board Tree
	 * 
	 * @return void
	 */
	public function boardtree_board($row) : void
	{
		global $boards;

		$boards[$row['id_board']]['can_solve'] = $row['can_solve'];
	}

	/**
	 * Modify Boards
	 * 
	 * @return void
	 */
	public function modify_boards($id, $boardOptions, &$boardUpdates, &$boardUpdateParameters) : void
	{
		global $modSettings;

		$boardOptions['can_solve'] = isset($_POST['TopicSolved_board_solve']);

		if (isset($boardOptions['can_solve']))
		{
			$boardUpdates[] = 'can_solve = {int:can_solve}';
			$boardUpdateParameters['can_solve'] = $boardOptions['can_solve'] ? 1 : 0;

			// Add the board to the boards that require prefixes, if it's not there already
			if (!empty($boardOptions['can_solve']) && !in_array($id, explode(',', $modSettings['TopicSolved_boards_can_solve'])))
				updateSettings(['TopicSolved_boards_can_solve' => !empty($modSettings['TopicSolved_boards_can_solve']) ? implode(',', array_merge(explode(',', $modSettings['TopicSolved_boards_can_solve']), [$id])) : $id]);
			// Remove the board from the required boards, if it's there
			elseif (empty($boardOptions['can_solve']) && in_array($id, explode(',', $modSettings['TopicSolved_boards_can_solve'])))
				updateSettings(['TopicSolved_boards_can_solve' => implode(',', array_diff(explode(',', $modSettings['TopicSolved_boards_can_solve']), [$id]))], true);
		}		
	}

	/**
	 * Edit board
	 * 
	 * @return void
	 */
	public function edit_board()
	{
		global $context, $txt;

		$context['custom_board_settings']['can_solve'] = [
			'dt' => '<label for="TopicSolved_board_solve"><strong>'. $txt['TopicSolved_board_solve']. '</strong></label>',
			'dd' => '<input type="checkbox" id="TopicSolved_board_solve" name="TopicSolved_board_solve" class="input_check"'. (!empty($context['board']['can_solve']) ? ' checked="checked"' : ''). '>',
		];
	}

	/**
	 * Permissions
	 * 
	 * @return void
	 */
	public function permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions) : void
	{
		$permissionList['board']['solve_topics'] = [true, 'topic', 'moderate'];
	}

	/**
	 * Best Answer
	 * 
	 * Mark the topic as solved after the best answer is chosen
	 * 
	 * @return void
	 */
	public function best_answer($id_msg) : void
	{
		global $smcFunc, $modSettings, $board;

		// Can we solve in this board?
		if (!in_array($board, explode(',', $modSettings['TopicSolved_boards_can_solve'])))
			return;

		// Find the topic from this msg
		$request = $smcFunc['db_query']('', '
			SELECT m.id_topic
			FROM {db_prefix}messages AS m
			WHERE m.id_msg = {int:id_msg}',
			[
				'id_msg' => $id_msg,
			]
		);
		$topic = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		// Mark the topic as solved
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}topics AS t
			SET is_solved = 1
			WHERE t.id_topic = {int:topic}',
			[
				'topic' => $topic['id_topic'],
			]
		);
	}
}