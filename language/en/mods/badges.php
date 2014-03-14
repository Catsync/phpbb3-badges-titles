<?php
/**
*
* common [English]
*
* @package language
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// â€™ Â» â€œ â€� â€¦
//

$lang = array_merge($lang, array(
	'ADDED_PERMISSIONS' 	=> 'You have successfully added badge mod permission options to your database.',
	'ACP_BADGES_MANAGE'		=> 'Manage Badges and Titles',
	'ACP_BADGES_TITLE'  	=> 'Badges and Titles',
	'ACP_BADGES_EDIT_EXPLAIN' => 'From this panel, you can administer the badges and titles for the site. You can edit, delete or create new badges or titles.',
	'ACP_BADGE_MEMBERS_EXPLAIN' => 'This is a list of all the people who have earned this badge.',
	'ACP_TITLE_MEMBERS_EXPLAIN' => 'This is a list of all the people who have earned this title.',
	'ACP_BADGE_CONFIG'		=> 'Configuration',
	'BADGE_SETTINGS'		=> 'Badge Settings',
	'BADGES_MAX_WORN'		=> 'Max Worn Badges',
	'BADGES_IMG_HEIGHT'		=> 'Badge Image Height',
	'BADGES_IMG_WIDTH'		=> 'Badge Image Width',
	'BADGES_IMG_PATH'		=> 'Path to Badge Images',
	'TITLES_SETTINGS'		=> 'Title Settings',
	'BADGES_GENERAL_SETTINGS' => 'General Settings',
	'BADGES_PER_PAGE'		=> 'Badges Per Page',
	'BADGES_BADGES'   		=> 'Badges',
	'BADGES_TITLES'   		=> 'Titles',
	'BADGES_BADGE_HEADER'	=> 'Badge',
	'BADGES_TITLE_HEADER'	=> 'Title',
	'MEMBERS'				=> 'Members',
	'NO_BADGE'				=> 'The specified badge does not exist.',
	'NO_TITLE'				=> 'The specified title does not exist.',
	'NO_USERS'				=> 'You haven\'t entered any users.',
	'BADGE_EDIT'			=> 'Edit Badge',
	'BADGE_CREATE'			=> 'Create Badge',
	'TITLE_EDIT'			=> 'Edit Title',
	'TITLE_CREATE'			=> 'Create Title',
	'BADGE_NAME'			=> 'Badge Name',
	'BADGE_DETAILS'			=> 'Badge Details',
	'BADGE_DESC'			=> 'Badge Description',
	'TITLE_NAME'			=> 'Title Name',
	'TITLE_DETAILS'			=> 'Title Details',
	'TITLE_DESC'			=> 'Title Description',
	'BADGE_IMAGE_URL'		=> 'Badge Image URL',
	'BADGE_URL_EXPLAIN'		=> 'Relative path to the image file',
	'CURRENT_IMAGE'			=> 'Current Image',
	'BADGE_ERR_USERNAME'	=> 'No badge name specified.',
	'TITLE_ERR_USERNAME'	=> 'No title name specified.',
	'BADGE_ERR_USER_LONG'	=> 'Badge names cannot exceed 60 characters. The specified name is too long.',
	'TITLE_ERR_USER_LONG'	=> 'Title names cannot exceed 60 characters. The specified name is too long.',
	'BADGE_NAME_TAKEN'		=> 'The badge name you entered is already in use, please select an alternative.',
	'TITLE_NAME_TAKEN'		=> 'The title you entered is already in use, please select an alternative.',
	'BADGE_UPDATED'			=> 'Badge updated successfully.',
	'TITLE_UPDATED'			=> 'Title updated successfully.',
	'BADGE_CREATED'			=> 'Badge has been created successfully.',
	'TITLE_CREATED'			=> 'Title has been created successfully.',
	'BADGE_DELETED'			=> 'Badge deleted.',
	'TITLE_DELETED'			=> 'Title deleted.',
	'BADGE_MEMBERS'			=> 'Badge Members',
	'TITLE_MEMBERS'			=> 'Title Members',
	'USER_BADGES'			=> 'User Badges',
	'USER_TITLES'			=> 'User Titles',
	'USER_ADD_BADGE'		=> 'Add A Badge',
	'USER_ADD_TITLE'		=> 'Add A Title',
	'USER_ADD_BADGE_EXPLAIN' => 'Choose badge(s) from the list to add to this user:',
	'USER_ADD_TITLE_EXPLAIN' => 'Choose title(s) from the list to add to this user:',
	'BADGE_NO_MEMBERS'		=> 'No one has earned this yet.',
	'BADGE_DATE_EARNED'		=> 'Date Earned',
	'NUM_BADGES'			=> 'Number of Badges',
	'BADGE_DELETE_MARKED'	=> 'Delete Marked',
	'ADD_USERS'				=> 'Add Users',
	'ADD_USERS_EXPLAIN'		=> 'This is where you can add a user to the list of people who have earned this badge. Please enter each username on a separate line.',
	'BADGE_USERS_EXIST'		=> 'The selected users already have that badge.',
	'TITLE_USERS_EXIST'		=> 'The selected users already have that title.',
	'BADGE_USERS_ADDED'		=> 'Badge awarded to users successfully.',
	'TITLE_USERS_ADDED'		=> 'Title awarded to users successfully.',
	'BADGE_USERS_REMOVED'	=> 'Badge removed from users.',
	'TITLE_USERS_REMOVED'	=> 'Title removed from users.',
	'BADGE_DISPLAYED_HEADER' => 'Displayed?',
	'USER_NO_BADGES'		=> 'This user has earned no badges',
	'USER_BADGES_EXPLAIN'	=> 'This is a list of badges for the selected user. You can add or remove a badge from the user, or set whether or not a badge is displayed.',
	'USER_TITLES_EXPLAIN'	=> 'This is a list of titles for the selected user. You can add or remove a title from the user, or set which title is displayed.',
	'UCP_BADGES_BADGES'		=> 'Badges',
	'UCP_BADGES_TITLES'		=> 'Titles',
	'UCP_BADGES_EXPLAIN'	=> 'Badges are earned for performing certain actions within the community. You can select up to five badges to be displayed under your avatar, as well as re-order the badges you\'re wearing. <strong>Note that changes are NOT SAVED until you click the Save button!</strong>',
	'UCP_TITLES_EXPLAIN'	=> 'Titles are earned for performing certain actions within the community. You can select one title to be displayed.',
	'YOUR_BADGES'			=> 'Badges You\'ve Earned',
	'YOUR_BADGES_EXPLAIN'	=> 'On the left, are "unworn" badges, on the right are "worn" badges that will be displayed. Use the left and right arrows to select badges to be worn. Use the up and down arrows to re-order the worn badges.',
	'UCP_NO_BADGES'			=> 'You have no badges.',
	'UCP_NO_TITLES'			=> 'You have no titles.',
	'BADGE_IMAGE_HEADER'	=> 'Image',
	'UCP_SAVE'				=> 'Save',
	'BADGE_ADD'				=> 'Add Badge(s)',
	'TITLE_ADD'				=> 'Add Title(s)',
	'BADGES_ADDED'			=> 'The badges have been added.',
	'TITLES_ADDED'			=> 'The titles have been added.',
	'CONFIRM_ADD_BADGES'	=> 'Are you sure you want to add badges to this user?',
	'CONFIRM_ADD_TITLES'	=> 'Are you sure you want to add titles to this user?',
	'BADGES_REMOVED'		=> 'The badges have been removed.',
	'TITLES_REMOVED'		=> 'The titles have been removed.',
	'CONFIRM_DELETE_BADGES'	=> 'Are you sure you want to delete badges from this user?',
	'CONFIRM_DELETE_TITLES'	=> 'Are you sure you want to delete titles from this user?',
	));

?>
