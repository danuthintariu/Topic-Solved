<?php

/**
 * @package Topic Solved
 * @version 1.2
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 */

// Main
$txt['TopicSolved_settings'] = 'Subiecte Rezolvate Setări';
$txt['TopicSolved_mark_solved'] = 'Marchează ca rezolvat';
$txt['TopicSolved_mark_unsolved'] = 'Marchează ca nerezolvat';

// Boards
$txt['TopicSolved_board_solve'] = 'Poate marca subiectele ca fiind rezolvate';
$txt['TopicSolved_boards_select'] = 'Selectează secțiunile în care subiectele pot fi marcate ca fiind rezolvate';

// Error
$txt['TopicSolved_error_no_topic'] = 'Nici un subiect specificat';
$txt['TopicSolved_error_no_board'] = 'Această secțiune nu este configurată pentru a permite marcarea subiectelor ca rezolvate';
$txt['cannot_solve_topics_own'] = 'Nu poți marca subiectele proprii ca rezolvate';
$txt['cannot_solve_topics_any'] = 'Nu poți marca subiectele ca rezolvate în această secțiune';

// Permissions
$txt['permissionname_solve_topics'] = 'Marchează subiectele ca rezolvate';
$txt['permissionname_solve_topics_own'] = 'Marchează propriile subiecte ca rezolvate';
$txt['permissionname_solve_topics_any'] = 'Marchează orice subiect ca rezolvat';
$txt['permissionhelp_solve_topics'] = 'Această permisiune permite unui utilizator să marcheze un subiect ca rezolvat';

// Settings
$txt['TopicSolved_automove_enable'] = 'Activează auto-mutarea subiectelor rezolvate sau nerezolvate în secţiunile speciale';
$txt['TopicSolved_automove_enable_desc'] = 'Poți selecta secțiunile pentru fiecare secțiune când editezi sau adăugi secțiuni în administratorul forumului. Subiectul este mutat doar atunci când există o modificare a statutului rezolvat.';
$txt['TopicSolved_solve_automove'] = 'Status de mutare automată a subiectelor rezolvate?';
$txt['TopicSolved_solved_destination'] = 'Selectează secțiunea de destinație pentru mutarea automată a subiectelor rezolvate';
$txt['TopicSolved_notsolved_destination'] = 'Destinație pentru nerezolvate';
$txt['TopicSolved_indicatorclass_disable'] = 'Dezactivează culoarea și indicatorii pictogramelor pentru subiectele rezolvate';
$txt['TopicSolved_single_status'] = 'Folosește o singură stare pentru mutare automată';
$txt['TopicSolved_single_status_desc'] = 'Când o secțiune are activată mutarea automată, activați această setare pentru a afișa doar starea care cauzează mutarea subiectelor. De exemplu: "Nerezolvat" nu va fi afișat dacă secțiunea mută subiecte cu statusul "Rezolvat"';
$txt['TopicSolved_auto_solved'] = 'Rezolvat';
$txt['TopicSolved_auto_notsolved'] = 'Nerezolvat';
$txt['TopicSolved_maint_board_solve'] = 'Rezolvă subiectele într-o secțiune';
$txt['TopicSolved_maint_board_solve_desc'] = 'Această acțiune nu este afectată de setarea de mutare automată, nici un subiect nu va fi mutat la efectuarea acestei acțiuni.';
$txt['TopicSolved_maint_solve_status'] = 'Status de setat pentru subiecte';
$txt['TopicSolved_maint_board_solve_save'] = 'Actualizează acum';
$txt['TopicSolved_maint_board_solve_sure'] = 'Sigur vrei să schimbi statusul tuturor subiectelor din secțiunea specificată?';

// Loggging
$txt['TopicSolved_log'] = 'Jurnal de subiecte rezolvate';
$txt['TopicSolved_log_desc'] = 'Folosește această zonă pentru a vizualiza subiectele marcate ca rezolvate.';
$txt['TopicSolved_no_logs'] = 'Nici un subiect nu a fost rezolvat.';
$txt['TopicSolved_log_marked_solved'] = '<a href="%1$s">%2$s</a> marcat ca rezolvat.';
$txt['TopicSolved_log_marked_not_solved'] = '<a href="%1$s">%2$s</a> marcat ca nerezolvat.';
$txt['TopicSolved_moved_log'] = 'Destinație secțiune';
$txt['modlog_ac_clearlog_solved'] = 'Jurnal de subiecte rezolvate curățat';
