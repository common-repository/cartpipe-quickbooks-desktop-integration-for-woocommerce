jQuery( function ( $ ) {
	$( 'a.button.refresh' )
	.on( 'click', function() {
		
		 $('.wrap.qbo').block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		
		var data = {
			action:    'cp_refresh_' + $(this).data('type'),
			security:  cp_options.refresh_nonce,
		};
// 
		$.post( cp_options.ajax_url, data, function( response ) {
				$( '.wrap.qbo' ).unblock();	  
				location.reload();
		});

		return false;

	});
	$( 'a.button.import' )
	.on( 'click', function() {
		
		 $('.wrap.qbo').block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		
		var data = {
			action:    'cp_import',
			security:  cp_options.refresh_nonce,
		};
// 
		$.post( cp_options.ajax_url, data, function( response ) {
				$( '.wrap.qbo' ).unblock();	  
				location.reload();
		});

		return false;

	});
	$( 'a.button.sync' )
	.on( 'click', function() {
		
		var data = {
			action:    'cp_sync_start',
			security:  cp_options.refresh_nonce,
		};
		$.post( cp_options.ajax_url, data, function( response ) {
			
			console.log(response);
		});
		
		return false;
	});
	$('a.message-trigger').on('click', function(){
		$('.product-import-result').toggle('slow');
		
	});
});