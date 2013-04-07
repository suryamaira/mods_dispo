/**
 * Arie Nugraha 2012 : dicarve@gmail.com
 */

jQuery('document').ready(function() {
  jQuery('.ajaxUpload').click(function(e) {
    e.preventDefault();
    var ajaxUpload = jQuery(this);
    var parentForm = ajaxUpload.parents('form');
    // set form target
    var originalFormAction = parentForm.attr('action');
    parentForm.attr('action', ajaxUpload.attr('href'));
    parentForm.attr('target', 'uploadTarget');
    parentForm.submit();
    // reset back to original setting
    parentForm.removeAttr('target');
    parentForm.attr('action', originalFormAction);
  });

  jQuery('#daftarFile').delegate('a.hapusFile', 'click', function(e) {
    e.preventDefault();
    var link = $(this);
    var idFile = link.attr('fileid');
    var actionURL = link.attr('href');
    // alert(idFile);
    jQuery.ajax({
      type: 'POST',
      url: actionURL,
      data: 'fileid='+idFile,
      success: function() {
       var tRow = link.parents('tr');
       $(tRow[0]).remove();
       alert('File berhasil dihapus');
      },
      error: function() {
       alert('File gagal dihapus!');
      }
    });
  });

});
