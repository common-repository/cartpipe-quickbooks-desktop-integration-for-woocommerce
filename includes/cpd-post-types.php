<?php 
add_action( 'init','cpd_register_taxonomies', 0 );
function cpd_register_taxonomies(){
	$labels = array(
		'name'              => _x( 'QuickBooks Status', 'cartpipe' ),
		'singular_name'     => _x( 'QuickBooks Status','cartpipe' ),
		'search_items'      => __( 'Search QuickBooks Statuses', 'cartpipe' ),
		'all_items'         => __( 'All QuickBooks Statuses', 'cartpipe' ),
		'parent_item'       => __( 'Parent QuickBooks Status', 'cartpipe' ),
		'parent_item_colon' => __( 'Parent QuickBooks Status:', 'cartpipe' ),
		'edit_item'         => __( 'Edit QuickBooks Status', 'cartpipe' ),
		'update_item'       => __( 'Update QuickBooks Status', 'cartpipe' ),
		'add_new_item'      => __( 'Add New QuickBooks Status', 'cartpipe' ),
		'new_item_name'     => __( 'New QuickBooks Status', 'cartpipe' ),
		'menu_name'         => __( 'QuickBooks Status', 'cartpipe' ),
	);

	$args = array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'qb_order_status' ),
	);
	//register_taxonomy( 'qb_order_status', array( 'shop_order' ), $args );
	$labels = array(
		'name'              => _x( 'QuickBooks Status', 'cartpipe' ),
		'singular_name'     => _x( 'QuickBooks Status','cartpipe' ),
		'search_items'      => __( 'Search QuickBooks Statuses', 'cartpipe' ),
		'all_items'         => __( 'All QuickBooks Statuses', 'cartpipe' ),
		'parent_item'       => __( 'Parent QuickBooks Status', 'cartpipe' ),
		'parent_item_colon' => __( 'Parent QuickBooks Status:', 'cartpipe' ),
		'edit_item'         => __( 'Edit QuickBooks Status', 'cartpipe' ),
		'update_item'       => __( 'Update QuickBooks Status', 'cartpipe' ),
		'add_new_item'      => __( 'Add New QuickBooks Status', 'cartpipe' ),
		'new_item_name'     => __( 'New QuickBooks Status', 'cartpipe' ),
		'menu_name'         => __( 'QuickBooks Status', 'cartpipe' ),
	);

	$args = array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_ui'           => false,
		'show_admin_column' => false,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'qb_product_status' ),
	);
	register_taxonomy( 'qb_product_status', array( 'product' ), $args );
	$actions = array(
			'qb_order_status'=> array(
				'In QuickBooks',
				'Not In QuickBooks',
				'Failed'
			),
			'qb_product_status'=> array(
				'Synced',
			)
	);
	foreach($actions as $tax=>$action){
		foreach($action as $term){
			if(!term_exists( $term, $tax )){
				 wp_insert_term( $term, $tax );
			}	
		}
	}
	// $labels = array(
		// 'name'              => _x( 'Sync Status', 'cartpipe' ),
		// 'singular_name'     => _x( 'Sync Status','cartpipe' ),
		// 'search_items'      => __( 'Search Sync Statuses', 'cartpipe' ),
		// 'all_items'         => __( 'All Sync Statuses', 'cartpipe' ),
		// 'parent_item'       => __( 'Parent Sync Status', 'cartpipe' ),
		// 'parent_item_colon' => __( 'Parent Sync Status:', 'cartpipe' ),
		// 'edit_item'         => __( 'Edit Sync Status', 'cartpipe' ),
		// 'update_item'       => __( 'Update Sync Status', 'cartpipe' ),
		// 'add_new_item'      => __( 'Add New Sync Status', 'cartpipe' ),
		// 'new_item_name'     => __( 'New Sync Status', 'cartpipe' ),
		// 'menu_name'         => __( 'Sync Status', 'cartpipe' ),
	// );

	// $args = array(
		// 'hierarchical'      => true,
		// 'labels'            => $labels,
		// 'show_ui'           => true,
		// 'show_admin_column' => true,
		// 'query_var'         => true,
		// 'rewrite'           => array( 'slug' => 'cpd_sync_status' ),
	// );
	// register_taxonomy( 'cpd_sync_status', array( 'product' ), $args );
	// $actions = array(
			// 'cpd_sync_status'=> array(
				// 'Sync',
				// 'Don\'t Sync',
			// )
	// );
	// foreach($actions as $tax=>$action){
		// foreach($action as $term){
			// if(!term_exists( $term, $tax )){
				 // wp_insert_term( $term, $tax );
			// }	
		// }
	// }
	}
	function restrict_queue_by_queue_status() {
		global $typenow;
		
		$post_types = array(
				'product'	=> array('qb_product_status'),
				'shop_order'=> 'qb_order_status',
				
		); // change 
		foreach($post_types as $post_type => $taxonomy){
			if ($typenow == $post_type) {
				if(is_array($taxonomy)){
					foreach ($taxonomy as $tax) {
						$selected = isset($_GET[$tax]) ? $_GET[$tax] : '';
						$info_taxonomy = get_taxonomy($tax);
						//$label = $post_type == 'product' ? __("Not Synced") : __("All {$info_taxonomy->label}");
						wp_dropdown_categories(array(
							'show_option_all' => __("All {$info_taxonomy->label}"),
							'taxonomy' => $tax,
							'name' => $tax,
							'orderby' => 'name',
							'selected' => $selected,
							'show_count' => false,
							'hide_empty' => false,
						));		
					}
				}else{
					$selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
					$info_taxonomy = get_taxonomy($taxonomy);
					
					wp_dropdown_categories(array(
						'show_option_all' => __("All {$info_taxonomy->label}"),
						'taxonomy' => $taxonomy,
						'name' => $taxonomy,
						'orderby' => 'name',
						'selected' => $selected,
						'show_count' => false,
						'hide_empty' => false,
					));
				}		
				
			};
		}
	}

	//add_action('restrict_manage_posts', 'restrict_queue_by_queue_status');

	function convert_id_to_term_in_queue_query($query) {
		global $pagenow;
		$post_types = array(
				'product'	=> 'qb_product_status',
				'shop_order'=> 'qb_order_status',
		); // change HERE
		
		$q_vars = &$query->query_vars;
		
		foreach($post_types as $post_type => $taxonomy){
			
			if(is_array($taxonomy)){
				foreach($taxonomy as $tax){
					if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($_GET[$tax]) && is_numeric($_GET[$tax]) && $_GET[$tax] != 0) {
						$term = get_term_by('id', $_GET[$tax], $tax);
						$q_vars[$tax] = $term->slug;
					}
				}
			}else{
				
				if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($_GET[$taxonomy]) && is_numeric($_GET[$taxonomy]) && $_GET[$taxonomy] != 0) {
					
					$term = get_term_by('id', $_GET[$taxonomy], $taxonomy);
					
					$q_vars[$taxonomy] = $term->slug;
				}
			}
		}
	}

	add_filter('parse_query', 'convert_id_to_term_in_queue_query', 99);