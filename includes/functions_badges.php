<?php
/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);

/**
* Get badge image
*
* @param string $url The path to the image file for the badge
*
* @return string badge image tag
*/
function get_badge_img($url, $bid = 0, $alt = '')
{
	global $user, $config, $phpbb_root_path, $phpEx;

	// make this configurable later?
	$badge_width = $config['badges_img_width'];
	$badge_height = $config['badges_img_height'];
	$badge_dir = $config['badges_img_path'];

	if (empty($url))
	{
		return '';
	}

	$badge_img = '';

	$badge_img = $phpbb_root_path . $badge_dir . $url;

	return '<img src="' . (str_replace(' ', '%20', $badge_img)) . '" width="' . $badge_width . '" height="' . $badge_height . '" data-id="'.$bid.'" alt="'.$alt.'" title="'.$alt.'"/>';
}

/*
 * Return the html to display the badge bar in a topic, for a user.
 */
function get_user_displayed_badges($user_id) {
	global $db, $phpbb_root_path;
	
	$badges_html = '';

	if(!$user_id) {
		return '';
	}
	
	$sql = "SELECT d.url, e.badge_id, d.name 
			FROM badges_earned as e, badges_def as d
			WHERE e.badge_id = d.id AND e.user_id = $user_id AND e.display > 0
			ORDER BY e.display";
	$result = $db->sql_query($sql);
	
	while ($row = $db->sql_fetchrow($result)) {
		$badges_html = $badges_html . " " .  get_badge_img($row['url'],$row['badge_id'], $row['name']);
	}

	$db->sql_freeresult($result);

	return $badges_html;
}

/**
* Add or edit a badge. 
*/
function badge_create(&$badge_id, $name, $url, $desc, $allow_desc_bbcode = false, $allow_desc_urls = false, $allow_desc_smilies = false)
{
	global $phpbb_root_path, $config, $db;

	$error = array();

	// Check data. Limit badge name length.
	if (!utf8_strlen($name) || utf8_strlen($name) > 60)
	{
		$error[] = (!utf8_strlen($name)) ? $user->lang['BADGE_ERR_USERNAME'] : $user->lang['BADGE_ERR_USER_LONG'];
	}

	$err = badge_validate_name($badge_id, $name);
	if (!empty($err))
	{
		$error[] = $user->lang[$err];
	}

	if (!sizeof($error))
	{
		$user_ary = array();
		$sql_ary = array(
			'name'					=> (string) $name,
			'description'			=> (string) $desc,
			'desc_uid'				=> '',
			'desc_bitfield'			=> '',
			'url'					=> (string) $url,
		);

		// Parse description
		if ($desc)
		{
			generate_text_for_storage($sql_ary['description'], $sql_ary['desc_uid'], $sql_ary['desc_bitfield'], $sql_ary['desc_options'], $allow_desc_bbcode, $allow_desc_urls, $allow_desc_smilies);
		}

		// Setting the log message before we set the badge id (if badge gets added)
		$log = ($badge_id) ? 'LOG_BADGE_UPDATED' : 'LOG_BADGE_CREATED';

		$query = '';

		if ($badge_id)
		{
			$sql = 'UPDATE badges_def
				SET ' . $db->sql_build_array('UPDATE', $sql_ary) . "
				WHERE id = $badge_id";
			$db->sql_query($sql);
		}
		else
		{
			$sql = 'INSERT INTO badges_def ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
		}

		if (!$badge_id)
		{
			$badge_id = $db->sql_nextid();
		}

		add_log('admin', $log, $name);
	}

	return (sizeof($error)) ? $error : false;
}

/**
* Validate a badge name. Return false if the name is okay, otherwise return an error.
*/
function badge_validate_name($badge_id, $badge_name)
{
	global $config, $db;

	$badge_name =  utf8_clean_string($badge_name);

	if (!empty($badge_id))
	{
		$sql = 'SELECT name
			FROM badges_def
			WHERE id = ' . (int) $badge_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			return false;
		}

		$allowed_badgename = utf8_clean_string($row['name']);

		if ($allowed_badgename == $badge_name)
		{
			return false;
		}
	}

	$sql = "SELECT name
		FROM badges_def
		WHERE LOWER(name) = '" . $db->sql_escape(utf8_strtolower($badge_name)) . "'";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if ($row)
	{
		return 'BADGE_NAME_TAKEN';
	}

	return false;
}

/**
* Badge Delete
*/
function badge_delete($badge_id, $badge_name = false)
{
	global $db, $phpbb_root_path, $phpEx;

	if (!$badge_name) {
		$badge_name = get_badge_name($badge_id);
	}

	// Delete badge from list of earned badges
	$sql = "DELETE FROM badges_earned 
		WHERE badge_id = $badge_id";
	$db->sql_query($sql);

	// Delete the badge
	$sql = "DELETE FROM badges_def
		WHERE id = $badge_id";
	$db->sql_query($sql);

	add_log('admin', 'LOG_BADGE_DELETE', $badge_name);

	// Return false - no error
	return false;
}

/**
* Get badge name
*/
function get_badge_name($badge_id)
{
	global $db, $user;

	$sql = "SELECT name
		FROM badges_def
		WHERE id = $badge_id";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!$row){
		return '';
	}

	return $row['name'];
}

/**
* Add user(s) to a badge
*
* @return mixed false if no errors occurred, else the user lang string for the relevant error, for example 'NO_USER'
*/

function badge_user_add($badge_id, $user_id_ary = false, $username_ary = false, $badge_name = false)
{
	global $db, $auth;

	// We need both username and user_id info
	$result = user_get_id_name($user_id_ary, $username_ary);

	if (!sizeof($user_id_ary) || $result !== false)
	{
		return 'NO_USER';
	}

	// Remove users who are already members of this
	$sql = 'SELECT user_id
		FROM badges_earned
		WHERE ' . $db->sql_in_set('user_id', $user_id_ary) . "
			AND badge_id = $badge_id";
	$result = $db->sql_query($sql);

	$add_id_ary = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$add_id_ary[] = (int) $row['user_id'];
	}
	$db->sql_freeresult($result);

	// Do all the users exist in this?
	$add_id_ary = array_diff($user_id_ary, $add_id_ary);

	// If we have no users
	if (!sizeof($add_id_ary))
	{
		return 'BADGE_USERS_EXIST';
	}

	$db->sql_transaction('begin');

	// Insert the new users
	if (sizeof($add_id_ary))
	{
		$sql_ary = array();

		foreach ($add_id_ary as $user_id)
		{
			$sql_ary[] = array(
				'user_id'		=> (int) $user_id,
				'badge_id'		=> (int) $badge_id,
				'date_earned'	=> time(),
			);
		}

		$db->sql_multi_insert('badges_earned', $sql_ary);
	}


	$db->sql_transaction('commit');

	if (!$badge_name)
	{
		$badge_name = get_badge_name($badge_id);
	}

	$log = 'LOG_BADGE_USERS_ADDED';

	add_log('admin', $log, $badge_name, implode(', ', $username_ary));

	// Return false - no error
	return false;
}

/**
* Remove a user/s from a given badge. 
*
* @return false if no errors occurred, else the user lang string for the relevant error, for example 'NO_USER'
*/

function badge_user_del($badge_id, $user_id_ary = false, $username_ary = false, $badge_name = false)
{
	global $db, $auth, $config;

	// We need both username and user_id info
	$result = user_get_id_name($user_id_ary, $username_ary);

	if (!sizeof($user_id_ary) || $result !== false)
	{
		return 'NO_USER';
	}

	$sql = "DELETE FROM badges_earned
		WHERE badge_id = $badge_id
			AND " . $db->sql_in_set('user_id', $user_id_ary);
	$db->sql_query($sql);

	if (!$badge_name)
	{
		$badge_name = get_badge_name($badge_id);
	}

	$log = 'LOG_BADGE_REMOVE';

	if ($badge_name)
	{
		add_log('admin', $log, $badge_name, implode(', ', $username_ary));
	}

	// Return false - no error
	return false;
}

function user_badges_del($user_id, $badge_id_ary = false, $badge_name_ary = false, $user_name = false)
{
	global $db, $auth, $config;

	if($user_id == 0) {
		return 'NO_USER';
	}

	if(!$badge_id_ary) {
		return 'NO_BADGE';
	}
	
	if($user_name == false) {
		$sql = 'SELECT username 
				FROM ' . USERS_TABLE . "
				WHERE user_id = $user_id";
		$result = $db->sql_query($sql);
		$user_name = $db->sql_fetchfield('username');
		$db->sql_freeresult($result);
	}
	
	if(!$user_name) {
		return 'NO_USER';
	}

	if(!$badge_name_ary) {
		$sql = "SELECT name
				FROM badges_def
				WHERE " . $db->sql_in_set('id', $badge_id_ary);
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result)) {
			$badge_name_ary[] = $row['name'];
		}
	}
	$db->sql_freeresult($result);
	
	$sql = "DELETE FROM badges_earned
			WHERE user_id = $user_id  
			AND " . $db->sql_in_set('badge_id', $badge_id_ary);
	$db->sql_query($sql);
	
	add_log('admin', 'LOG_BADGES_REMOVE', $user_name, implode(', ', $badge_name_ary));
	
	// return false - no error
	return false;
}

function user_badges_add($user_id, $badge_id_ary = false, $user_name = false)
{
	global $db, $auth;

	if ($user_id == 0)
	{
		return 'NO_USER';
	}

	if(!$badge_id_ary) {
		return 'NO_BADGE';
	}
	
	if($user_name == false) {
		$sql = 'SELECT username 
				FROM ' . USERS_TABLE . "
				WHERE user_id = $user_id";
		$result = $db->sql_query($sql);
		$user_name = $db->sql_fetchfield('username');
		$db->sql_freeresult($result);
	}
	
	if(!$user_name) {
		return 'NO_USER';
	}

	$badge_name_ary = array();
	foreach($badge_id_ary as $bid) {
		$bn = get_badge_name($bid);
		if($bn != '') {
			$badge_id_add[] = $bid;
			$badge_name_ary[] = $bn;
		}
	}

	$db->sql_transaction('begin');

	// Insert the new badges
	if (sizeof($badge_id_add))
	{
		$sql_ary = array();

		foreach ($badge_id_add as $badge_id)
		{
			$sql_ary[] = array(
				'user_id'		=> (int) $user_id,
				'badge_id'		=> (int) $badge_id,
				'date_earned'	=> time(),
			);
		}

		$db->sql_multi_insert('badges_earned', $sql_ary);
	}


	$db->sql_transaction('commit');

	$log = 'LOG_BADGES_USER_ADDED';

	add_log('admin', $log, $user_name, implode(', ', $badge_name_ary));

	// Return false - no error
	return false;
}

/**
* Add or edit a title. 
*/
function title_create(&$title_id, $name, $desc, $allow_desc_bbcode = false, $allow_desc_urls = false, $allow_desc_smilies = false)
{
	global $phpbb_root_path, $config, $db;

	$error = array();

	// Check data. Limit badge name length.
	if (!utf8_strlen($name) || utf8_strlen($name) > 60)
	{
		$error[] = (!utf8_strlen($name)) ? $user->lang['TITLE_ERR_USERNAME'] : $user->lang['TITLE_ERR_USER_LONG'];
	}

	$err = title_validate_name($title_id, $name);
	if (!empty($err))
	{
		$error[] = $user->lang[$err];
	}

	if (!sizeof($error))
	{
		$user_ary = array();
		$sql_ary = array(
			'title'					=> (string) $name,
			'description'			=> (string) $desc,
			'desc_uid'				=> '',
			'desc_bitfield'			=> '',
		);

		// Parse description
		if ($desc)
		{
			generate_text_for_storage($sql_ary['description'], $sql_ary['desc_uid'], $sql_ary['desc_bitfield'], $sql_ary['desc_options'], $allow_desc_bbcode, $allow_desc_urls, $allow_desc_smilies);
		}

		// Setting the log message before we set the badge id (if badge gets added)
		$log = ($title_id) ? 'LOG_TITLE_UPDATED' : 'LOG_TITLE_CREATED';

		$query = '';

		if ($title_id)
		{
			$sql = 'UPDATE titles_def
				SET ' . $db->sql_build_array('UPDATE', $sql_ary) . "
				WHERE id = $title_id";
			$db->sql_query($sql);
		}
		else
		{
			$sql = 'INSERT INTO titles_def ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
		}

		if (!$title_id)
		{
			$title_id = $db->sql_nextid();
		}

		add_log('admin', $log, $name);
	}

	return (sizeof($error)) ? $error : false;
}

/**
* Validate a title name. Return false if the name is okay, otherwise return an error.
*/
function title_validate_name($title_id, $title_name)
{
	global $config, $db;

	$title_name =  utf8_clean_string($title_name);

	if (!empty($title_id))
	{
		$sql = 'SELECT title
			FROM titles_def
			WHERE id = ' . (int) $title_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			return false;
		}

		$allowed_title = utf8_clean_string($row['title']);

		if ($allowed_title == $title_name)
		{
			return false;
		}
	}

	$sql = "SELECT title
		FROM titles_def
		WHERE LOWER(title) = '" . $db->sql_escape(utf8_strtolower($title_name)) . "'";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if ($row)
	{
		return 'TITLE_NAME_TAKEN';
	}

	return false;
}

/**
* Add user(s) to a title
*
* @return mixed false if no errors occurred, else the user lang string for the relevant error, for example 'NO_USER'
*/

function title_user_add($title_id, $user_id_ary = false, $username_ary = false, $title_name = false)
{
	global $db, $auth;

	// We need both username and user_id info
	$result = user_get_id_name($user_id_ary, $username_ary);

	if (!sizeof($user_id_ary) || $result !== false)
	{
		return 'NO_USER';
	}

	// Remove users who are already members of this
	$sql = 'SELECT user_id
		FROM titles_earned
		WHERE ' . $db->sql_in_set('user_id', $user_id_ary) . "
			AND title_id = $title_id";
	$result = $db->sql_query($sql);

	$add_id_ary = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$add_id_ary[] = (int) $row['user_id'];
	}
	$db->sql_freeresult($result);

	// Do all the users exist in this?
	$add_id_ary = array_diff($user_id_ary, $add_id_ary);

	// If we have no users
	if (!sizeof($add_id_ary))
	{
		return 'TITLE_USERS_EXIST';
	}

	$db->sql_transaction('begin');

	// Insert the new users
	if (sizeof($add_id_ary))
	{
		$sql_ary = array();

		foreach ($add_id_ary as $user_id)
		{
			$sql_ary[] = array(
				'user_id'		=> (int) $user_id,
				'title_id'		=> (int) $title_id,
				'date_earned'	=> time(),
			);
		}

		$db->sql_multi_insert('titles_earned', $sql_ary);
	}


	$db->sql_transaction('commit');

	if (!$title_name)
	{
		$title_name = get_badge_name($badge_id);
	}

	$log = 'LOG_TITLE_USERS_ADDED';

	add_log('admin', $log, $title_name, implode(', ', $username_ary));

	// Return false - no error
	return false;
}

/**
* Get title name
*/
function get_title_name($title_id)
{
	global $db, $user;

	$sql = "SELECT title
		FROM titles_def
		WHERE id = $title_id";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!$row){
		return '';
	}

	return $row['title'];
}

/**
* Title Delete
*/
function title_delete($title_id, $title_name = false)
{
	global $db, $phpbb_root_path, $phpEx;

	if (!$title_name) {
		$title_name = get_title_name($title_id);
	}

	// First remove the title from anyone who's earned it.
	$sql = "DELETE FROM titles_earned 
		WHERE title_id = $title_id";
	$db->sql_query($sql);

	// Delete the title definition
	$sql = "DELETE FROM titles_def
		WHERE id = $title_id";
	$db->sql_query($sql);

	add_log('admin', 'LOG_TITLE_DELETE', $title_name);

	// Return false - no error
	return false;
}

/**
* Remove user/s from a given title. 
*
* @return false if no errors occurred, else the user lang string for the relevant error, for example 'NO_USER'
*/

function title_user_del($title_id, $user_id_ary = false, $username_ary = false, $title_name = false)
{
	global $db, $auth, $config;

	// We need both username and user_id info
	$result = user_get_id_name($user_id_ary, $username_ary);

	if (!sizeof($user_id_ary) || $result !== false)
	{
		return 'NO_USER';
	}

	$sql = "DELETE FROM titles_earned
		WHERE title_id = $title_id
			AND " . $db->sql_in_set('user_id', $user_id_ary);
	$db->sql_query($sql);

	if (!$title_name)
	{
		$title_name = get_title_name($title_id);
	}

	$log = 'LOG_TITLE_REMOVE';

	if ($title_name)
	{
		add_log('admin', $log, $title_name, implode(', ', $username_ary));
	}

	// Return false - no error
	return false;
}

function user_titles_add($user_id, $title_id_ary = false, $user_name = false)
{
	global $db, $auth;

	if ($user_id == 0)
	{
		return 'NO_USER';
	}

	if(!$title_id_ary) {
		return 'NO_TITLE';
	}
	
	if($user_name == false) {
		$sql = 'SELECT username 
				FROM ' . USERS_TABLE . "
				WHERE user_id = $user_id";
		$result = $db->sql_query($sql);
		$user_name = $db->sql_fetchfield('username');
		$db->sql_freeresult($result);
	}
	
	if(!$user_name) {
		return 'NO_USER';
	}

	$title_name_ary = array();
	foreach($title_id_ary as $tid) {
		$tn = get_title_name($tid);
		if($tn != '') {
			$title_id_add[] = $tid;
			$title_name_ary[] = $tn;
		}
	}

	$db->sql_transaction('begin');

	// Insert the new titles
	if (sizeof($title_id_add))
	{
		$sql_ary = array();

		foreach ($title_id_add as $title_id)
		{
			$sql_ary[] = array(
				'user_id'		=> (int) $user_id,
				'title_id'		=> (int) $title_id,
				'date_earned'	=> time(),
			);
		}

		$db->sql_multi_insert('titles_earned', $sql_ary);
	}


	$db->sql_transaction('commit');

	$log = 'LOG_TITLES_USER_ADDED';

	add_log('admin', $log, $user_name, implode(', ', $title_name_ary));

	// Return false - no error
	return false;
}

function user_titles_del($user_id, $title_id_ary = false, $title_name_ary = false, $user_name = false)
{
	global $db, $auth, $config;

	if($user_id == 0) {
		return 'NO_USER';
	}

	if(!$title_id_ary) {
		return 'NO_TITLE';
	}
	
	if($user_name == false) {
		$sql = 'SELECT username 
				FROM ' . USERS_TABLE . "
				WHERE user_id = $user_id";
		$result = $db->sql_query($sql);
		$user_name = $db->sql_fetchfield('username');
		$db->sql_freeresult($result);
	}
	
	if(!$user_name) {
		return 'NO_USER';
	}

	if(!$title_name_ary) {
		$sql = "SELECT title
				FROM titles_def
				WHERE " . $db->sql_in_set('id', $title_id_ary);
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result)) {
			$title_name_ary[] = $row['title'];
		}
	}
	$db->sql_freeresult($result);
	
	$sql = "DELETE FROM titles_earned
			WHERE user_id = $user_id  
			AND " . $db->sql_in_set('title_id', $title_id_ary);
	$db->sql_query($sql);
	
	add_log('admin', 'LOG_TITLES_REMOVE', $user_name, implode(', ', $title_name_ary));
	
	// return false - no error
	return false;
}

function get_user_displayed_title($user_id) {
	global $db, $phpbb_root_path;
	
	$title_html = '';

	if(!$user_id) {
		return '';
	}
	
	$sql = "SELECT d.title, e.title_id 
			FROM titles_earned as e, titles_def as d
			WHERE e.title_id = d.id AND e.user_id = $user_id AND e.display = 1";
	$result = $db->sql_query($sql);
	
	// should only be 1 row
	while ($row = $db->sql_fetchrow($result)) {
		$title_html = $title_html . " " .  $row['title'];
	}

	$db->sql_freeresult($result);

	return $title_html;
}

?>
