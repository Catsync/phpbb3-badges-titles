<?php
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
 
// Adding new category
$lang['permission_cat']['badges'] = 'Badges';
 
// Adding the permissions
$lang = array_merge($lang, array(
    'acl_a_badges_create'    => array('lang' => 'Can create badges and titles', 'cat' => 'badges'),
    'acl_a_badges_assign'    => array('lang' => 'Can assign badges and titles', 'cat' => 'badges'),
	'acl_u_badges_has'		 => array('lang' => 'Can have badges and titles', 'cat' => 'badges'),
));
?>