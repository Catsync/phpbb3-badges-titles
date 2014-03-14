<?php
/**
*
* @package acp
* @version $Id: v3_modules.xml 52 2007-12-09 19:45:45Z jelly_doughnut $
* @copyright (c) 2007 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/
                             
/**
* @package module_install
*/
class acp_hello_world_info
{
    function module()
    {
    return array(
        'filename'  => 'acp_hello_world',
        'title'     => 'ACP_HELLO_WORLD',
        'version'   => '1.0.0',
        'modes'     => array(
            'hello_user'        => array('title' => 'HELLO_USER', 'auth' => 'acl_a_user', 'cat' => array('ACP_AUTOMATION')),
            'hello_world'       => array('title' => 'HELLO_WORLD', 'auth' => 'acl_a_group', 'cat' => array('ACP_AUTOMATION')),
            'hello_bertie'      => array('title' => 'HELLO_BERTIE', 'auth' => 'acl_a_board && acl_a_server', 'cat' => array('ACP_AUTOMATION')),
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