<?php

function template_topic_solved_above() {}

function template_topic_solved_below()
{
	global $scripturl, $context, $txt;

	echo '
	<div class="cat_bar">
			<h3 class="catbg">', $txt['TopicSolved_maint_board_solve'], '</h3>
		</div>
		<div class="information">', $txt['TopicSolved_maint_board_solve_desc'] , '</div>
		<div class="windowbg">
			<form action="', $scripturl, '?action=admin;area=maintain;sa=topics;activity=solveboard" method="post" accept-charset="', $context['character_set'], '">
				<p>
					<label for="id_board_solve">', $txt['move_topics_from'], ' </label>
					<select name="id_board_solve" id="id_board_solve">
						<option disabled>(', $txt['move_topics_select_board'], ')</option>';

	// From board
	foreach ($context['categories'] as $category) {
		echo '
						<optgroup label="', $category['name'], '">';

		foreach ($category['boards'] as $board)
			echo '
							<option value="', $board['id'], '"> ', str_repeat('==', $board['child_level']), '=&gt;&nbsp;', $board['name'], '</option>';

		echo '
						</optgroup>';
	}

	echo '
					</select>
				</p>
				<p>
					<label for="solved_status">' . $txt['TopicSolved_maint_solve_status'] . '</label>
					<select id="solved_status" name="solved_status">
						<option value="0">' . $txt['TopicSolved_auto_solved'] . '</option>
						<option value="1">' . $txt['TopicSolved_auto_notsolved'] . '</option>
					</select>
				</p>
				<input type="submit" value="', $txt['TopicSolved_maint_board_solve_save'], '" data-confirm="', $txt['TopicSolved_maint_board_solve_sure'], '" class="button you_sure">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<input type="hidden" name="', $context['admin-maint_token_var'], '" value="', $context['admin-maint_token'], '">
			</form>
		</div>';
}