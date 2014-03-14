<?php
/**
*
* @package ucp
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/
                             
/**
* @package module_install
*/
class ucp_badges_info
{
    function module()
    {
    return array(
        'filename'  => 'ucp_badges',
        'title'     => 'Badges',
        'version'   => '1.0.0',
        'modes'     => array(
            'badges'        => array('title' => 'Manage Badges', 'auth' => 'acl_u_badges_has', 'cat' => array('UCP_BADGES')),
            'titles'        => array('title' => 'Manage Titles', 'auth' => 'acl_u_badges_has', 'cat' => array('UCP_BADGES')),
    
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