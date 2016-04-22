<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based picture gallery                                  |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2011 Piwigo Team                  http://piwigo.org |
// | Copyright(C) 2003-2008 PhpWebGallery Team    http://phpwebgallery.net |
// | Copyright(C) 2002-2003 Pierrick LE GALL   http://le-gall.net/pierrick |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

if( !defined("PHPWG_ROOT_PATH") )
{
  die ("Hacking attempt!");
}

include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
include_once(PHPWG_ROOT_PATH.'include/functions_picture.inc.php');

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

check_status(ACCESS_ADMINISTRATOR);

// +-----------------------------------------------------------------------+
// | form submission                                                       |
// +-----------------------------------------------------------------------+

if (!empty($_POST))
{
  check_input_parameter('groups', $_POST, true, PATTERN_ID);

  // first we must reset all groups to false
  $query = '
UPDATE '.GROUPS_TABLE.'
  SET ctds_notify = \'false\'
;';
  pwg_query($query);

  // then we set submitted groups to true
  if (isset($_POST['groups']) and count($_POST['groups']) > 0)
  {
    $query = '
UPDATE '.GROUPS_TABLE.'
  SET ctds_notify = \'true\'
  WHERE id IN ('.implode(',', $_POST['groups']).')
;';
    pwg_query($query);
  }
  
  array_push($page['infos'], l10n('Information data registered in database'));
}

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->set_filename('plugin_admin_content', dirname(__FILE__).'/admin_config.tpl');

// +-----------------------------------------------------------------------+
// | form options                                                          |
// +-----------------------------------------------------------------------+

// groups
$query = '
SELECT id
  FROM '.GROUPS_TABLE.'
;';
$group_ids = query2array($query, null, 'id');

$query = '
SELECT id
  FROM '.GROUPS_TABLE.'
  WHERE ctds_notify = \'true\'
;';
$groups_selected = query2array($query, null, 'id');

$template->assign(
  array(
    'CACHE_KEYS' => get_admin_client_cache_keys(array('groups')),
    'groups' => $group_ids,
    'groups_selected' => $groups_selected,
    )
  );

// +-----------------------------------------------------------------------+
// | sending html code                                                     |
// +-----------------------------------------------------------------------+

$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
?>