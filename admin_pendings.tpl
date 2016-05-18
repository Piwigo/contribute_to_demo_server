{combine_script id='jquery.colorbox' load='footer' require='jquery' path='themes/default/js/plugins/jquery.colorbox.min.js'}
{combine_css path="themes/default/js/plugins/colorbox/style2/colorbox.css"}

{combine_script id='ctds_pendings' load='footer' require='jquery' path='plugins/contribute_to_demo_server/admin_pendings.js'}

{html_style}{literal}
.comment p {text-align:left; margin:5px 0 0 5px}
.comment table {margin:5px 0 0 0; text-align:left;}
.comment table th {padding-right:10px}
.checkPhoto img.loading {display:none}
{/literal}{/html_style}

<div class="titrePage">
  <h2>{'Pending Photos'|@translate} - Contribute to Demo [server]</h2>
</div>

{if !empty($photos) }

<div class="showcaseFilter">
{if !empty($navbar) }{include file='navigation_bar.tpl'|@get_extent:'navbar'}{/if}
</div>

<table width="99%">
  {foreach from=$photos item=photo name=photo}
  <tr valign="top" class="{if $smarty.foreach.photo.index is odd}row2{else}row1{/if}" id="s{$photo.ID}">
    <td style="width:50px;text-align:center" class="checkPhoto">
      <img src="{$photo.TN_SRC}" style="margin:0.5em"><br>

      <span class="validate"><a data-image_id="{$photo.ID}" href="#">{'Validate'|@translate}</a>
      <img class="loading" src="themes/default/images/ajax-loader-small.gif">
      </span>

      ·

      <span class="reject"><a data-image_id="{$photo.ID}" href="#">{'Reject'|@translate}</a>
      <img class="loading" src="themes/default/images/ajax-loader-small.gif">
      </span>
    </td>
    <td>
  <div class="comment">
    <p class="commentAction" style="float:left;margin:0.5em 0 0 0.5em"><a href="{$photo.ZOOM_SRC}" class="zoom" title="{$photo.NAME|escape:html}">{'Zoom'|@translate}</a> &middot; <a href="{$photo.U_EDIT}">{'Edit'|@translate}</a></p>
    <p class="commentHeader"><strong><a href="{$photo.PIWIGO_URL}" target="_blank">{$photo.PIWIGO_GALLERY_TITLE}</a></strong> - <a class="icon-mail-alt" href="mailto:{$CONTRIBUTOR_EMAIL}"></a> - <em>{$photo.ADDED_ON}</em></p>
    <table>
      <tr>
        <th>{'Name'|@translate}</th>
        <td>{$photo.NAME}</td>
      </tr>
      <tr>
        <th>{'Created on'|@translate}</th>
        <td>{$photo.DATE_CREATION}</td>
      </tr>
      <tr>
        <th>{'Dimensions'|@translate}</th>
        <td>{$photo.DIMENSIONS}</td>
      </tr>
    </table>
  </div>
    </td>
  </tr>
  {/foreach}
</table>

{if !empty($navbar) }{include file='navigation_bar.tpl'|@get_extent:'navbar'}{/if}
{/if}
