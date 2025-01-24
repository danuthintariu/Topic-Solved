<?php

/**
 * @package Topic Solved
 * @version 1.2
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2025, SMF Tricks
 */

class TopicSolved
{
	/**
	 * @var array The array of boards using topic solved
	 */
	private array $boards = [];

	/**
	 * Load the hooks used by the mod
	 */
	public function hooks() : void
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
	 * @param array $config_vars: The config vars for the settings
	 */
	public function settings(array &$config_vars) : void
	{
		global $txt, $smcFunc;

		if (!empty($config_vars))
			$config_vars[] = '';

		$config_vars[] = ['title', 'TopicSolved_settings'];
		$config_vars[] = ['boards', 'TopicSolved_boards_can_solve', 'label' => $txt['TopicSolved_boards_select']];
		$config_vars[] = ['check', 'TopicSolved_indicatorclass_disable'];
		$config_vars[] = ['check', 'TopicSolved_automove_enable', 'subtext' => $txt['TopicSolved_automove_enable_desc']];

		// Set those boards to be able to solve topics when saving this setting...
		if (!isset($_REQUEST['save'])) {
			return;
		}

		// Setup the boards for the array
		$this->boards = isset($_REQUEST['TopicSolved_boards_can_solve']) ? array_keys($_REQUEST['TopicSolved_boards_can_solve']) : [0];

		// Boards that can solve topics
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}boards
			SET can_solve = {int:TopicSolved}',
			[
				'boards' => $this->boards,
				'TopicSolved' => 1,
			]
		);

		// Boards that can't solve topics
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}boards
			SET can_solve = {int:TopicSolved}
			WHERE id_board NOT IN ({array_int:boards})',
			[
				'boards' => $this->boards,
				'TopicSolved' => 0,
			]
		);
	}

	/**
	 * Add the action to mark a topic as solved or unsolved
	 * 
	 * @param array $actions: The forum actions
	 */
	public function actions(array &$actions) : void
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
	 */
	public function solve() : void
	{
		global $smcFunc, $user_info, $modSettings;

		// Load the language file
		$this->language();
		
		// We need a topic id
		if (!isset($_REQUEST['topic']) || empty($_REQUEST['topic'])) {
			fatal_lang_error('TopicSolved_error_no_topic', false);
		}

		checkSession('get');

		// Get the topic
		$request = $smcFunc['db_query']('', '
			SELECT t.id_topic, t.is_solved, t.id_first_msg, t.id_board, t.solved_board, m.id_member, b.solved_destination
			FROM {db_prefix}topics AS t
				LEFT JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
				LEFT JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
			WHERE t.id_topic = {int:topic}',
			[
				'topic' => (int) $_REQUEST['topic'],
			]
		);
		$topic_info = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		// Check if the topic exists
		if (empty($topic_info)) {
			fatal_lang_error('TopicSolved_error_no_topic', false);
		}

		// Can they solve in this board?
		if (!in_array($topic_info['id_board'], explode(',', $modSettings['TopicSolved_boards_can_solve']))) {
			fatal_lang_error('TopicSolved_error_no_board', false);
		}

		// Do you have permission to solve this topic?
		$user_solve = !allowedTo('solve_topics_any');
		if ($user_solve && $topic_info['id_member'] == $user_info['id']) {
			isAllowedTo('solve_topics_own');
		} else {
			isAllowedTo('solve_topics_any');
		}

		// Solve or unsolve...
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}topics
			SET
				is_solved = {int:is_solved}' . (!empty($modSettings['TopicSolved_automove_enable']) ? ',
				solved_board = {int:solved_board}' : '' ) . '
			WHERE id_topic = {int:topic}',
			[
				'is_solved' => $topic_info['is_solved'] ? 0 : 1,
				'topic' => $topic_info['id_topic'],
				'solved_board' => empty($topic_info['is_solved']) && empty($topic_info['solved_board']) ? $topic_info['id_board'] : 0,
			]
		);

		// Auto-move topic?
		$this->automove($topic_info['id_topic'], !empty($topic_info['solved_board']) ? $topic_info['solved_board'] : $topic_info['solved_destination'] ?? 0);

		// We are done here
		redirectexit('topic=' . $_REQUEST['topic'] . '.0');
	}

	/**
	 * Add the solved column to the message index
	 * 
	 * @param array The columns to select
	 */
	public function message_index(array &$message_index_selects) : void
	{
		$message_index_selects[] = 't.is_solved';
	}

	/**
	 * Add the class to the solved topics
	 */
	public function topic_class() : void
	{
		global $context, $board, $modSettings;

		// Check for topics, if solving is enabled, if indicators are enabled
		if (empty($context['topics']) || !in_array($board, explode(',', $modSettings['TopicSolved_boards_can_solve'])) || !empty($modSettings['TopicSolved_indicatorclass_disable'])) {
			return;
		}

		// Load css file for this
		loadCSSFile('topicsolved.css', ['default_theme' => true, 'minimize' => true], 'smf_topic_solved');

		// Is the topic solved?
		foreach ($context['topics'] as $topic) {
			if ($topic['is_solved']) {
				$context['topics'][$topic['id']]['css_class'] .= ' solved';
			}
		}
	}

	/**
	 * Add the solved column to the topic
	 * 
	 * @param array The columns being selected.
	 */
	public function display_topic(array &$topic_selects) : void
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

		// Check if it's available and can solve
		if (!isset($context['topicinfo']['is_solved']) || !in_array($board, explode(',', $modSettings['TopicSolved_boards_can_solve']))) {
			return;
		}

		// Can you solve topics?
		if ((!allowedTo('solve_topics_any') && !allowedTo('solve_topics_own')) || (!allowedTo('solve_topics_any') && allowedTo('solve_topics_own') && $user_info['id'] != $context['topicinfo']['id_member_started'])) {
			return;
		}

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
		global $modSettings;
	
		$boardColumns[] = 'b.can_solve';

		if (!empty($modSettings['TopicSolved_automove_enable'])) {
			$boardColumns[] = 'b.solved_destination';
		}
	}

	/**
	 * Board Tree
	 * 
	 * @return void
	 */
	public function boardtree_board($row) : void
	{
		global $boards, $modSettings;

		$boards[$row['id_board']]['can_solve'] = $row['can_solve'];

		if (!empty($modSettings['TopicSolved_automove_enable'])) {
			$boards[$row['id_board']]['solved_destination'] = $row['solved_destination'];
		}
	}

	/**
	 * Modify Boards
	 * 
	 * @param int $id: The board id
	 * @param array $boardOptions: The options for the board
	 * @param array $boardUpdates: The columns being updated
	 * @param array $boardUpdateParameters: The values for the columns
	 */
	public function modify_boards(int $id, array $boardOptions, array &$boardUpdates, array &$boardUpdateParameters) : void
	{
		global $modSettings;

		// Mark topic as solved
		$boardOptions['can_solve'] = isset($_POST['TopicSolved_board_solve']);
		$boardUpdates[] = 'can_solve = {int:can_solve}';
		$boardUpdateParameters['can_solve'] = $boardOptions['can_solve'] ? 1 : 0;
		
		// Get the solved boards
		$solvedBoards = explode(',', $modSettings['TopicSolved_boards_can_solve']);
		
		// Add the board to the boards that can solve topics, if it's not there already
		if (!empty($boardOptions['can_solve']) && !in_array($id, $solvedBoards)) {
			updateSettings(['TopicSolved_boards_can_solve' => !empty($modSettings['TopicSolved_boards_can_solve']) ? implode(',', array_merge($solvedBoards, [$id])) : $id]);
		}
		// Remove the board from the required boards, if it's there
		elseif (empty($boardOptions['can_solve']) && in_array($id, $solvedBoards)) {
			updateSettings(['TopicSolved_boards_can_solve' => implode(',', array_diff($solvedBoards, [$id]))], true);
		}

		// Auto Move solved topic
		if (!empty($modSettings['TopicSolved_automove_enable'])) {
			$boardOptions['solved_destination'] = $_POST['TopicSolved_solved_destination'] ?? 0;
			$boardUpdates[] = 'solved_destination = {int:solved_destination}';
			$boardUpdateParameters['solved_destination'] = $boardOptions['solved_destination'] ?? 0;
		}
	}

	/**
	 * Edit board
	 */
	public function edit_board() : void
	{
		global $context, $txt, $modSettings;

		$context['custom_board_settings']['can_solve'] = [
			'dt' => '<label for="TopicSolved_board_solve"><strong>'. $txt['TopicSolved_board_solve']. '</strong></label>',
			'dd' => '<input type="checkbox" id="TopicSolved_board_solve" name="TopicSolved_board_solve" class="input_check"'. (!empty($context['board']['can_solve']) ? ' checked="checked"' : ''). '>',
		];

		// Auto move solved topics
		if (!empty($modSettings['TopicSolved_automove_enable'])) {
			$context['custom_board_settings']['solved_destination'] = [
				'dt' => '<label for="TopicSolved_solved_destination"><strong>'. $txt['TopicSolved_automove_where']. '</strong></label>',
				'dd' => $this->boardsList(),
			];
		}
	}

	/**
	 * Get a list of boards using SMF functions
	 * 
	 * @return string A formatted select HTML element with the forum boards.
	 */
	private function boardsList() : string
	{
		global $sourcedir, $context, $txt;

		require_once($sourcedir . '/Subs-MessageIndex.php');
		$board_list = getBoardList(['not_redirection' => true, 'excluded_boards' => [$context['board']['id']]]);
		
		$boards_select = '<select id="TopicSolved_solved_destination" name="TopicSolved_solved_destination">
				<option value="0">' . $txt['none'] . '</option>';

		foreach ($board_list as $board_category) {
			$boards_select .= '<optgroup label="' . $board_category['name'] . '">';

			foreach ($board_category['boards'] as $board_option) {
				$boards_select .= '<option value="' . $board_option['id'] . '" ' . (!empty($context['board']['solved_destination']) && $context['board']['solved_destination'] == $board_option['id'] ? ' selected' : '') . '>' . $board_option['name'] . '</option>';
			}

			$boards_select .= '</optgroup>';
		}

		$boards_select .= '</select>';

		return $boards_select;
	}

	/**
	 * Permissions
	 * 
	 * @param array $permissionList: The list of permissions
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
	 * @param int $id_msg: The id of the mssage
	 */
	public function best_answer(int $id_msg) : void
	{
		global $smcFunc, $modSettings, $board;

		// Can we solve in this board?
		if (!in_array($board, explode(',', $modSettings['TopicSolved_boards_can_solve']))) {
			return;
		}

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

	/**
	 * Auto move a solved topic
	 * 
	 * @param int topic_id The topic to move
	 * @param int board_id The board to move the topic to
	 */
	private function automove(int $topicID, int $boardID) : void
	{
		global $modSettings, $sourcedir;

		// Auto move is enabled and there is a board selected?
		if (empty($modSettings['TopicSolved_automove_enable']) || empty($boardID)) {
			return;
		}

		include_once($sourcedir . '/MoveTopic.php');
		moveTopics($topicID, $boardID);
	}
}