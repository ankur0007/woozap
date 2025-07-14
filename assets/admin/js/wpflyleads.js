jQuery(document).ready(function ($) {
  var body = $(document).find('body').find('.wpflyleads');
  var wpflyleads_get_all_status = body.find('.wpflyleads_get_all_status');
  var wpflyleads_setting_form = body.find("#wpflyleads_setting_form");
  var wpflyleads_custom_url_column = body.find("#wpflyleads_custom_url_column");
  var url_count = body.find("#wpflyleads_connection_url_count");
  var wpflyleads_plus = body.find("#wpflyleads_plus");
  var wpflyleads_plus_status_field = body.find("#wpflyleads_plus_status_field");
  var wpflyleads_custom_status_field = body.find("#wpflyleads_custom_status_field");
  var wc_all_status = wpflyleads_ajax.get_status;

  console.log(wc_all_status);
  var get_saved_status = wpflyleads_ajax.get_all_status_array;
  var saved_array_length = get_saved_status.length;

  //var get_checkbox_value=wpflyleads_ajax.get_checkbox_value;
  var wpflyleads_connection_name_select = body.find("#wpflyleads_connection_name_select");
  var i = 1;

  wpflyleads_get_all_status.select2();
  wpflyleads_connection_name_select.select2();


  body.on('click', '#wpflyleads_plus_status_field', function (event) {
    event.preventDefault();

    i++;
    console.log('Add more status' + i);
    if (i > 1) {

      wpflyleads_custom_status_field.after("<tr class='wpflyleads_custom_status_field' id ='wpflyleads_custom_status_field" + i + "'><td><strong>From</strong>&nbsp;&nbsp;&nbsp;<select style='width:50%;' class='wpflyleads_get_all_status' id='wpflyleads_get_from_status" + i + "' name='wpflyleads_get_from_status[]' ><option value='0' disabled selected hidden >Select a status</option>" + wc_all_status + "</select></td><td><strong>To</strong>&nbsp;&nbsp;&nbsp;<select style='width:50%;' class='wpflyleads_get_all_status' id='wpflyleads_get_to_status" + i + "' name='wpflyleads_get_to_status[]' > <option value= '0' disabled selected hidden >Select a status</option>" + wc_all_status + "</select><button style='height:27px; width:46px;margin-left:28px;' id='" + i + "' class='wpflyleads_remove_status '>x</button></td><td></td></tr>");
      $("#wpflyleads_get_from_status" + i + "").select2();
      $("#wpflyleads_get_to_status" + i + "").select2();
    }
  });


  body.on('click', '.wpflyleads_remove_status', function (event) {
    event.preventDefault();
    var button_id = $(this).attr("id");
    $("#wpflyleads_custom_status_field" + button_id + "").remove();

  });

  wpflyleads_plus.on('click', function (event) {
    event.preventDefault();
    i++;
    if (i > 1) {

      wpflyleads_custom_url_column.after("<tr id='wpflyleads_custom_url_column" + i + "'><th class='wpflyleads-connection' ><div class='wpflyleads-connection-name'><label>Name</label><input  style='' type='text' name='connection_url_name[]' class='connection_url_name' id='connection_url_name" + i + "' value='' placeholder='Url name'></div><div class='wpflyleads-connection-name'><label>Name</label><input style='' type='text' name='connection_url_slug[]' class='connection_url_slug'  id='connection_url_slug" + i + "' value='' placeholder='Url slug'></div><div class='wpflyleads-connection-server'><label>Server</label><select name='connection_url_server[]' class='connection_url_server'  id='connection_url_server" + i + "' value='' placeholder='Url Server'>" + wpflyleads_ajax.get_servers + "</select></div></th><td><input style='min-width:500px' type='text' class='wpflyleads_connection_url_cls' name='wpflyleads_connection_url[]' id='wpflyleads_connection_url" + i + "' value=''></td><td><button style='height:27px; width:46px; margin-left:146px;' id='" + i + "' class='wz_remove_field '>x</button></td></tr>");

    }
  });
  $(document).on('click', '.wz_remove_field', function (event) {
    event.preventDefault();
    var button_id = $(this).attr("id");

    $("#wpflyleads_custom_url_column" + button_id + "").remove();

  });
});

jq2 = jQuery.noConflict();
jq2(function ($) {


  var wpflyleads_setting_form = $('#wpflyleads_setting_form');
  var wpflyleads_url_field = $('.wpflyleads_connection_url_cls');
  var wpflyleads_sp_click = $('.wpflyleads_sp_click');
  var wpflyleads_url_field_parent = wpflyleads_url_field.parent();



  if (wpflyleads_sp_click.length > 0) {
    wpflyleads_sp_click.click(function (event) {
      event.preventDefault();
      $(this).parent().find('#wpflyleads_sp').toggle();
    });
  }

  if (wpflyleads_url_field.length > 0) {


    $(document).on('click', '#wpflyleads_trigger_btn', function (event) {
      event.preventDefault();

      var btn = $(this);
      var url = btn.parents('tr').find('.wpflyleads_connection_url_cls');
      var wpflyleads_url = url.val();
      var server = btn.parents('tr').find('.wpflyleads-connection').find('.wpflyleads-connection-server select option:checked').val();

      if (wpflyleads_url.length <= 0) {
        alert('Please enter a valid url before apply trigger.');
        return false;
      }


      var params = {
        'action': 'trigger_wpflyleads_ajax',
        'api_url': wpflyleads_url,
        'api_server': server
      };
      $.post(wpflyleads_ajax.ajaxurl, params, function (res) {
        if (res == 'success') {
          alert('Trigged successfully !');
        } else {
          alert('Error while execution , please try again !');
        }
      });
    });
  }//if exist

  $(document).on('click', '#wpflyleads_connection_list_trigger_btn', function (event) {
    event.preventDefault();

    var connection_name = $("#wpflyleads_connection_name_select").val();
    var current_order_id = $("#wpflyleads_connection_name_select").attr("current_order_id");

    if (connection_name == null) {
      return false;
    }
    if (current_order_id <= 0) {
      return false;
    }


    var params = {
      'action': 'trigger_zapname_list_wpflyleads_ajax',
      'connection_name': connection_name,
      'current_order_id': current_order_id
    };
    $.post(wpflyleads_ajax.ajaxurl, params, function (res) {

      if (res == 'success') {

        $("#wpflyleads_connection_status_messages").html("");
        $("#wpflyleads_connection_status_messages").append("Trigged successfully");
        $("#wpflyleads_connection_status_messages").css('color', 'green').show().delay(3000).fadeOut();




      } else {
        $("#wpflyleads_connection_status_messages").html("");
        $("#wpflyleads_connection_status_messages").append("Error while execution , please try again !");
        $("#wpflyleads_connection_status_messages").css('color', 'red').show().delay(3000).fadeOut();

      }
    });
  });


});