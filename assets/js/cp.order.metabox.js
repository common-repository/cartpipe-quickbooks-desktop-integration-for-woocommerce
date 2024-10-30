jQuery( function ( $ ) {
	$('[data-dependency]').each(function(){
		var dependency 	= $(this).data('dependency');
		var value 		= $(this).data('value');
		var c_value 	= $('[name="' + dependency + '"]').val();
		
		if(value == c_value){
			$(this).show();
		}else{
			$(this).hide();
		}
		
	});
	$('select[name="qbo[order_type]"]').on('change', function() {
		$('[data-dependency]').each(function(){
			var dependency 	= $(this).data('dependency');
			
			if(dependency == 'qbo[order_type]'){//(this).attr('name')){
				 var value 		= $(this).data('value');
				 var c_value 	= $('[name="' + dependency + '"]').val();
				 
				 if(value == c_value){
					$(this).show('slow');
				}else{
					$(this).hide('slow');
				}
			}
		});
  		
	});
	$( '#qbo-order-data' )
	.on( 'click', 'a.transfer-to.button', function() {
		$( '#qbo-order-data' ).block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_order_meta_box.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});

		var data = {
			action:    'cp_transfer_order',
			post_id:   cp_order_meta_box.post_id,
			security:  cp_order_meta_box.transfer_order_nonce,
		};

		$.post( cp_order_meta_box.ajax_url, data, function( response ) {
			$( '#qbo-order-data' ).unblock();
			location.reload;
		});

		
		return false;
	});
	$('a.button.transfer').on('click', function(e) {
		var url 	= $(this).attr('href');
		var vars 	= [], hash;
		$this 		= $(this);
		var parent 	= $(this).parent().parent().parent(); 
		    var q 	= url.split('?')[1];
		    if(	q != undefined	){
		        q = q.split('&');
		        for(var i = 0; i < q.length; i++){
		            hash = q[i].split('=');
		            vars.push(hash[1]);
		            vars[hash[0]] = hash[1];
		        }
		}
		
		parent.toggleClass("queued");
		var data = {
			action:	    vars['action'],
			order_id:   vars['order_id'],
			security:	vars['_wpnonce'],
		};
		$.post( cp_order_meta_box.ajax_url, data, function( response ) {
			console.log(response); 
			parent.toggleClass("queued");
			window.location.reload();
		});
		//	
		return false;
	});
	$( '#qbo-order-data' )
	.on( 'change', '#qb_resend', function() {
		
		if ($(this).attr("checked")) {
			$('a.transfer-to').removeClass('hide');
			$('a.transfer-to').show('slow');
			
		}else{
			$('a.transfer-to').hide('slow');
			
		}
		return false;
	});
	
});