<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class contribute_to_demo_server_maintain extends PluginMaintain
{
  private $installed = false;

  function __construct($plugin_id)
  {
    parent::__construct($plugin_id);
  }

  function install($plugin_version, &$errors=array())
  {
    global $conf, $prefixeTable;

    $query = '
CREATE TABLE IF NOT EXISTS '.$prefixeTable.'contribs (
  image_idx int(11) NOT NULL,
  piwigo_url varchar(255) NOT NULL,
  piwigo_relative_path varchar(255) NOT NULL,
  piwigo_image_id int(11) NOT NULL,
  contrib_uuid varchar(255) NOT NULL,
  state enum(\'submitted\',\'validated\',\'rejected\',\'removed\') default \'submitted\',
  submitted_on datetime NOT NULL,
  validated_on datetime,
  rejected_on datetime,
  removed_on datetime,
  PRIMARY KEY (image_idx)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;';
    pwg_query($query);

    $this->installed = true;
  }

  function activate($plugin_version, &$errors=array())
  {
    global $prefixeTable;

    if (!$this->installed)
    {
      $this->install($plugin_version, $errors);
    }
  }

  function update($old_version, $new_version, &$errors=array())
  {

    $this->install($new_version, $errors);
  }

  function deactivate()
  {
  }

  function uninstall()
  {
    global $prefixeTable;

    $query = 'DROP TABLE '.$prefixeTable.'contribs;';
    pwg_query($query);
  }
}
?>