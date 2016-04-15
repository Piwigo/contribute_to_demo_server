<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based picture gallery                                  |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2015 Piwigo Team                  http://piwigo.org |
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

// get invalidate_user_cache function
include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

function ctds_get_pending_ids()
{
  $query = '
  SELECT
      image_idx
    FROM '.CTDS_CONTRIB_TABLE.'
    WHERE state = \'submitted\'
  ;';
  return query2array($query, null, 'image_idx');
}

/*
 * try to get the mime-type of a file
 * as no method is totally reliable we can fallback to a default mime
 */
function get_mime($file, $default="application/octet-stream")
{
  if (function_exists("mime_content_type"))
  {
    $mime = mime_content_type($file);
    if (!empty($mime)) return $mime;
  }

  if (function_exists("finfo_file"))
  {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file);
    finfo_close($finfo);
    if (!empty($mime)) return $mime;
  }

  if (!stristr(ini_get("disable_functions"), "shell_exec"))
  {
    $file = escapeshellarg($file);
    $mime = shell_exec("file -bi " . $file);
    if (!empty($mime)) return $mime;
  }

  return $default;
}

function ctds_photo_validate($image_id)
{
  global $conf, $page, $user;

  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));

  single_update(
    CTDS_CONTRIB_TABLE,
    array(
      'state' => 'validated',
      'validated_on' => $dbnow,
      ),
    array(
      'image_idx' => $image_id
      )
    );

  single_update(
    IMAGES_TABLE,
    array(
      'date_available' => $dbnow,
      'level' => 0,
      ),
    array(
      'id' => $image_id,
      )
    );

  array_push($page['infos'], l10n('photo validated'));

  invalidate_user_cache();

  // TODO send email to contributor

  // notify the contributor Piwigo
  $query = '
SELECT
    *
  FROM '.CTDS_CONTRIB_TABLE.'
  WHERE image_idx = '.$image_id.'
;';
  $contribs = query2array($query);
  $contrib = $contribs[0];

  $notify_url = $contrib['piwigo_url'].'/ws.php';

  $get_params = array(
    'format' => 'json',
    'method' => 'contrib.photo.validated',
    'uuid' => $contrib['contrib_uuid'],
  );

  // fetchRemote($src, &$dest, $get_data=array(), $post_data=array()
  fetchRemote($notify_url, $result, $get_params);

  return true;
}

function ctds_photo_reject($image_id)
{
  global $conf, $page, $user;

  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));

  single_update(
    CTDS_CONTRIB_TABLE,
    array(
      'state' => 'rejected',
      'rejected_on' => $dbnow,
      ),
    array(
      'image_idx' => $image_id
      )
    );

  delete_elements(array($image_id), true);

  array_push($page['infos'], l10n('Photo rejected'));

  invalidate_user_cache();

  // TODO send email to contributor

  return true;
}

// +-----------------------------------------------------------------------+
// | API functions                                                         |
// +-----------------------------------------------------------------------+

function ctds_ws_photo_submit($params, &$service)
{
  global $conf;

  $params = array_map('trim', $params);

  $params['piwigo_url'] = rtrim($params['piwigo_url'], '/');

  $allowed_extensions = array('jpg','jpeg','png','gif');
  $allowed_mimes = array('image/jpeg', 'image/png', 'image/gif');

  // check remote url
  if (!url_is_remote($params['piwigo_url']))
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid piwigo_url');
  }
  // check file extension
  if (!in_array(strtolower(get_extension($params['piwigo_relative_path'])), $allowed_extensions))
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid file type');
  }
  // download file
  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

  $upload_dir = $conf['upload_dir'].'/buffer';

  // create the upload directory tree if not exists
  if (!mkgetdir($upload_dir, MKGETDIR_DEFAULT&~MKGETDIR_DIE_ON_ERROR))
  {
    return new PwgError(500, 'error during buffer directory creation');
  }

  $piwigo_photo_url = $params['piwigo_url'].'/'.$params['piwigo_relative_path'];

  $temp_filepath = $upload_dir.'/'.md5($piwigo_photo_url.time()).'.'.get_extension($piwigo_photo_url);
  $file = fopen($temp_filepath, 'w+');
  $result = fetchRemote($piwigo_photo_url, $file);
  fclose($file);

  // download failed ?
  if (!$result)
  {
    @unlink($temp_filepath);
    return new PwgError(WS_ERR_INVALID_PARAM, l10n('Unable to download file'));
  }
  // check mime-type
  if (!in_array(get_mime($temp_filepath, $allowed_mimes[0]), $allowed_mimes))
  {
    @unlink($temp_filepath);
    return new PwgError(WS_ERR_INVALID_PARAM, l10n('Invalid file type'));
  }
  // add photo
  include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');

  $image_id = add_uploaded_file(
    $temp_filepath,
    $params['file'],
    array(1), // TODO make this configurable
    16 // level -> moderation pending
    );

  single_update(
    IMAGES_TABLE,
    array('name' => $params['name']),
    array('id' => $image_id)
  );

  $query = '
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$image_id.'
;';
  $images = query2array($query);
  $image = $images[0];

  $uuid = generate_key(40);

  single_insert(
    CTDS_CONTRIB_TABLE,
    array(
      'image_idx' => $image_id,
      'gallery_title' => $params['gallery_title'],
      'piwigo_url' => $params['piwigo_url'],
      'piwigo_relative_path' => $params['piwigo_relative_path'],
      'piwigo_image_id' => $params['piwigo_image_id'],
      'contrib_uuid' => $uuid,
      'state' => 'submitted',
      'submitted_on' => $image['date_available'],
      )
    );

   return array(
     'uuid' => $uuid,
     );
}

function ctds_ws_photo_remove($params, &$service)
{
  global $conf;

  // check the uuid
  if (!preg_match(CTDS_UUID_PATTERN, $params['uuid']))
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid uuid');
  }

  // does the uuid exists?
  $query = '
SELECT
    *
  FROM '.CTDS_CONTRIB_TABLE.'
  WHERE contrib_uuid = \''.$params['uuid'].'\'
;';
  $contribs = query2array($query);

  if (count($contribs) == 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'unknow uuid');
  }

  $contrib = $contribs[0];

  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));

  // we update the contribution but we don't remove the row from database
  single_update(
    CTDS_CONTRIB_TABLE,
    array(
      'state' => 'removed',
      'removed_on' => $dbnow,
      ),
    array(
      'image_idx' => $contrib['image_idx']
      )
    );

  delete_elements(array($contrib['image_idx']), true);

  invalidate_user_cache();

  return array(
    'uuid' => $params['uuid'],
  );
}

function ctds_ws_photo_validate($params, &$service)
{
  if (ctds_photo_validate($params['image_id']))
  {
    return true;
  }

  return false;
}

function ctds_ws_photo_reject($params, &$service)
{
  if (ctds_photo_reject($params['image_id']))
  {
    return true;
  }

  return false;
}
