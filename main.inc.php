<?php
/*
Plugin Name: Contribute to Demo Server
Version: auto
Description: server side for Contribute to Demo plugin (receive contributions)
Plugin URI: auto
Author: plg
Author URI: http://piwigo.org
*/

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+

global $prefixeTable;

define('CTDS_CONTRIB_TABLE', $prefixeTable.'contribs');
define('CTDS_PATH' , PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');

include_once(CTDS_PATH.'include/functions.inc.php');

// +-----------------------------------------------------------------------+
// | Add event handlers                                                    |
// +-----------------------------------------------------------------------+

add_event_handler('get_admin_plugin_menu_links', 'ctds_admin_menu');
function ctds_admin_menu($menu)
{
  global $page;

  $page['ctds_pendings'] = ctds_get_pending_ids();

  $name = 'Contribs [server]';
  if (count($page['ctds_pendings']) > 0)
  {
    $style = 'background-color:#666;';
    $style.= 'color:white;';
    $style.= 'padding:1px 5px;';
    $style.= 'border-radius:10px;';
    $style.= 'margin-left:5px;';

    $name.= '<span style="'.$style.'">'.count($page['ctds_pendings']).'</span>';
  }

  array_push(
    $menu,
    array(
      'NAME' => $name,
      'URL'  => get_root_url().'admin.php?page=plugin-contribute_to_demo_server'
      )
    );

  return $menu;
}

add_event_handler('ws_add_methods', 'ctds_ws_add_methods');
function ctds_ws_add_methods($arr)
{
  global $conf;
  $service = &$arr[0];

  $service->addMethod(
    'contrib.photo.submit',
    'ctds_ws_photo_submit',
    array(
      'piwigo_url' => array(),
      'piwigo_relative_path' => array(),
      'piwigo_image_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'file' => array('default' => null),
      'name' => array('default' => null),
      ),
    'Submit a photo to the Piwigo demo'
    );

  $service->addMethod(
    'contrib.photo.validate',
    'ctds_ws_photo_validate',
    array(
      'image_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      ),
    'Validate a submitted photo',
    null,
    array('admin_only'=>true)
    );

  $service->addMethod(
    'contrib.photo.reject',
    'ctds_ws_photo_reject',
    array(
      'image_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      ),
    'Reject a submitted photo',
    null,
    array('admin_only'=>true)
    );
}
