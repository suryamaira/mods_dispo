/**
 * Arie Nugraha 2010 : dicarve@yahoo.com
 * This script is licensed under GNU GPL License 3.0
 */

/**
 * JQuery method to bind all Admin module related event
 */
jQuery.fn.registerAdminEvents = function() {
    // cache AJAX container
    var container = jQuery(this);

    // set all table with class datagrid
    jQuery('table.datagrid').each(function() {
        var datagrid = jQuery(this);
        datagrid.simbioTable();
        // register uncheck click event
        jQuery('.uncheck-all').click(function() {
            jQuery.unCheckAll('.datagrid');
        });
        // register check click event
        jQuery('.check-all').click(function() {
            jQuery.checkAll('.datagrid');
        });
        // set all row to show detail when double clicked
        datagrid.find('tr').each( function() {
            var tRow = jQuery(this);
            var rowLink = tRow.css({'cursor' : 'pointer'}).find('a');
            if (rowLink[0] != undefined) {
                tRow.dblclick(function() {jQuery(rowLink[0]).trigger('click')});
            }
        });
        // unregister event for table-header
        jQuery('.table-header', datagrid).parent().unbind();
    });

    // set all text with class dateInput to date input
    jQuery('.dateInput').dateInput();

    // unlock form button action
    jQuery('.form-unlock').unbind('click').click( function(evt) {
        evt.preventDefault();
        var unlock = jQuery(this);
        unlock.parents('form').enableForm();
    });

    jQuery('.form-cancel').click( function(evt) {
      history.back();
    });

    jQuery('.datagrid-option').change( function() {
      var optionVal = $(this).val();
      if (optionVal == '0') {
        return;
      }
      var parentForm = $(this).parents('form');
      parentForm.attr('action', './index.php?p='+optionVal);
      // alert(parentForm.attr('action'));
    });

    jQuery('.datagrid-submit').click( function(evt) {
      evt.preventDefault();
      var parentForm = $(this).parents('form');
      var optionVal = parentForm.find('.datagrid-option').val();
      if (optionVal == '0') {
        return;
      }
      var confirmation = confirm('Yakin ingin melakukan tindakan ini');
      if (confirmation) {
        parentForm.submit();
      }
    })

    return container;
}

// set all navigation links behaviour to AJAX
jQuery('document').ready(function() {
    // Register admin related event
    jQuery('#main-content').registerAdminEvents();

    // register form submit button action
    jQuery('.form-submit').click(function(evt) {
        var validated = true;
        evt.preventDefault();
        // get parent form
        var parentForm = jQuery(this).parents('form');
        // validate form
        parentForm.find('.required').each( function() {
            var elm = jQuery(this);
            if (!elm.val() && elm.context.nodeName != 'IFRAME') {
                elm.addClass('field-required');
                validated = false;
            } else {
                elm.removeClass('field-required');
            }
        });

        // stop if not validated
        if (!validated) {
            alert('Harap lengkapi terlebih dahulu semua field dengan tanda *!');
            return;
        }

        parentForm.submit();
    });
});
