{combine_script id='LocalStorageCache' load='footer' path='admin/themes/default/js/LocalStorageCache.js'}

{combine_script id='jquery.selectize' load='footer' path='themes/default/js/plugins/selectize.min.js'}
{combine_css id='jquery.selectize' path="themes/default/js/plugins/selectize.{$themeconf.colorscheme}.css"}

{html_style}{literal}
form p {text-align:left;}
.subOption {margin-left:2em; margin-bottom:20px;}
fieldset {border:none; border-top:1px solid #bbb;}
table.table2 {margin:0;}
.passwordCell {display:none}
form input[type=text] {width:400px}
.loading {display:none;}
#examples {display:none;}
.example {font-style:italic;}
{/literal}{/html_style}

{footer_script}
var pwg_token = "{$PWG_TOKEN}";

var groupsCache = new GroupsCache({
  serverKey: '{$CACHE_KEYS.groups}',
  serverId: '{$CACHE_KEYS._hash}',
  rootUrl: '{$ROOT_URL}'
});

{* <!-- CATEGORIES --> *}
var categoriesCache = new CategoriesCache({
  serverKey: '{$CACHE_KEYS.categories}',
  serverId: '{$CACHE_KEYS._hash}',
  rootUrl: '{$ROOT_URL}'
});

{literal}
jQuery(document).ready(function() {
  groupsCache.selectize(jQuery('[data-selectize=groups]'));
  categoriesCache.selectize(jQuery('[data-selectize=categories]'));
});
{/literal}{/footer_script}

<div class="titrePage">
  <h2>{'Configuration'|@translate} - Contribute to Demo [server]</h2>
</div>

<form method="post" action="{$F_ACTION}">

  <p>
    <strong>{'Destination album for contributions'|@translate}</strong>
    <br>
    <select data-selectize="categories" data-value="{$destination|@json_encode|escape:html}"
      placeholder="{'Type in a search term'|translate}"
      name="destination" style="width:600px;"></select>
  </p>

  <p>
{if count($groups) > 0}
    <strong>{'Notify group'|@translate}</strong>
    <br>
    <select data-selectize="groups" data-value="{$groups_selected|@json_encode|escape:html}"
      placeholder="{'Type in a search term'|translate}"
      name="groups[]" style="width:600px;"></select>
{else}
    {'There is no group in this gallery.'|@translate} <a href="admin.php?page=group_list" class="externalLink">{'Group management'|@translate}</a>
{/if}
  </p>

{if count($groups) > 0}
  <p class="formButtons">
    <input type="submit" name="submit" value="{'Save Settings'|@translate}">
  </p>
{/if}

</form>