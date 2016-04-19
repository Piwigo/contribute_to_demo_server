jQuery(document).ready(function(){
  jQuery("a.zoom").colorbox({rel:"zoom"});

  jQuery('.validate a').click(function() {
    var imageId = jQuery(this).data('image_id');

    jQuery.ajax({
      url: "ws.php?format=json&method=contrib.photo.validate",
      type:"POST",
      data: {image_id:imageId},
      beforeSend: function() {
        jQuery('#s'+imageId+' .validate img.loading').show();
      },
      success:function(data) {
        var data = jQuery.parseJSON(data);
        if (data.result === true) {
          jQuery('#s'+imageId).fadeOut();
        }
        else {
          alert('problem on validate');
          jQuery('#s'+imageId+' .validate img.loading').hide();
        }
      },
      error:function(XMLHttpRequest, textStatus, errorThrows) {
        alert('problem on validate');
        jQuery('#s'+imageId+' .validate img.loading').hide();
      }
    });

    return false;
  });

  jQuery('.reject a').click(function() {
    var imageId = jQuery(this).data('image_id');

    jQuery.ajax({
      url: "ws.php?format=json&method=contrib.photo.reject",
      type:"POST",
      data: {image_id:imageId},
      beforeSend: function() {
        jQuery('#s'+imageId+' .reject img.loading').show();
      },
      success:function(data) {
        var data = jQuery.parseJSON(data);
        if (data.stat == 'ok') {
          console.log("submission rejected, uuid="+data.result.contrib_uuid);

          // sub AJAX request, this time we call the remote Piwigo
          jQuery.ajax({
            url: data.result.piwigo_url+"/ws.php?format=json&method=contrib.photo.rejected",
            type:"POST",
            data: {
              uuid : data.result.contrib_uuid,
            },
            success:function(data) {
              var data = jQuery.parseJSON(data);
              if (data.stat == 'ok') {
                console.log("contribution rejected");
                jQuery('#s'+imageId).fadeOut();
              }
              else {
                console.log("reject failed");
              }
            },
            error:function(XMLHttpRequest, textStatus, errorThrows) {
              alert("error calling remote reject");
            }
          });
        }
        else {
          alert('problem on reject');
          jQuery('#s'+imageId+' .reject img.loading').hide();
        }
      },
      error:function(XMLHttpRequest, textStatus, errorThrows) {
        alert('problem on reject');
        jQuery('#s'+imageId+' .reject img.loading').hide();
      }
    });

    return false;
  });
});
