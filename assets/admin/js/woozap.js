jq2 = jQuery.noConflict();
jq2(function( $ ) {


  var woozap_url_field = $('.woozap_zapier_url_cls');
  var woozap_sp_click = $('.woozap_sp_click');
  var woozap_url_field_parent = woozap_url_field.parent();

  if(woozap_sp_click.length > 0){
    woozap_sp_click.click(function(event){
      event.preventDefault();
      $(this).parent().find('#woozap_sp').toggle();
    });
  }
  
  if(woozap_url_field.length > 0){

  	woozap_url_field.after('<button style="padding: 0 10px;margin-left: 20px;height: 32px;" id="woozap_trigger_btn" class="button" >Trigger</button>');
  	woozap_url_field_parent.find('span.description').after('<span style="float:left;"><em style="font-size:12px;color:red"><strong>Note : Click the Save button below once you have added the url in field. Once you have saved the url then click Trigger button to test this zap.</strong></em></span>');
  
  //action


  woozap_url_field_parent.on('click','#woozap_trigger_btn',function(event){
  	event.preventDefault();
  	var btn = $(this);
    var url = btn.parent().find('.woozap_zapier_url_cls');
  	var woozap_url = url.val();
  	if(woozap_url.length <= 0){
  		alert('Please enter a valid url before apply trigger.');
  		return false;
  	}
    
    
  	var params ={
  		'action' : 'trigger_woozap_ajax',
  		'api_url' : woozap_url
  	};
  	$.post(woozap_ajax.ajaxurl,params,function(res){
  		if(res == 'success'){
  			alert('Trigged successfully !');
  		}else{
  			alert('Error while execution , please try again !');
  		}
  	});
  });
  }//if exist
});