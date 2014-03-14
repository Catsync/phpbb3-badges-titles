<?php
/**
*
* @package ucp
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* 
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* ucp_badges
* @package ucp
*/
class ucp_badges
{
	var $u_action;

	function main($id, $mode)
	{
		global $config, $db, $user, $auth, $template, $phpbb_root_path, $phpEx;
		
		$user->add_lang('mods/badges');
		include($phpbb_root_path . 'includes/functions_badges.' . $phpEx);
		
		$form_key = 'ucp_badges';
		add_form_key($form_key);
		
		$submit	= (isset($_POST['submit'])) ? true : false;
		$return_page = '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $this->u_action . '">', '</a>');
		$l_mode = strtoupper($mode);
		
		switch($mode) {
			case 'badges' :
				if ($submit)
				{
					$data = $error = array();
					$updated = false;
					$badge_ary = request_var('worn',array(0));
					$change_ary = array(0);
					$pos = 1;
					
					if (!check_form_key($form_key))
					{
						trigger_error($user->lang['FORM_INVALID'] . $return_page, E_USER_WARNING);
					}
					foreach($badge_ary as $k => $v) {
						$change_ary[$v] = $pos++;
						//$status .= "form: ". $v . " => ". $change_ary[$v] . "({$k})<br>";
					}
					// get previously worn badges for comparison
					$sql = 'SELECT badge_id, user_id, display
							FROM badges_earned
							WHERE user_id = ' . $user->data['user_id'] . ' AND display != 0';
					$result = $db->sql_query($sql);
					while($row = $db->sql_fetchrow($result)) {
						// if the old badge is not already in the new list, we need to set its display position to 0
						if(!array_key_exists($row['badge_id'],$change_ary)) {
							$change_ary[$row['badge_id']] = 0;
							//$status .= "db: ". $row['badge_id'] ." => " .$change_ary[$row['badge_id']] ."<br>";
						}
					}
					
					foreach($change_ary as $k => $v) {
						if($k == 0) continue; // probably should find where the 0 sneaks in :p
						$sql_ary = array("display" => $v);
						$sql = "UPDATE badges_earned 
								SET " . $db->sql_build_array('UPDATE', $sql_ary) ."
								WHERE user_id = " . $user->data['user_id'] . " AND badge_id = ".$k ;
						$db->sql_query($sql);
						
					}
					$status .= "Saved.";
				}
				$sql = 'SELECT * 
						FROM badges_earned
						WHERE user_id = '. $user->data['user_id'] .'
						ORDER BY DATE_EARNED';
				$result = $db->sql_query($sql);
		
				$earned = array();
				while($row = $db->sql_fetchrow($result)) {
					$earned[$row['badge_id']] = array('date_earned' => $row['date_earned'],
													  'display' => $row['display']);
				}
				$db->sql_freeresult($result);
		
				
				
				$s_worn_options = '';
				$s_badge_options = '';
				$preview = '';
				if(count($earned) > 0) {
					$has_badges = true;
					
					$sql = 'SELECT *
						FROM badges_def
						WHERE ' . $db->sql_in_set('id', array_keys($earned));
					$result = $db->sql_query($sql);
		
					while($row = $db->sql_fetchrow($result)) {
						$def[$row['id']] = array('name' => $row['name'],
											 'description' => $row['description'],
											 'desc_uid' => $row['desc_uid'], 'desc_bitfield' => $row['desc_bitfield'], 'desc_options' => $row['desc_options'],
											 'url' => $row['url']);
					}
					$db->sql_freeresult($result);
					
					foreach($earned as $k => $v) 
					{
						if($earned[$k]['display'] > 0) {
							$worn[$earned[$k]['display']] = $k;	
						} else {
							$s_badge_options .= '<option value="' . $k . '">' . $def[$k]['name'] . '</option>';
						}
						//$badge_desc_data = generate_text_for_edit($def[$k]['description'], $def[$k]['desc_uid'], $def[$k]['desc_options']);
						$template->assign_block_vars('badgelist',array(
							'BADGENAME'		=> $def[$k]['name'],
							'IMAGE'			=> get_badge_img($def[$k]['url'],$k,$def[$k]['name']),
							'DISPLAYED'		=> ($earned[$k]['display'] > 0 ? "Yes" : "No"),
							'DATE_EARNED'	=> $user->format_date($earned[$k]['date_earned']),
							'DESC'			=> $def[$k]['description'],
						));
					}
					if(count($worn) > 0) {
						ksort($worn);
						foreach($worn as $k => $v) {
							$s_worn_options .= '<option value="'. $v .'">' . $def[$v]['name'] . '</option>';
						}
		
						$preview = get_user_displayed_badges($user->data['user_id']);
					}
					
					// the select boxes don't render correctly when empty, so:
					if($s_badge_options == '') {
						$s_badge_options = '<option value="merylplaceholder" disabled="disabled">none</option>';
					}
					if($s_worn_options == '') {
						$s_worn_options = '<option value="merylplaceholder" disabled="disabled">none</option>';
					}
				} else {
					$has_badges = false;
					// this is just to get rid of the nonsensical instructions when there are no badges yet
					$status = ' ';
				}
				$template->assign_vars(array(
					'HAS_BADGES'		=> $has_badges,
					'S_BADGES_OPTIONS'	=> $s_badge_options,
					'S_WORN_OPTIONS'	=> $s_worn_options,
					'S_MAX_BADGES'		=> $config['badges_max_worn'],
					'L_BADGENAME'		=> $user->lang['BADGE_NAME'],
					'L_BADGE_IMAGE_HEADER'		=> $user->lang['BADGE_IMAGE_HEADER'],
					'L_BADGE_DATE_EARNED'		=> $user->lang['BADGE_DATE_EARNED'],
					'L_BADGE_DESC_HEADER'		=> $user->lang['BADGE_DESC'],
					'PREVIEW'				=> $preview
				));
			break;
			case 'titles' :
				if ($submit)
				{
					if (!check_form_key($form_key))
					{
						trigger_error($user->lang['FORM_INVALID'] . $return_page, E_USER_WARNING);
					}
					$data = $error = array();
					$title_new = request_var('displayed',0);
					$change_ary[$title_new] = 1;
					
					// get previous title
					$sql = 'SELECT title_id, display
							FROM titles_earned
							WHERE user_id = ' . $user->data['user_id'] . ' AND display = 1';
					$result = $db->sql_query($sql);
					while($row = $db->sql_fetchrow($result)) {
						if(!array_key_exists($row['title_id'],$change_ary)) {
							$change_ary[$row['title_id']] = 0;
						}
					}

					foreach($change_ary as $k => $v) {
						if($k == 0) continue;
						$sql_ary = array("display" => $v);
						$sql = "UPDATE titles_earned 
								SET " . $db->sql_build_array('UPDATE', $sql_ary) ."
								WHERE user_id = " . $user->data['user_id'] . " AND title_id = ".$k ;
						$db->sql_query($sql);
					}
				}
				
				$sql = 'SELECT e.title_id, e.display, e.date_earned, d.title, d.description 
						FROM titles_earned e, titles_def d
						WHERE e.user_id = '.$user->data['user_id'].' AND e.title_id = d.id
						ORDER BY e.date_earned';
				$result = $db->sql_query($sql);
				
				$has_titles = $displayed = false;
				$earned = array();
				$s_title_options = '';
				while($row = $db->sql_fetchrow($result)) {
					if($row['display'] == 1) {
						$displayed = true;
					}							
					$s_title_options .= '<option value="' . $row['title_id'] . '"' . (($row['display'] == 1) ? ' selected ' : '') . '>' . $row['title'] . '</option>';
					$template->assign_block_vars('titlelist',array(
						'TITLENAME'		=> $row['title'],
						'DATE_EARNED'	=> $user->format_date($row['date_earned']),
						'DESC'			=> $row['description'],
						'TITLE_ID'		=> $row['title_id'],
					));
					$has_titles = true;
					$earned[$row['title_id']] = $row['title'];
				}
				$db->sql_freeresult($result);

				$s_title_options = '<option value="0"'.($displayed ? '' : ' selected ').'>(none)</option>' . $s_title_options;
		
				$template->assign_vars(array(
					'HAS_TITLES'		=> $has_titles,
					'S_TITLES_OPTIONS'	=> $s_title_options,
					'L_TITLENAME'		=> $user->lang['TITLE_NAME'],
					'L_TITLE_DATE_EARNED'		=> $user->lang['BADGE_DATE_EARNED'],
					'L_TITLE_DESC_HEADER'		=> $user->lang['BADGE_DESC'],
					'L_UCP_NO_TITLES'			=> $user->lang['UCP_NO_TITLES'],
				));
			break;
			default:
        		trigger_error('NO_MODE', E_USER_ERROR);
       		break;
		}
		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang['UCP_BADGES_' . $l_mode],
			'L_SAVE'				=> $user->lang['UCP_SAVE'],
			'ERROR'					=> $status,
			'S_UCP_ACTION'			=> $this->u_action,
		));
		
		$this->tpl_name = 'ucp_badges_' . $mode;
		$this->page_title = 'UCP_BADGES_' . $mode;
	}
}

?>