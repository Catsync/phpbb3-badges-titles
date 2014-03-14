<?php
/**
*
* @package acp
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

// File: includes/acp/info/acp_badges.php


/**
* @package module_install
*/
class acp_badges_info
{
    function module()
    {
    return array(
        'filename'  => 'acp_badges',
        'title'     => 'Badges',
        'version'   => '1.0.0',
        'modes'     => array(
            'badges'        => array('title' => 'Badges', 'auth' => 'acl_a_badges_create', 'cat' => array('ACP_BADGES')),
            'titles'        => array('title' => 'Titles', 'auth' => 'acl_a_badges_create', 'cat' => array('ACP_BADGES')),
    		'config'		=> array('title' => 'Config', 'auth' => 'acl_a_badges_create', 'cat' => array('ACP_BADGES')),
        	),
        );
         
    }
                             
    function install()
    {
    }
                                 
    function uninstall()
    {
    }
 
}
?>