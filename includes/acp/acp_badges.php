<?php
/**
*
* @package acp
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

// File: includes/acp/acp_badges.php
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package acp
*/
class acp_badges
{
    var $u_action;
                     
    function main($id, $mode)
    {
        global $db, $user, $auth, $template;
        global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
                     
        $user->add_lang('mods/badges');
        $form_key = 'acp_badges';
		add_form_key($form_key);

		//include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
		include($phpbb_root_path . 'includes/functions_badges.' . $phpEx);
		
        // Set up the page
        $this->tpl_name  = 'acp_badges';
        $this->page_title    = 'ACP_BADGES_TITLE';
                             
        // Set up general vars
		$action		= (isset($_POST['add'])) ? 'add' : ((isset($_POST['addusers'])) ? 'addusers' : ((isset($_POST['deleteusers'])) ? 'deleteusers' : ((isset($_POST['deletebadges'])) ? 'deletebadges' : ((isset($_POST['addbadges'])) ? 'addbadges' : request_var('action', '')))));
        $start		= request_var('start', 0);
		$update		= (isset($_POST['update'])) ? true : false;

        // This is for the checkboxes in the list screen
        $mark_ary	= request_var('mark', array(0));
		// This is for the "add users" box from the list screen
        $name_ary	= request_var('usernames', '', true);
        // This is for the "add badges" box in the edit user screen
        $add_badges_ary = request_var('unearned', array(0));
        // Which mode: badges or titles?
        switch($mode) {
        	case 'badges':
        		$title = "Badges";
        		$type = "Badge";
		        $badge_id	= request_var('b', 0);
        		// Grab basic data for badge, if badge_id is set and exists
				if ($badge_id)
				{
					$sql = "SELECT *
						FROM badges_def
						WHERE id = $badge_id";
					$result = $db->sql_query($sql);
					$badge_row = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);

					if (!$badge_row)
					{
						trigger_error($user->lang['NO_BADGE'] . adm_back_link($this->u_action), E_USER_WARNING);
					}
				}
				switch($action) {
					case 'list':
						if (!$badge_id)
						{
							trigger_error($user->lang['NO_BADGE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						$this->page_title = 'BADGE_MEMBERS';
						
						$sql = "SELECT COUNT(user_id) AS total_members
							FROM badges_earned
							WHERE badge_id = $badge_id";
						$result = $db->sql_query($sql);

						$total_members = (int) $db->sql_fetchfield('total_members');
						$db->sql_freeresult($result);
						
						
						$template->assign_vars(array(
							'S_LIST'			=> true,
							'S_ON_PAGE'			=> on_page($total_members, $config['badges_per_page'], $start),
							'PAGINATION'		=> generate_pagination($this->u_action . "&amp;action=$action&amp;b=$badge_id", $total_members, $config['badges_per_page'], $start, true),
							'BADGE_NAME'		=> $badge_row['name'],
							'BADGE_DESC'		=> $badge_row['description'],
							'U_ACTION'			=> $this->u_action . "&amp;b=$badge_id",
							'U_BACK'			=> $this->u_action,
							'U_FIND_USERNAME'	=> append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=searchuser&amp;form=list&amp;field=usernames'),
							'U_DEFAULT_ALL'		=> "{$this->u_action}&amp;action=default&amp;b=$badge_id",
							'L_BADGE_MEMBERS'	=> $user->lang('BADGE_MEMBERS'),
							'L_BADGE_MEMBERS_EXPLAIN'	=> $user->lang('ACP_BADGE_MEMBERS_EXPLAIN'),
						));

						// Grab the members
						$sql = 'SELECT u.user_id, u.username, u.username_clean, u.user_colour, b.date_earned, b.display
							FROM ' . USERS_TABLE . " u, badges_earned b
							WHERE b.badge_id = $badge_id
								AND u.user_id = b.user_id
							ORDER BY u.username_clean";
						$result = $db->sql_query_limit($sql, $config['badges_per_page'], $start);

						while ($row = $db->sql_fetchrow($result))
						{

							$template->assign_block_vars('member', array(
								'U_USER_EDIT'		=> $this->u_action . "&amp;action=edituser&amp;u={$row['user_id']}",
								'USERNAME'			=> $row['username'],
								'USERNAME_COLOUR'	=> $row['user_colour'],
								'DISPLAY'			=> ($row['display'] ? "Yes" : "No"),
								'DATE_EARNED'		=> ($row['date_earned']) ? $user->format_date($row['date_earned']) : ' - ',
								'USER_ID'			=> $row['user_id'])
							);
						}
						$db->sql_freeresult($result);

						//return;
					break;
					case 'edituser':
						$user_id = request_var('u', 0);
						if (!$user_id)
						{
							trigger_error($user->lang['NO_USERS'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						$this->page_title = 'USER_BADGES';
						
						// get user info
						$sql = 'SELECT username, user_colour
								FROM ' . USERS_TABLE . "
								WHERE user_id = $user_id";
						$result = $db->sql_query($sql);
						$user_name = $db->sql_fetchfield('username');
						$user_colour = $db->sql_fetchfield('user_colour');
						$db->sql_freeresult($result);
						
						// Total number of badges earned
						$sql = "SELECT COUNT(badge_id) AS total_badges
							FROM badges_earned
							WHERE user_id = $user_id";
						$result = $db->sql_query($sql);

						$total_badges = (int) $db->sql_fetchfield('total_badges');
						$db->sql_freeresult($result);
						
						if ($update)
						{
							if (!check_form_key($form_key))
							{
								trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							$data = $error = array();
							$badge_ary = request_var('worn',array(0));
							$change_ary = array(0);
							$pos = 1;
							foreach($badge_ary as $k => $v) {
								$change_ary[$v] = $pos++;
							}
							// get previously worn badges for comparison
							$sql = 'SELECT badge_id, user_id, display
									FROM badges_earned
									WHERE user_id = ' . $user_id . ' AND display != 0';
							$result = $db->sql_query($sql);
							while($row = $db->sql_fetchrow($result)) {
								// if the old badge is not already in the new list, we need to set its display position to 0
								if(!array_key_exists($row['badge_id'],$change_ary)) {
									$change_ary[$row['badge_id']] = 0;
								}
							}
			
							foreach($change_ary as $k => $v) {
								if($k == 0) continue; // probably should find where the 0 sneaks in :p
								$sql_ary = array("display" => $v);
								$sql = "UPDATE badges_earned 
										SET " . $db->sql_build_array('UPDATE', $sql_ary) ."
										WHERE user_id = " . $user_id . " AND badge_id = ".$k ;
								$db->sql_query($sql);
								
							}
							$status .= "Saved.";
						}
						$sql = "SELECT * 
								FROM badges_earned
								WHERE user_id = $user_id
								ORDER BY DATE_EARNED";
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
									'IMAGE'			=> get_badge_img($def[$k]['url'],$k),
									'DISPLAYED'		=> ($earned[$k]['display'] > 0 ? "Yes" : "No"),
									'DATE_EARNED'	=> $user->format_date($earned[$k]['date_earned']),
									'DESC'			=> $def[$k]['description'],
									'BADGE_ID'		=> $k,
									'U_BADGE_EDIT'	=> $this->u_action . "&amp;action=edit&amp;b=$k",
								));
							}
							if(count($worn) > 0) {
								ksort($worn);
								foreach($worn as $k => $v) {
									$s_worn_options .= '<option value="'. $v .'">' . $def[$v]['name'] . '</option>';
								}
				
								$preview = get_user_displayed_badges($user_id);
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

						$sql = "SELECT id, name
								FROM badges_def";
						$result = $db->sql_query($sql);

						$s_unearned_options = '';
						while($row = $db->sql_fetchrow($result)) {
							if(!array_key_exists($row['id'],$earned)) {
								$s_unearned_options .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
							}
						}
						$db->sql_freeresult($result);
						
						$template->assign_vars(array(
							'S_EDIT_USER'		=> true,
							'U_USER_EDIT'		=> append_sid("{$phpbb_admin_path}index.$phpEx", "i=users&amp;action=edit&amp;u={$user_id}"),
							'USER_NAME'			=> $user_name,
							'USER_COLOUR'		=> $user_colour,
							'TOTAL_BADGES'		=> $total_badges,
							'L_TITLE'			=> $user->lang['UCP_BADGES_' . $l_mode],
							'HAS_BADGES'		=> $has_badges,
							'S_BADGES_OPTIONS'	=> $s_badge_options,
							'S_WORN_OPTIONS'	=> $s_worn_options,
							'S_UNEARNED_OPTIONS' => $s_unearned_options,
							'L_BADGENAME'		=> $user->lang['BADGE_NAME'],
							'L_BADGE_IMAGE_HEADER'		=> $user->lang['BADGE_IMAGE_HEADER'],
							'L_BADGE_DATE_EARNED'		=> $user->lang['BADGE_DATE_EARNED'],
							'L_BADGE_DESC_HEADER'		=> $user->lang['BADGE_DESC'],
							'L_SAVE'				=> $user->lang['UCP_SAVE'],
							'L_BADGE_ADD'			=> $user->lang['BADGE_ADD'],
							'ERROR'					=> $status,
							'U_BACK'			=> $this->u_action,
							'PREVIEW'				=> $preview
						));

					break;
					case 'addusers':
						if (!check_form_key($form_key))
						{
							trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						if (!$badge_id)
						{
							trigger_error($user->lang['NO_BADGE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						if (!$name_ary)
						{
							trigger_error($user->lang['NO_USERS'] . adm_back_link($this->u_action . '&amp;action=list&amp;b=' . $badge_id), E_USER_WARNING);
						}
						if (!$auth->acl_get('a_badges_assign'))
						{
							trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						
						$name_ary = array_unique(explode("\n", $name_ary));
						$badge_name = $badge_row['name'];
		
						// Add user/s to badge
						if ($error = badge_user_add($badge_id, false, $name_ary, $badge_name))
						{
							trigger_error($user->lang[$error] . adm_back_link($this->u_action . '&amp;action=list&amp;b=' . $badge_id), E_USER_WARNING);
						}
		
						$message = 'BADGE_USERS_ADDED';
						trigger_error($user->lang[$message] . adm_back_link($this->u_action . '&amp;action=list&amp;b=' . $badge_id));
					break;
					case 'edit':
					case 'add':
						if ($action == 'edit' && !$badge_id)
						{
							trigger_error($user->lang['NO_BADGE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						if (!$auth->acl_get('a_badges_create'))
						{
							trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						$template->assign_vars(array(
						        	'U_BACK'			=> $this->u_action
						));
						// Did we submit the edit form?
						if ($update)
						{
							if (!check_form_key($form_key))
							{
								trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							
							$badge_name	= utf8_normalize_nfc(request_var('badge_name', '', true));
							$badge_desc = utf8_normalize_nfc(request_var('badge_desc', '', true));
							$allow_desc_bbcode	= request_var('desc_parse_bbcode', false);
							$allow_desc_urls	= request_var('desc_parse_urls', false);
							$allow_desc_smilies	= request_var('desc_parse_smilies', false);
							$badge_url = utf8_normalize_nfc(request_var('badge_url', '', true));
							
							if (!($error = badge_create($badge_id, $badge_name, $badge_url, $badge_desc, $allow_desc_bbcode, $allow_desc_urls, $allow_desc_smilies)))
							{

								$message = ($action == 'edit') ? 'BADGE_UPDATED' : 'BADGE_CREATED';
								trigger_error($user->lang[$message] . adm_back_link($this->u_action."&amp;action=edit&amp;b=$badge_id"));
							}
							
							if (sizeof($error))
							{
								$badge_desc_data = array(
									'text'			=> $badge_desc,
									'allow_bbcode'	=> $allow_desc_bbcode,
									'allow_smilies'	=> $allow_desc_smilies,
									'allow_urls'	=> $allow_desc_urls
								);
							}
							
							// end: did we submit the edit form?
						} else if (!$badge_id) {
							// create some default data
							$badge_name = utf8_normalize_nfc(request_var('badge_name', '', true));
							$badge_desc_data = array(
								'text'			=> '',
								'allow_bbcode'	=> true,
								'allow_smilies'	=> true,
								'allow_urls'	=> true
							);
							$badge_url = '';
						}
						else
						{
							// grab info from the sql results
							$badge_name = $badge_row['name'];
							$badge_desc_data = generate_text_for_edit($badge_row['description'], $badge_row['desc_uid'], $badge_row['desc_options']);
							$badge_url = $badge_row['url'];
						}
						$badge_img = (!empty($badge_url)) ? get_badge_img($badge_url) : '';
						$template->assign_vars(array(
							'S_TITLE'           => ($action == 'add') ? $user->lang['BADGE_CREATE'] : $user->lang['BADGE_EDIT'],
							'S_EDIT'			=> true,
							'S_ADD_BADGE'		=> ($action == 'add') ? true : false,
						   	'S_EDIT_BADGE'		=> true,
							'S_ERROR'			=> (sizeof($error)) ? true : false,

							'ERROR_MSG'				=> (sizeof($error)) ? implode('<br />', $error) : '',
							'BADGE_NAME'			=> $badge_name,
							'BADGE_DESC'			=> $badge_desc_data['text'],
							'S_DESC_BBCODE_CHECKED'	=> $badge_desc_data['allow_bbcode'],
							'S_DESC_URLS_CHECKED'	=> $badge_desc_data['allow_urls'],
							'S_DESC_SMILIES_CHECKED'=> $badge_desc_data['allow_smilies'],

							'BADGE_URL'				=> $badge_url,
							'BADGE_IMAGE'			=> $badge_img,

							'U_BACK'			=> $this->u_action,
							'U_ACTION'			=> "{$this->u_action}&amp;action=$action&amp;b=$badge_id",
							'L_BADGE_DETAILS'	=> $user->lang['BADGE_DETAILS'],
							'L_BADGE_NAME'		=> $user->lang['BADGE_NAME'],
							'L_BADGE_DESC'		=> $user->lang['BADGE_DESC'],
							'L_BADGE_URL_EXPLAIN' => $user->lang['BADGE_URL_EXPLAIN']. " (do not include the base path '". $config['badges_img_path']. "')",
						));
					break;
					case 'deleteusers':
					case 'delete':
						if (!$badge_id)
						{
							trigger_error($user->lang['NO_BADGE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						if (confirm_box(true))
						{
							$error = '';
							switch($action) {
								case 'delete':
									if (!$auth->acl_get('a_badges_create'))
									{
										trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
									}
									$error = badge_delete($badge_id, $badge_row['name']);
								break;
								case 'deleteusers':
									if (!$auth->acl_get('a_badges_assign'))
									{
										trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
									}
									$badge_name = $badge_row['name'];
									$error = badge_user_del($badge_id, $mark_ary, false, $badge_name);
								break;

							}
							
							$back_link = $this->u_action;
							if ($error)
							{
								trigger_error($user->lang[$error] . adm_back_link($back_link), E_USER_WARNING);
							}

							$message = ($action == 'delete') ? 'BADGE_DELETED' : 'BADGE_USERS_REMOVED';
							trigger_error($user->lang[$message] . adm_back_link($back_link));
						} 
						else
						{
							confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
								'mark'		=> $mark_ary,
								'b'			=> $badge_id,
								'mode'		=> $mode,
								'action'	=> $action))
							);
						}
						
					break;
					case 'deletebadges':
						$user_id = request_var('u', 0);
						
						if (!$user_id)
						{
							trigger_error($user->lang['NO_USERS'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						if (confirm_box(true))
						{
							if (!$auth->acl_get('a_badges_assign'))
							{
								trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							
							// do stuff
							$error = user_badges_del($user_id, $mark_ary);
							
							$back_link = $this->u_action . "&amp;action=edituser&amp;u=$user_id";
							if ($error)
							{
								trigger_error($user->lang[$error] . adm_back_link($back_link), E_USER_WARNING);
							}

							trigger_error($user->lang['BADGES_REMOVED'] . adm_back_link($back_link));
						} else {
							if (!check_form_key($form_key))
							{
								trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							confirm_box(false, $user->lang['CONFIRM_DELETE_BADGES'], build_hidden_fields(array(
								'mark'		=> $mark_ary,
								'u'			=> $user_id,
								'mode'		=> $mode,
								'action'	=> $action))
							);
						}
					break;
					case 'addbadges':
						$user_id = request_var('u', 0);
						
						if (!$user_id)
						{
							trigger_error($user->lang['NO_USERS'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						
						if (confirm_box(true))
						{
							if (!$auth->acl_get('a_badges_assign'))
							{
								trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							
							// do stuff
							$error = user_badges_add($user_id, $add_badges_ary);
							
							$back_link = $this->u_action . "&amp;action=edituser&amp;u=$user_id";
							if ($error)
							{
								trigger_error($user->lang[$error] . adm_back_link($back_link), E_USER_WARNING);
							}

							trigger_error($user->lang['BADGES_ADDED'] . adm_back_link($back_link));
						} else {
							if (!check_form_key($form_key))
							{
								trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							confirm_box(false, $user->lang['CONFIRM_ADD_BADGES'], build_hidden_fields(array(
								'unearned'	=> $add_badges_ary,
								'u'			=> $user_id,
								'mode'		=> $mode,
								'action'	=> $action))
							);
						}
					break;
					default:
						$sql = "SELECT COUNT(id) AS total_badges FROM badges_def";
						$result = $db->sql_query($sql);
						$total_badges = (int) $db->sql_fetchfield('total_badges');
						$db->sql_freeresult($result);
						
						$sql = "SELECT * FROM badges_def
						ORDER BY name";
						$result = $db->sql_query_limit($sql, $config['badges_per_page'], $start);

    					$badges_sql_ary = array();
    					while ($row = $db->sql_fetchrow($result))
    					{
    						$badges_sql_ary[] = array(
							'id'			=> (int) $row['id'],
							'name'			=> (string) $row['name'],
							'description'	=> (string) $row['description'],
							'url'			=> (string) $row['url'],
    						);
    					}
    					$db->sql_freeresult($result);
    	
    					foreach ($badges_sql_ary as $v) {
							$sql = "SELECT count(user_id) AS total_members 
									FROM badges_earned WHERE badge_id={$v['id']}";
							$result = $db->sql_query($sql);
							$total_members = (int) $db->sql_fetchfield('total_members');
							$db->sql_freeresult($result);
    						$template->assign_block_vars('badges',array(
    							'B_ID'			=> $v['id'],
    							'B_NAME'		=> $v['name'],
    							'B_TOTAL'		=> $total_members,
    							'U_LIST'		=> "{$this->u_action}&amp;action=list&amp;b={$v['id']}",
								'U_EDIT'		=> "{$this->u_action}&amp;action=edit&amp;b={$v['id']}",
    							'U_DELETE'		=> ($auth->acl_get('a_badges_create')) ? "{$this->u_action}&amp;action=delete&amp;b={$v['id']}" : '',
    						));
    					}
    					$template->assign_vars(array(
							'S_TITLE'           => $title,
    						'NUM_BADGES'		=> $total_badges,
    					));
					break;
				}
       		break;
        	case 'titles':
        		$title = "Titles";
        		$type = "Title";
        		$this->tpl_name  = 'acp_badges_titles';
        		
        		$title_id	= request_var('t', 0);

				if ($title_id)
				{
					$sql = "SELECT *
						FROM titles_def
						WHERE id = $title_id";
					$result = $db->sql_query($sql);
					$title_row = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);

					if (!$title_row)
					{
						trigger_error($user->lang['NO_TITLE'] . adm_back_link($this->u_action), E_USER_WARNING);
					}
				}
        		// TODO title actions here
        		switch($action) {
        			case 'list':
        				if (!$title_id)
						{
							trigger_error($user->lang['NO_TITLE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						$this->page_title = 'TITLE_MEMBERS';
						
						$sql = "SELECT COUNT(user_id) AS total_members
							FROM titles_earned
							WHERE title_id = $title_id";
						$result = $db->sql_query($sql);

						$total_members = (int) $db->sql_fetchfield('total_members');
						$db->sql_freeresult($result);
						
						
						$template->assign_vars(array(
							'S_LIST'			=> true,
							'S_ON_PAGE'			=> on_page($total_members, $config['badges_per_page'], $start),
							'PAGINATION'		=> generate_pagination($this->u_action . "&amp;action=$action&amp;t=$title_id", $total_members, $config['badges_per_page'], $start, true),
							'TITLE_NAME'		=> $title_row['title'],
							'TITLE_DESC'		=> $title_row['description'],
							'U_ACTION'			=> $this->u_action . "&amp;t=$title_id",
							'U_BACK'			=> $this->u_action,
							'U_FIND_USERNAME'	=> append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=searchuser&amp;form=list&amp;field=usernames'),
							'L_TITLE_MEMBERS'	=> $user->lang('TITLE_MEMBERS'),
							'L_TITLE_MEMBERS_EXPLAIN'	=> $user->lang('ACP_TITLE_MEMBERS_EXPLAIN'),
						));

						// Grab the members
						$sql = 'SELECT u.user_id, u.username, u.username_clean, u.user_colour, t.date_earned, t.display
							FROM ' . USERS_TABLE . " u, titles_earned t
							WHERE t.title_id = $title_id
								AND u.user_id = t.user_id
							ORDER BY u.username_clean";
						$result = $db->sql_query_limit($sql, $config['badges_per_page'], $start);

						while ($row = $db->sql_fetchrow($result))
						{

							$template->assign_block_vars('member', array(
								'U_USER_EDIT'		=> $this->u_action . "&amp;action=edituser&amp;u={$row['user_id']}",
								'USERNAME'			=> $row['username'],
								'USERNAME_COLOUR'	=> $row['user_colour'],
								'DISPLAY'			=> ($row['display'] ? "Yes" : "No"),
								'DATE_EARNED'		=> ($row['date_earned']) ? $user->format_date($row['date_earned']) : ' - ',
								'USER_ID'			=> $row['user_id'])
							);
						}
						$db->sql_freeresult($result);

						//return;
        			break;
        			case 'edit':
        			case 'add':

						if ($action == 'edit' && !$title_id)
						{
							trigger_error($user->lang['NO_BADGE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						if (!$auth->acl_get('a_badges_create'))
						{
							trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						$template->assign_vars(array(
						        	'U_BACK'			=> $this->u_action
						));
						// Did we submit the edit form?
						if ($update)
						{
							if (!check_form_key($form_key))
							{
								trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							
							$title_name	= utf8_normalize_nfc(request_var('title_name', '', true));
							$title_desc = utf8_normalize_nfc(request_var('title_desc', '', true));
							$allow_desc_bbcode	= request_var('desc_parse_bbcode', false);
							$allow_desc_urls	= request_var('desc_parse_urls', false);
							$allow_desc_smilies	= request_var('desc_parse_smilies', false);
							
							if (!($error = title_create($title_id, $title_name, $title_desc, $allow_desc_bbcode, $allow_desc_urls, $allow_desc_smilies)))
							{
								$message = ($action == 'edit') ? 'TITLE_UPDATED' : 'TITLE_CREATED';
								trigger_error($user->lang[$message] . adm_back_link($this->u_action."&amp;action=edit&amp;t=$title_id"));
							}
							
							if (sizeof($error))
							{
								$title_desc_data = array(
									'text'			=> $title_desc,
									'allow_bbcode'	=> $allow_desc_bbcode,
									'allow_smilies'	=> $allow_desc_smilies,
									'allow_urls'	=> $allow_desc_urls
								);
							}
							
							// end: did we submit the edit form?
						} else if (!$title_id) {
							// create some default data
							$title_name = utf8_normalize_nfc(request_var('title_name', '', true));
							$title_desc_data = array(
								'text'			=> '',
								'allow_bbcode'	=> true,
								'allow_smilies'	=> true,
								'allow_urls'	=> true
							);
						}
						else
						{
							// grab info from the sql results
							$title_name = $title_row['title'];
							$title_desc_data = generate_text_for_edit($title_row['description'], $title_row['desc_uid'], $title_row['desc_options']);
						}
						$template->assign_vars(array(
							'S_TITLE'           => ($action == 'add') ? $user->lang['TITLE_CREATE'] : $user->lang['TITLE_EDIT'],
							'S_EDIT'			=> true,
							'S_ADD_TITLE'		=> ($action == 'add') ? true : false,
						   	'S_EDIT_TITLE'		=> true,
							'S_ERROR'			=> (sizeof($error)) ? true : false,

							'ERROR_MSG'				=> (sizeof($error)) ? implode('<br />', $error) : '',
							'TITLE_NAME'			=> $title_name,
							'TITLE_DESC'			=> $title_desc_data['text'],
							'S_DESC_BBCODE_CHECKED'	=> $title_desc_data['allow_bbcode'],
							'S_DESC_URLS_CHECKED'	=> $title_desc_data['allow_urls'],
							'S_DESC_SMILIES_CHECKED'=> $title_desc_data['allow_smilies'],

							'U_BACK'			=> $this->u_action,
							'U_ACTION'			=> "{$this->u_action}&amp;action=$action&amp;t=$title_id",
							'L_BADGE_DETAILS'	=> $user->lang['TITLE_DETAILS'],
							'L_BADGE_NAME'		=> $user->lang['TITLE_NAME'],
							'L_BADGE_DESC'		=> $user->lang['TITLE_DESC'],
						));
       				
        			break;
        			case 'addusers':
        				if (!check_form_key($form_key))
						{
							trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						if (!$title_id)
						{
							trigger_error($user->lang['NO_TITLE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						if (!$name_ary)
						{
							trigger_error($user->lang['NO_USERS'] . adm_back_link($this->u_action . '&amp;action=list&amp;t=' . $title_id), E_USER_WARNING);
						}
						if (!$auth->acl_get('a_badges_assign'))
						{
							trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						
						$name_ary = array_unique(explode("\n", $name_ary));
						$title_name = $title_row['title'];
		
						// Add user/s to title
						if ($error = title_user_add($title_id, false, $name_ary, $title_name))
						{
							trigger_error($user->lang[$error] . adm_back_link($this->u_action . '&amp;action=list&amp;b=' . $badge_id), E_USER_WARNING);
						}
		
						$message = 'TITLE_USERS_ADDED';
						trigger_error($user->lang[$message] . adm_back_link($this->u_action . '&amp;action=list&amp;t=' . $title_id));
        			break;
        			case 'deleteusers':
					case 'delete':
						if (!$title_id)
						{
							trigger_error($user->lang['NO_TITLE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						if (confirm_box(true))
						{
							$error = '';
							switch($action) {
								case 'delete':
									if (!$auth->acl_get('a_badges_create'))
									{
										trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
									}
									$error = title_delete($title_id, $title_row['title']);
								break;
								case 'deleteusers':
									if (!$auth->acl_get('a_badges_assign'))
									{
										trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
									}
									$title_name = $title_row['title'];
									$error = title_user_del($title_id, $mark_ary, false, $title_name);
								break;

							}
							
							$back_link = $this->u_action;
							if ($error)
							{
								trigger_error($user->lang[$error] . adm_back_link($back_link), E_USER_WARNING);
							}

							$message = ($action == 'delete') ? 'TITLE_DELETED' : 'TITLE_USERS_REMOVED';
							trigger_error($user->lang[$message] . adm_back_link($back_link));
						} 
						else
						{
							confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
								'mark'		=> $mark_ary,
								'b'			=> $badge_id,
								'mode'		=> $mode,
								'action'	=> $action))
							);
						}
						
					break;
					case 'edituser':
						$user_id = request_var('u', 0);
						if (!$user_id)
						{
							trigger_error($user->lang['NO_USERS'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						$this->page_title = 'USER_TITLES';
						
						// get user info
						$sql = 'SELECT username, user_colour
								FROM ' . USERS_TABLE . "
								WHERE user_id = $user_id";
						$result = $db->sql_query($sql);
						$user_name = $db->sql_fetchfield('username');
						$user_colour = $db->sql_fetchfield('user_colour');
						$db->sql_freeresult($result);
						
						// Total number of badges earned
						$sql = "SELECT COUNT(title_id) AS total_titles
							FROM titles_earned
							WHERE user_id = $user_id";
						$result = $db->sql_query($sql);

						$total_titles = (int) $db->sql_fetchfield('total_titles');
						$db->sql_freeresult($result);
						if ($update)
						{
							if (!check_form_key($form_key))
							{
								trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							$data = $error = array();
							$title_new = request_var('displayed',0);
							$change_ary[$title_new] = 1;
							
							// get previous title
							$sql = 'SELECT title_id, display
									FROM titles_earned
									WHERE user_id = ' . $user_id . ' AND display != 0';
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
										WHERE user_id = " . $user_id . " AND title_id = ".$k ;
								$db->sql_query($sql);
							}
							$status .= "Saved.";
						} // end $update

						$sql = "SELECT e.title_id, e.display, e.date_earned, d.title, d.description 
								FROM titles_earned e, titles_def d
								WHERE e.user_id = $user_id AND e.title_id = d.id
								ORDER BY e.DATE_EARNED";
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

						$sql = "SELECT id, title
								FROM titles_def";
						$result = $db->sql_query($sql);

						$s_unearned_options = '';
						while($row = $db->sql_fetchrow($result)) {
							if(!array_key_exists($row['id'],$earned)) {
								$s_unearned_options .= '<option value="'.$row['id'].'">'.$row['title'].'</option>';
							}
						}
						$db->sql_freeresult($result);
						
						$template->assign_vars(array(
							'S_EDIT_USER'		=> true,
							'U_USER_EDIT'		=> append_sid("{$phpbb_admin_path}index.$phpEx", "i=users&amp;action=edit&amp;u={$user_id}"),
							'USER_NAME'			=> $user_name,
							'USER_COLOUR'		=> $user_colour,
							'TOTAL_TITLES'		=> $total_titles,
							'L_TITLE'			=> $user->lang['UCP_TITLES_' . $l_mode],
							'HAS_TITLES'		=> $has_titles,
							'S_TITLES_OPTIONS'	=> $s_title_options,
							'S_UNEARNED_OPTIONS' => $s_unearned_options,
							'L_TITLENAME'		=> $user->lang['TITLE_NAME'],
							'L_TITLE_DATE_EARNED'		=> $user->lang['BADGE_DATE_EARNED'],
							'L_TITLE_DESC_HEADER'		=> $user->lang['BADGE_DESC'],
							'L_SAVE'				=> $user->lang['UCP_SAVE'],
							'L_TITLE_ADD'			=> $user->lang['TITLE_ADD'],
							'ERROR'					=> $status,
							'U_BACK'			=> $this->u_action,
						));

					break;
					case 'addbadges':
						$user_id = request_var('u', 0);
						
						if (!$user_id)
						{
							trigger_error($user->lang['NO_USERS'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
						
						if (confirm_box(true))
						{
							if (!$auth->acl_get('a_badges_assign'))
							{
								trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							
							// do stuff
							$error = user_titles_add($user_id, $add_badges_ary);
							
							$back_link = $this->u_action . "&amp;action=edituser&amp;u=$user_id";
							if ($error)
							{
								trigger_error($user->lang[$error] . adm_back_link($back_link), E_USER_WARNING);
							}

							trigger_error($user->lang['TITLES_ADDED'] . adm_back_link($back_link));
						} else {
							if (!check_form_key($form_key))
							{
								trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							confirm_box(false, $user->lang['CONFIRM_ADD_TITLES'], build_hidden_fields(array(
								'unearned'	=> $add_badges_ary,
								'u'			=> $user_id,
								'mode'		=> $mode,
								'action'	=> $action))
							);
						}
					break;
					case 'deletebadges':
						$user_id = request_var('u', 0);
						
						if (!$user_id)
						{
							trigger_error($user->lang['NO_USERS'] . adm_back_link($this->u_action), E_USER_WARNING);
						}

						if (confirm_box(true))
						{
							if (!$auth->acl_get('a_badges_assign'))
							{
								trigger_error($user->lang['NO_AUTH_OPERATION'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							
							// do stuff
							$error = user_titles_del($user_id, $mark_ary);
							
							$back_link = $this->u_action . "&amp;action=edituser&amp;u=$user_id";
							if ($error)
							{
								trigger_error($user->lang[$error] . adm_back_link($back_link), E_USER_WARNING);
							}

							trigger_error($user->lang['TITLES_REMOVED'] . adm_back_link($back_link));
						} else {
							if (!check_form_key($form_key))
							{
								trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
							}
							confirm_box(false, $user->lang['CONFIRM_DELETE_TITLES'], build_hidden_fields(array(
								'mark'		=> $mark_ary,
								'u'			=> $user_id,
								'mode'		=> $mode,
								'action'	=> $action))
							);
						}
					break;
        			default:
        				$sql = "SELECT COUNT(id) AS total_titles FROM titles_def";
						$result = $db->sql_query($sql);
						$total_titles = (int) $db->sql_fetchfield('total_titles');
						$db->sql_freeresult($result);
						
						$sql = "SELECT * FROM titles_def
						ORDER BY title";
						$result = $db->sql_query_limit($sql, $config['badges_per_page'], $start);

    					$titles_sql_ary = array();
    					while ($row = $db->sql_fetchrow($result))
    					{
    						$titles_sql_ary[] = array(
							'id'			=> (int) $row['id'],
							'title'			=> (string) $row['title'],
							'description'	=> (string) $row['description'],
    						);
    					}
    					$db->sql_freeresult($result);
    	
    					foreach ($titles_sql_ary as $v) {
							$sql = "SELECT count(user_id) AS total_members 
									FROM titles_earned WHERE title_id={$v['id']}";
							$result = $db->sql_query($sql);
							$total_members = (int) $db->sql_fetchfield('total_members');
							$db->sql_freeresult($result);
    						$template->assign_block_vars('titles',array(
    							'ID'			=> $v['id'],
    							'TITLE'		=> $v['title'],
    							'TOTAL'		=> $total_members,
    							'U_LIST'		=> "{$this->u_action}&amp;action=list&amp;t={$v['id']}",
								'U_EDIT'		=> "{$this->u_action}&amp;action=edit&amp;t={$v['id']}",
    							'U_DELETE'		=> ($auth->acl_get('a_badges_create')) ? "{$this->u_action}&amp;action=delete&amp;t={$v['id']}" : '',
    						));
    					}
    					$template->assign_vars(array(
							'S_TITLE'           => $title,
    						'NUM_TITLES'		=> $total_titles,
    					));
        			break;
        		}
       		break;
        	case 'config':
        		$this->tpl_name  = 'acp_badges_config';
        		$display_vars = array(
					'title'	=> 'ACP_BADGE_CONFIG',
					'vars'	=> array(
       						'legend1'				=> 'BADGE_SETTINGS',
        					'badges_max_worn'		=> array('lang' => 'BADGES_MAX_WORN', 
        													 'validate' => 'int:1',	'type' => 'text:2:3', 'explain' => false),
        					'badges_img_height'		=> array('lang' => 'BADGES_IMG_HEIGHT', 'validate' => 'int:1',
        													 'type' => 'text:2:3', 'explain' => false),
        					'badges_img_width'		=> array('lang' => 'BADGES_IMG_WIDTH', 'validate' => 'int:1',
        													 'type' => 'text:2:3', 'explain' => false),
        					'badges_img_path'		=> array('lang' => 'BADGES_IMG_PATH', 'validate' => 'path',
        													 'type' => 'text:50:150', 'explain' => false),
        		        		
        					'legend2'				=> 'BADGES_GENERAL_SETTINGS',
        					'badges_per_page'		=> array('lang' => 'BADGES_PER_PAGE', 'validate' => 'int:10',
        													 'type' => 'text:2:3', 'explain' => false),
        		
        		
        		));
        		
        		$this->new_config = $config;
				$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
				$error = array();
				
        		// We validate the complete config if whished
				validate_config_vars($display_vars['vars'], $cfg_array, $error);

				$submit		= (isset($_POST['submit'])) ? true : false;
				
				if ($submit && !check_form_key($form_key))
				{
					$error[] = $user->lang['FORM_INVALID'];
				}
        		// Do not write values if there is an error
				if (sizeof($error))
				{
					$submit = false;
				}
				
        		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
				foreach ($display_vars['vars'] as $config_name => $null)
				{
					if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
					{
						continue;
					}
		
					$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];
		
					if ($submit)
					{
						set_config($config_name, $config_value);
					}
				}
        		// Output relevant page
				foreach ($display_vars['vars'] as $config_key => $vars)
				{
					if (!is_array($vars) && strpos($config_key, 'legend') === false)
					{
						continue;
					}
		
					if (strpos($config_key, 'legend') !== false)
					{
						$template->assign_block_vars('options', array(
							'S_LEGEND'		=> true,
							'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
						);
		
						continue;
					}
		
					$type = explode(':', $vars['type']);
		
					$l_explain = '';
					if ($vars['explain'] && isset($vars['lang_explain']))
					{
						$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
					}
					else if ($vars['explain'])
					{
						$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
					}
		
					$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);
		
					if (empty($content))
					{
						continue;
					}
		
					$template->assign_block_vars('options', array(
						'KEY'			=> $config_key,
						'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
						'S_EXPLAIN'		=> $vars['explain'],
						'TITLE_EXPLAIN'	=> $l_explain,
						'CONTENT'		=> $content,
						)
					);
		
					unset($display_vars['vars'][$config_key]);
				}
				$template->assign_vars(array(
					'S_ERROR'			=> (sizeof($error)) ? true : false,
					'ERROR_MSG'			=> implode('<br />', $error),
				));
        		break;
        		
        	default:
        		trigger_error('NO_MODE', E_USER_ERROR);
       		break;
        }

        $permission = ($auth->acl_get('a_badges_create') ? "True" : "False");
        
        $template->assign_vars(array(

        	'S_PERMISSION'		=> $permission,
        	'S_ACTION'			=> $action,
			'S_HEADER_TYPE'		=> $type,
			'S_ON_PAGE'			=> on_page($total_badges, $config['badges_per_page'], $start),
			'PAGINATION'		=> generate_pagination($this->u_action, (($mode == 'badges') ? $total_badges : $total_titles), $config['badges_per_page'], $start, true),
        
	        'S_SUBMIT'          => $this->u_action)
        );


    }
}



?>