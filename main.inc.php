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
define('CTDS_UUID_PATTERN', '/^[a-zA-Z0-9]{20,}$/');

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
      'gallery_title' => array('default' => null),
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

// +-----------------------------------------------------------------------+
// | picture                                                               |
// +-----------------------------------------------------------------------+

add_event_handler('loc_end_picture', 'ctds_end_picture');
function ctds_end_picture()
{
  global $conf, $template, $picture, $user;

  $query = '
SELECT
    *
  FROM '.CTDS_CONTRIB_TABLE.'
  WHERE image_idx = '.$picture['current']['id'].'
;';
  $contribs = query2array($query);

  if (count($contribs) == 0)
  {
    return;
  }

  $contrib = $contribs[0];

  $gallery_title = $contrib['gallery_title'];
  if (empty($gallery_title))
  {
    $gallery_title = preg_replace('#^https?://#', '', $contrib['piwigo_url']);
  }

  $template->set_prefilter('picture', 'ctds_end_picture_prefilter');
  $template->assign(
    array(
      'CTDS_GALLERY_TITLE' => $gallery_title,
      'CTDS_PIWIGO_URL' => $contrib['piwigo_url'],
      )
    );

  $template->set_filename('ctds_picture', realpath(CTDS_PATH.'picture.tpl'));
  $template->assign_var_from_handle('CTDS_CONTENT', 'ctds_picture');
}

function ctds_end_picture_prefilter($content, &$smarty)
{
  $content = preg_replace(
    '/id="standard"\s+class="imageInfoTable"[^>]*>/',
    '$0{$CTDS_CONTENT}',
    $content
  );

  return $content;
}

// +-----------------------------------------------------------------------+
// | section init                                                          |
// +-----------------------------------------------------------------------+

add_event_handler('loc_end_section_init', 'ctds_section_init');

/* define page section from url */
function ctds_section_init()
{
  global $tokens, $page, $conf, $user, $template;

  if ($tokens[0] != 'contrib')
  {
    return;
  }

  if (!isset($tokens[1]))
  {
    die("missing uuid");
  }

  $uuid = $tokens[1];

  if (!preg_match(CTDS_UUID_PATTERN, $uuid))
  {
    die("invalid uuid");
  }

  $query = '
SELECT
    image_idx
  FROM '.CTDS_CONTRIB_TABLE.'
  WHERE contrib_uuid = \''.$uuid.'\'
;';
  $contribs = query2array($query);
  if (count($contribs) == 0)
  {
    die('unknown uuid');
  }

  $contrib = $contribs[0];

  $query = '
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$contrib['image_idx'].'
;';
  $images = query2array($query);

  if (count($images) == 0)
  {
    die('image no longer available');
  }

  $image = $images[0];

  // find the first reachable category linked to the photo
  $query = '
SELECT
    category_id
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE image_id = '.$image['id'].'
;';

  $authorizeds = array_diff(
    array_from_query($query, 'category_id'),
    explode(',', calculate_permissions($user['id'], $user['status']))
    );

  foreach ($authorizeds as $category_id)
  {
    $url = make_picture_url(
      array(
        'image_id' => $image['id'],
        'category' => get_cat_info($category_id),
        )
      );

    redirect($url);
  }

  die("the image is not visible currently");
}
