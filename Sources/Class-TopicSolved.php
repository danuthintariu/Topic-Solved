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
	 * @var int Solved log type
	 */
	private int $logType = 4;

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

		// Moderation Center
		add_integration_function('integrate_log_types', __CLASS__ . '::logTypes#', false, $sourcedir . '/Class-TopicSolved.php');
		add_integration_function('integrate_moderate_areas', __CLASS__ . '::moderate#', false, $sourcedir . '/Class-TopicSolved.php');

		// Maintenance
		add_integration_function('integrate_manage_maintenance', __CLASS__ . '::manageMaintenance#', false, $sourcedir . '/Class-TopicSolved.php');
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
		$config_vars[] = ['check', 'TopicSolved_single_status', 'subtext' => $txt['TopicSolved_single_status_desc']];

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
			SELECT t.id_topic, t.is_solved, t.id_first_msg, t.id_board, t.solved_board, m.id_member, b.solve_automove, b.solved_destination
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

		// auto-move single status?
		$solved_status = $topic_info['is_solved'] ? 0 : 1;
		if (!empty($modSettings['TopicSolved_single_status']) && !empty($topic_info['solved_destination'])) {
			$solved_status = !empty($topic_info['solve_automove']) ? 0 : 1;
		}

		// Solve or unsolve...
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}topics
			SET
				is_solved = {int:is_solved}
			WHERE id_topic = {int:topic}',
			[
				'is_solved' => $solved_status,
				'topic' => $topic_info['id_topic'],
			]
		);

		// Auto-move topic?
		$destinationBoard = !$solved_status == $topic_info['solve_automove'] ? $topic_info['solved_destination'] : 0;
		$this->automove($topic_info['id_topic'], $destinationBoard);
		
		// Log the action
		logAction('solve', ['topic' => $topic_info['id_topic'], 'board' => $destinationBoard, 'solved' => $topic_info['is_solved'] ? 0 : 1], 'solved');

		// We are done here
		if (empty($destinationBoard)) {
			redirectexit('topic=' . $_REQUEST['topic'] . '.0');
		} else {
			redirectexit('board=' . $topic_info['id_board'] . '.0');
		}
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
		global $context, $scripturl, $modSettings, $board, $user_info, $smcFunc;

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

		// Auto-move behavior
		if (!empty($modSettings['TopicSolved_automove_enable']) && !empty($modSettings['TopicSolved_single_status'])) {
			$board_info = $smcFunc['db_query']('', '
				SELECT b.solve_automove, b.solved_destination
				FROM {db_prefix}boards as b
				WHERE id_board = {int:board}',
				[
					'board' => $board,
				]
			);
			$context['solved_board_info'] = $smcFunc['db_fetch_assoc']($board_info);
			$smcFunc['db_free_result']($board_info);

			if (!empty($context['solved_board_info']['solved_destination'])) {
				$buttons['solve']['text'] = 'TopicSolved_mark_' . (empty($context['solved_board_info']['solve_automove']) ? 'solved' : 'unsolved');
				$buttons['solve']['class'] = 'topic_' . (empty($context['solved_board_info']['solve_automove']) ? 'solve' : 'unsolve');
			}
		}
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
			$boardColumns[] = 'b.solve_automove';
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
			$boards[$row['id_board']]['solve_automove'] = $row['solve_automove'];
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
			$boardOptions['solve_automove'] = $_POST['TopicSolved_solve_automove'] ?? 0;
			$boardOptions['solved_destination'] = $_POST['TopicSolved_solved_destination'] ?? 0;
			
			$boardUpdates[] = 'solve_automove = {int:solve_automove}';
			$boardUpdates[] = 'solved_destination = {int:solved_destination}';

			$boardUpdateParameters['solve_automove'] = $boardOptions['solve_automove'] ?? 0;
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
			$context['custom_board_settings']['solve_automove'] = [
				'dt' => '<label for="TopicSolved_solve_automove"><strong>'. $txt['TopicSolved_solve_automove']. '</strong></label>',
				'dd' => '<select id="TopicSolved_solve_automove" name="TopicSolved_solve_automove">
							<option value="0"' . (empty($context['board']['solve_automove']) ? ' selected' : '') . '>' . $txt['TopicSolved_auto_solved'] . '</option>
							<option value="1"' . (!empty($context['board']['solve_automove']) ? ' selected' : '') . '>' . $txt['TopicSolved_auto_notsolved'] . '</option>
						</select>',
			];
			$context['custom_board_settings']['solved_destination'] = [
				'dt' => '<label for="TopicSolved_solved_destination"><strong>'. $txt['TopicSolved_solved_destination']. '</strong></label>',
				'dd' => $this->boardsList(),
			];
		}
	}

	/**
	 * Get a list of boards using SMF functions
	 * 
	 * @param bool The solved status.
	 * 
	 * @return string A formatted select HTML element with the forum boards.
	 */
	private function boardsList(bool $setting_type = true) : string
	{
		global $sourcedir, $context, $txt;

		require_once($sourcedir . '/Subs-MessageIndex.php');
		$board_list = getBoardList(['not_redirection' => true, 'excluded_boards' => [$context['board']['id']]]);
		
		$boards_select = '<select id="TopicSolved_' . (!$setting_type ? 'not' : '') . 'solved_destination" name="TopicSolved_' . (!$setting_type ? 'not' : '') . 'solved_destination">
				<option value="0">' . $txt['none'] . '</option>';

		foreach ($board_list as $board_category) {
			$boards_select .= '<optgroup label="' . $board_category['name'] . '">';

			foreach ($board_category['boards'] as $board_option) {
				$boards_select .= '<option value="' . $board_option['id'] . '" ' . (!empty($context['board'][(!$setting_type ? 'not' : '') . 'solved_destination']) && $context['board'][(!$setting_type ? 'not' : '') . 'solved_destination'] == $board_option['id'] ? ' selected' : '') . '>' . $board_option['name'] . '</option>';
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
	public function permissions(&$permissionGroups, &$permissionList) : void
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

	/**
	 * Add solved log type
	 * 
	 * @param array The typs of logs
	 */
	public function logTypes(array &$log_types) : void
	{
		$log_types['solved'] = $this->logType;
	}

	/**
	 * Modeartion center areas
	 * 
	 * @param array The moderation center areas menu
	 */
	public function moderate(array &$areas) : void
	{
		global $txt;

		$this->language();

		$areas['logs']['areas']['solvedlogs'] = [
			'label' => $txt['TopicSolved_log'],
			'icon' => 'valid',
			'function' => [new self, 'logs'],
		];
	}

	/**
	 * Delete solved log entry
	 */
	private function delete() : void
	{
		global $smcFunc;

		if (!isset($_POST['removeall']) && !isset($_POST['delete'])) {
			return;
		}

		checkSession();
		validateToken('mod-solved');
		isAllowedTo('admin_forum');

		if (isset($_POST['removeall'])) {
			$this->deleteAll();
		}

		if (isset($_POST['delete']) && !empty($_POST['delete'])) {
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}log_actions
				WHERE id_log = {int:logtype}
					AND id_action iN ({array_int:entries})
					AND action = {string:action}',
				[
					'logtype' => $this->logType,
					'action' => 'solve',
					'entries' => array_unique($_POST['delete'])
				]
			);
		}
	}

	/**
	 * Delete all the solved log entries
	 */
	private function deleteAll() : void
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}log_actions
			WHERE id_log = {int:logtype}
				AND action = {string:action}',
			[
				'logtype' => $this->logType,
				'action' => 'solve'
			]
		);

		// Log this admin action
		logAction('clearlog_solve', [], 'admin');
	}

	/**
	 * Solved Topics log
	 */
	public function logs() : void
	{
		global $context, $txt, $sourcedir, $smcFunc, $scripturl;

		// Deleting entries from the log?
		$this->delete();

		loadLanguage('Admin+Modlog');

		$context['url_start'] = '?action=moderate;area=solvedlogs';
		$context['page_title'] = $txt['TopicSolved_log'];
		$context['can_delete'] = allowedTo('admin_forum');
		$context[$context['moderation_menu_name']]['tab_data'] = [
			'title' => $txt['TopicSolved_log'],
			'description' => $txt['TopicSolved_log_desc'],
		];

		// Do the column stuff!
		$sort_types = [
			'action' => 'lm.action',
			'time' => 'lm.log_time',
			'member' => 'mem.real_name',
		];

		// Setup the direction stuff...
		$context['order'] = isset($_REQUEST['sort']) && isset($sort_types[$_REQUEST['sort']]) ? $_REQUEST['sort'] : 'time';

		// If we're coming from a search, get the variables.
		if (!empty($_REQUEST['params']) && empty($_REQUEST['is_search']))
		{
			$search_params = base64_decode(strtr($_REQUEST['params'], array(' ' => '+')));
			$search_params = $smcFunc['json_decode']($search_params, true);
		}

		// This array houses all the valid search types.
		$searchTypes = [
			'action' => ['sql' => 'lm.action', 'label' => $txt['modlog_action']],
			'member' => ['sql' => 'mem.real_name', 'label' => $txt['modlog_member']],
			'group' => ['sql' => 'mg.group_name', 'label' => $txt['modlog_position']],
			'ip' => ['sql' => 'lm.ip', 'label' => $txt['modlog_ip']]
		];

		if (!isset($search_params['string']) || (!empty($_REQUEST['search']) && $search_params['string'] != $_REQUEST['search']))
			$search_params_string = empty($_REQUEST['search']) ? '' : $_REQUEST['search'];
		else
			$search_params_string = $search_params['string'];

		if (isset($_REQUEST['search_type']) || empty($search_params['type']) || !isset($searchTypes[$search_params['type']]))
			$search_params_type = isset($_REQUEST['search_type']) && isset($searchTypes[$_REQUEST['search_type']]) ? $_REQUEST['search_type'] : (isset($searchTypes[$context['order']]) ? $context['order'] : 'member');
		else
			$search_params_type = $search_params['type'];

		$search_params_column = $searchTypes[$search_params_type]['sql'];
		$search_params = [
			'string' => $search_params_string,
			'type' => $search_params_type,
		];

		// Setup the search context.
		$context['search_params'] = empty($search_params['string']) ? '' : base64_encode($smcFunc['json_encode']($search_params));
		$context['search'] = array(
			'string' => $search_params['string'],
			'type' => $search_params['type'],
			'label' => $searchTypes[$search_params_type]['label'],
		);

		$this->language();
		require_once($sourcedir . '/Subs-List.php');
		require_once($sourcedir . '/Modlog.php');
		$listOptions = [
			'id' => 'solved_log_list',
			// 'title' => $txt['TopicSolved_log'],
			'items_per_page' => 20,
			'not_items_label' => $txt['TopicSolved_no_logs'],
			'base_href' => $scripturl . $context['url_start'],
			'default_sort_col' => 'time',
			'get_items' => [
				'function' => 'list_getModLogEntries',
				'params' => [
				(!empty($search_params['string']) ? ' INSTR({raw:sql_type}, {string:search_string}) > 0' : ''),
				['sql_type' => $search_params_column, 'search_string' => $search_params['string']],
				$this->logType,
				]
			],
			'get_count' => [
				'function' => 'list_getModLogEntryCount',
				'params' => [
				(!empty($search_params['string']) ? ' INSTR({raw:sql_type}, {string:search_string}) > 0' : ''),
				['sql_type' => $search_params_column, 'search_string' => $search_params['string']],
				$this->logType,
				]
			],
			'columns' => [
				'action' => [
					'header' => [
						'value' => $txt['modlog_action'],
						'class' => 'lefttext',
					],
					'data' => [
						'function' => function($row) use($txt, $scripturl) {
							return sprintf($txt['TopicSolved_log_marked_' . (!empty($row['extra']['solved']) ? 'solved' : 'not_solved')], $scripturl . '?topic=' . $row['topic']['id'] . '.0', $row['topic']['subject']);
						},
					],
				],
				'time' => [
					'header' => [
						'value' => $txt['modlog_date'],
						'class' => 'lefttext'
					],
					'data' => [
						'db' => 'time'
					],
					'sort' => [
						'default' => 'lm.log_time DESC',
						'reverse' => 'lm.log_time',
					]
				],
				'member' => [
					'header' => [
						'value' => $txt['modlog_member'],
						'class' => 'lefttext'
					],
					'data' => [
						'db' => 'moderator_link'
					],
					'sort' => [
						'default' => 'mem.real_name',
						'reverse' => 'mem.real_name DESC',
					]
				],
				'board' => [
					'header' => [
						'value' => $txt['TopicSolved_moved_log'],
						'class' => 'lefttext'
					],
					'data' => [
						'function' => function($row) use($txt) {
							return $row['extra']['board'] ?? $txt['none'];
						},
					],
				],
				'delete' => [
					'header' => [
						'value' => '<input type="checkbox" name="all" onclick="invertAll(this, this.form);">',
						'class' => 'centercol',
					],
					'data' => [
						'function' => function($row) {
							return '<input type="checkbox" name="delete[]" value="' . $row['id'] . '"' . ($row['editable'] ? '' : ' disabled') . '>';
						},
						'class' => 'centercol',
					]
				],
			],


			'form' => [
				'href' => $scripturl . $context['url_start'],
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => [
					$context['session_var'] => $context['session_id'],
					'params' => $context['search_params']
				],
				'token' => 'mod-solved',
			],
			'additional_rows' => [
				[
				'position' => 'after_title',
				'value' => '
					' . $txt['modlog_search'] . ' (' . $txt['modlog_by'] . ': ' . $context['search']['label'] . '):
					<input type="text" name="search" size="18" value="' . $smcFunc['htmlspecialchars']($context['search']['string']) . '">
					<input type="submit" name="is_search" value="' . $txt['modlog_go'] . '" class="button" style="float:none">
					' . ($context['can_delete'] ? '
					<input type="submit" name="remove" value="' . $txt['modlog_remove'] . '" data-confirm="' . $txt['modlog_remove_selected_confirm'] . '" class="button you_sure">
					<input type="submit" name="removeall" value="' . $txt['modlog_removeall'] . '" data-confirm="' . $txt['modlog_remove_all_confirm'] . '" class="button you_sure">' : ''),
				'class' => '',
				],
				[
				'position' => 'below_table_data',
				'value' => $context['can_delete'] ? '
					<input type="submit" name="remove" value="' . $txt['modlog_remove'] . '" data-confirm="' . $txt['modlog_remove_selected_confirm'] . '" class="button you_sure">
					<input type="submit" name="removeall" value="' . $txt['modlog_removeall'] . '" data-confirm="' . $txt['modlog_remove_all_confirm'] . '" class="button you_sure">' : '',
				'class' => 'floatright',
				],
			],

		];

		createToken('mod-solved');
		createList($listOptions);
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'solved_log_list';
	}

	/**
	 * Modify the maintenance subactions
	 * 
	 * @param array Subactions for the maintenance section
	 */
	public function manageMaintenance(array &$subActions) : void
	{
		global $context;

		$subActions['topics']['activities']['solveboard'] = [$this, 'boardSolve'];

		// Add sub-layer
		loadTemplate('TopicSolved');
		$context['template_layers'][] = 'topic_solved';
	}

	/**
	 * Mark zll topics as solved or not solved in a board
	 */
	public function boardSolve() : void
	{
		global $smcFunc;

		checkSession();
		validateToken('admin-maint');

		$smcFunc['db_query']('','
			UPDATE {db_prefix}topics
			SET
				is_solved = {int:solved}
			WHERE id_board = {int:board}
				AND approved = {int:approved}
				AND id_redirect_topic = {int:redirect}',
			[
				'solved' => (int) isset($_REQUEST['solved_status']) ? 1 : 0,
				'board' => (int) $_REQUEST['id_board_solve'],
				'approved' => 1,
				'redirect' => 0
			]
		);
	}
}