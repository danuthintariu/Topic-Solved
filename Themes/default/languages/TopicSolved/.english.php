<?php

/**
 * @package Topic Solved
 * @version 1.2
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 */

// Main
$txt['TopicSolved_settings'] = 'Topic Solved Settings';
$txt['TopicSolved_mark_solved'] = 'Mark Solved';
$txt['TopicSolved_mark_unsolved'] = 'Mark Unsolved';

// Boards
$txt['TopicSolved_board_solve'] = 'Can mark topics as solved';
$txt['TopicSolved_boards_select'] = 'Select boards where you can mark topics as solved';

// Error
$txt['TopicSolved_error_no_topic'] = 'No topic specified';
$txt['TopicSolved_error_no_board'] = 'This board is not configured to allow solving topics';
$txt['cannot_solve_topics_own'] = 'You cannot solve your own topics';
$txt['cannot_solve_topics_any'] = 'You cannot solve topics in this board';

// Permissions
$txt['permissionname_solve_topics'] = 'Mark topics as solved';
$txt['permissionname_solve_topics_own'] = 'Mark their own topics as solved';
$txt['permissionname_solve_topics_any'] = 'Mark any topic as solved';
$txt['permissionhelp_solve_topics'] = 'This permission allows a user to mark a topic as solved';

// Settings
$txt['TopicSolved_automove_enable'] = 'Enable auto moving solved or not solved topics to specfic boards';
$txt['TopicSolved_automove_enable_desc'] = 'You can select the boards for each board when editing or adding boards within the forum admin. The topic is only moved when there is a change in the solved status.';
$txt['TopicSolved_solve_automove'] = 'Status to auto move solved topics?';
$txt['TopicSolved_solved_destination'] = 'Select destination board for auto moving solved topics';
$txt['TopicSolved_notsolved_destination'] = 'Not solved destination';
$txt['TopicSolved_indicatorclass_disable'] = 'Disable color and icon indicators for solved topics';
$txt['TopicSolved_single_status'] = 'Use single status for auto-move';
$txt['TopicSolved_single_status_desc'] = 'When a board has auto-move enabled, enable this setting to only ever show the status that causes topics to move. For example: "Not Solved" will not be displayed if the board moves topics with "Solved" status';
$txt['TopicSolved_auto_solved'] = 'Solved';
$txt['TopicSolved_auto_notsolved'] = 'Not Solved';
$txt['TopicSolved_maint_board_solve'] = 'Solve Topics in a board';
$txt['TopicSolved_maint_board_solve_desc'] = 'This action is unaffected by the auto-move setting, no topics will be moved when performing this action.';
$txt['TopicSolved_maint_solve_status'] = 'Status to set for the topics';
$txt['TopicSolved_maint_board_solve_save'] = 'Update now';
$txt['TopicSolved_maint_board_solve_sure'] = 'Are you sure you want to change the status for all of the topics in the specificed board?';

// Loggging
$txt['TopicSolved_log'] = 'Solved Topics Log';
$txt['TopicSolved_log_desc'] = 'Use this area to view the topics marked as solved.';
$txt['TopicSolved_no_logs'] = 'No topics have been solved.';
$txt['TopicSolved_log_marked_solved'] = '<a href="%1$s">%2$s</a> marked as solved.';
$txt['TopicSolved_log_marked_not_solved'] = '<a href="%1$s">%2$s</a> marked as not solved.';
$txt['TopicSolved_moved_log'] = 'Destination Board';
$txt['modlog_ac_clearlog_solved'] = 'Cleared the solved topics log';
