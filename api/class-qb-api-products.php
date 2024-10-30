<?php
/**
 * Cartpipe API Products Class
 *
 * Handles requests to the /qb/items endpoint
 *
 * @author      Cartpipe
 * @category    API
 * @package     Cartpipe-QuickBooks-Api/API
 * @since       1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class QB_API_Products extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/qb';

	/**
	 * Register the routes for this class
	 *
	 * @since 1.0
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET /products/<id>
		$routes[ $this->base . '/items/sync/set' ] = array(
			array( array( $this, 'update_quickbooks_info' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
		);
		
		$routes[ $this->base . '/items/stock/set' ] = array(
			array( array( $this, 'update_stock' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
		);
		$routes[ $this->base . '/items/price/set' ] = array(
			array( array( $this, 'update_price' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
		);
        $routes[ $this->base . '/items/stock_price/set' ] = array(
            array( array( $this, 'update_stock_price' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
        );
		# GET /products/count
		$routes[ $this->base . '/items/synced/get'] = array(
			array( array( $this, 'get_synced_products' ), WC_API_Server::READABLE ),
		);
		
		$routes[ $this->base . '/items/all/get'] = array(
			array( array( $this, 'get_all_products' ), WC_API_Server::READABLE ),
		);
		$routes[ $this->base . '/items/add'] = array(
			array( array( $this, 'add_qb_item' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
		);
		return $routes;
	}

	
	
	public function get_unsynced_products( $fields = 'sku', $type = null, $filter = array(), $page = 1 ){
		if ( ! empty( $type ) ) {
			$filter['type'] = $type;
		}
		$total = 0;

		$query = $this->query_unsynced_products( $filter );

		$products = array();
		
		$products_per_page 	= CPD()->settings->posts_per_page;//get_option('posts_per_page');
		$totals_query 		= $this->query_total_unsynced_products( $filter );
		$total 				= (int) $totals_query->found_posts;
		$number_of_pages 	= $query->found_posts / $products_per_page; 
		
		foreach ( $query->posts as $product_id ) {

			if ( ! $this->is_readable( $product_id ) ) {
				continue;
			}
			//if( !$this->has_qb_data( $product_id )){
				$product 		= $this->get_product( $product_id, $fields );
				$variation_data = false;
				if(isset($product['variations']) && sizeof($product['variations'] > 0)){
				$variations = $product['variations'];
				//$total = $total + sizeof( $product['variations'] );	
				foreach($variations as $variation){
					$variation_data[] = 
					array(
						'sku'			=> $variation['sku'],
						'created'		=> $variation['created_at'],
						'regular_price' => $variation['regular_price'] > 0 ? $variation['regular_price'] : $variation['price'],
						'price' 		=> $variation['price'],
						'sale_price' 	=> $variation['sale_price'],
						'managing_stock'=> $variation['managing_stock'],
						'qty'			=> $variation['stock_quantity'],
						'type'			=> 'variation',
					);
					$total++;
				};
			}
			$products[] = 
			array(
					'sku'			=> $product['sku'],
					'name'			=> $product['title'],
					'created'		=> $product['created_at'],
					'regular_price' => $product['regular_price'] > 0 ? $product['regular_price'] : $product['price'],
					'sale_price' 	=> $product['sale_price'],
					'price' 		=> $product['price'],
					'qty'			=> $product['stock_quantity'],
					'type'			=> $product['type'],
					'variations'	=> $variation_data,
					'managing_stock'=> $product['managing_stock'],
				);
			//}
		}

		$this->server->add_pagination_headers( $query );
		
		return  array('products' => $products, 'num_pages' => ceil( $number_of_pages ), 'total_products' => (int) $total );
	}
	public function get_synced_products( $fields = 'sku', $type = null, $filter = array(), $page = 1 ){
		if ( ! empty( $type ) ) {
			$filter['type'] = $type;
		}

		$filter['page'] = $page;

		$query = $this->query_synced_products( $filter );

		$products = array();
		
		$products_per_page 	= CPD()->settings->posts_per_page;//get_option('posts_per_page');
		
		$number_of_pages 	= $query->found_posts / $products_per_page; 
		
		foreach ( $query->posts as $product_id ) {

			if ( ! $this->is_readable( $product_id ) ) {
				continue;
			}
			//if( !$this->has_qb_data( $product_id )){
				$product 		= current( $this->get_product( $product_id, $fields ) );
                if($product){
				$variation_data = false;
				if(isset($product['variations']) && sizeof($product['variations'] > 0)){
				    $variations = $product['variations'];
				    foreach($variations as $variation){
					   $variation_data[] = 
					   array(
    						'sku'			=> $variation['sku'],
    						'created'		=> $variation['created_at'],
    						//'updated'		=> $product['updated'],
    						'regular_price' => $variation['regular_price'] > 0 ? $variation['regular_price'] : $variation['price'],
    						'price' 		=> $variation['price'],
    						'qty'			=> $variation['stock_quantity'],
    						'type'			=> 'variation',
    				   );
    				};
			     }
    			$products[] = 
    			array(
    					'sku'			=> $product['sku'],
    					'name'			=> $product['title'],
    					'created'		=> $product['created_at'],
    					//'updated'		=> $product['updated'],
    					'regular_price' => ( $product['regular_price'] > 0 ) ? $product['regular_price'] :$product['price'],
    					'price' 		=> $product['price'],
    					'qty'			=> $product['stock_quantity'],
    					'type'			=> $product['type'],
    					'variations'	=> $variation_data,
    					'managing_stock'=> $product['managing_stock'],
    			);
    		}
		}

		$this->server->add_pagination_headers( $query );
		
		return  array('products' => $products, 'total_products' => (int) $query->found_posts , 'num_pages' => ceil( $number_of_pages ) );
	}
	public function get_all_products( $fields = 'sku', $type = null, $filter = array(), $page = 1 ){
		if ( ! empty( $type ) ) {
			$filter['type'] = $type;
		}

		$filter['page'] = $page;

		$query = $this->query_products( $filter );

		$products = array();
		
		$products_per_page 	= CPD()->settings->posts_per_page;//get_option('posts_per_page');
		
		$number_of_pages 	= $query->found_posts / $products_per_page; 
		
		foreach ( $query->posts as $product_id ) {

			if ( ! $this->is_readable( $product_id ) ) {
				continue;
			}
			//if( !$this->has_qb_data( $product_id )){
				$product 		= current( $this->get_product( $product_id, $fields ) );
				$variation_data = false;
				if(isset($product['variations']) && sizeof($product['variations'] > 0)){
				$variations = $product['variations'];
				foreach($variations as $variation){
				   // error_log(print_r($variation, true), 3, plugin_dir_path(__FILE__) . "/products.log");
					$attributes = $variation['attributes'];
                    $labels = array();
                    if(sizeof($attributes) > 0){
                        foreach($attributes as $attribute){
                            if(is_array($attribute)){
                                $labels[] = sprintf('%s : %s', $attribute['name'], $attribute['option']);
                            }
                        }
                    }
					
					$products[] = 
					array(
						'sku'			=> $variation['sku'],
						'name'            => sprintf('%s - %s', $product['title'], implode(',', $labels)),
						'created'		=> $variation['created_at'],
						'regular_price' => $variation['regular_price'],
						'price' 		=> $variation['price'],
						'qty'			=> $variation['stock_quantity'],
						'type'			=> 'variation',
					);
				};
            }
                
			$products[] = 
			array(
					'sku'			=> $product['sku'],
					'name'			=> $product['title'],
					'created'		=> $product['created_at'],
					'regular_price' => $product['regular_price'],
					'price' 		=> $product['price'],
					'qty'			=> $product['stock_quantity'],
					'type'			=> $product['type'],
					//'variations'	=> $variation_data,
					'managing_stock'=> $product['managing_stock'],
				);
			//}
		}

		$this->server->add_pagination_headers( $query );
		
		return  array('products' => $products, 'total_products' => (int) $query->found_posts , 'num_pages' => ceil( $number_of_pages ) );
	}
	
	/**
	 * Get the product for the given ID
	 *
	 * @since 1.0
	 * @param int $id the product ID
	 * @param string $fields
	 * @return array
	 */
	public function get_product( $id, $fields = null ) {

		$id = $this->validate_request( $id, 'product', 'read' );

		if ( is_wp_error( $id ) )
			return $id;
        $sync_status = get_post_meta( $id, '_quickbooks_sync', true);
        if( !$sync_status ){
            $sync_status = CPD()->settings->default_sync == "yes" ? "yes" : "no";
        }
        if($sync_status == "yes" ){
    		$product = get_product( $id );
    
    		// add data that applies to every product type
    		$product_data = $this->get_product_data( $product );
    
    		// add variations to variable products
    		if ( $product->is_type( 'variable' ) && $product->has_child() ) {
    
    			$product_data['variations'] = $this->get_variation_data( $product );
    		}
    
    		// add the parent product data to an individual variation
    		if ( $product->is_type( 'variation' ) ) {
    
    			$product_data['parent'] = $this->get_product_data( $product->parent );
    		}
    
    		return array( 'product' => apply_filters( 'wc_api_product_response', $product_data, $product, $fields, $this->server ) );
        }else{
            return false;
        }
       }

	

	/**
	 * Update item
	 *
	 * 
	 * @param int $id the product ID
	 * @param array $data
	 * @return array
	 */
	public function update_quickbooks_info( $data ) {
       	$id = $this->get_product_by_sku( $data['FullName'] );
    		$id = $this->validate_request( $id->id, 'product', 'edit' );
    
    		if ( is_wp_error( $id ) )
    			return $id;
    
    		$product = get_product( $id );
    		$allowed_keys = array(
    								'ListID', 
    								'EditSequence', 
    								'FullName', 
    								'Price', 
    								'OnHandQty'
    							);
    		foreach($data as $key=>$value){
    			if(!in_array($key, $allowed_keys)){
    				unset($data[$key]);
    			}
    		}
    		if ( ! empty( $data ) ) {
    
    			update_post_meta( $id, '_quickbooks_data' , wc_clean( maybe_serialize( $data  ) ) );
    
    		}
    		
    		
    		if ( is_wp_error( $id ) )
    			return $id;
    
    		return $product;
        //}
	}

	public function get_quickbooks_info( $id ) {


		$id = $this->validate_request( $id, 'product', 'read' );

		if ( is_wp_error( $id ) )
			return $id;
		
		$qb_data = get_post_meta( $id, '_quickbooks_data' , true );
		$qb_data = maybe_unserialize( $qb_data );
		$return_keys = array(
								'ListID', 
								'EditSequence', 
								'FullName', 
								'Price', 
								'OnHandQty'
							);
		foreach($qb_data as $key=>$value){
			if(!in_array($key, $return_keys)){
				unset($qb_data[$key]);
			}
		}
		if ( is_wp_error( $id ) )
			return $id;

		return $qb_data; 
	}

	
	private function query_total_unsynced_products( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      	=> 'ids',
			'post_type'   	=> array('product'),
			'post_status' 	=> 'publish',
			'posts_per_page'=> -1,
			'meta_query' => array(
				'relation' => 'AND',  
				array(
					'key'		=>'_quickbooks_data',
					'compare' 	=> 'NOT EXISTS',
				),
			),
			
		);
		return new WP_Query( $query_args );
	}
	private function query_unsynced_products( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'product',
			'post_status' => 'publish',
			'post_parent' => 0,
			'meta_query' => array(
                'relation' => 'AND',  
                array(
                    'key'       =>'_quickbooks_sync',
                    'compare'   => 'NOT EXISTS',
                ),
            ),
		);

		if ( ! empty( $args['type'] ) ) {

			$types = explode( ',', $args['type'] );

			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $types,
				),
			);

			unset( $args['type'] );
		}

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}
	private function query_products( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'product',
			'post_status' => 'publish',
			'posts_per_page'=>CPD()->settings->posts_per_page,
			'meta_query' => array(
                'relation' => 'AND',  
                array(
                    'key'       =>'_quickbooks_sync',
                    'compare'   => 'EXISTS',
                ),
            ),
		);
		$query_args = $this->merge_query_args( $query_args, $args );
		return new WP_Query( $query_args );
	}
    private function query_product_variations( $args ) {

        // set base query arguments
        $query_args = array(
            'fields'      => 'ids',
            'post_type'   => 'product_variation',
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',  
                array(
                    'key'       =>'_quickbooks_sync',
                    'compare'   => 'EXISTS',
                ),
            ),
        );
        $query_args = $this->merge_query_args( $query_args, $args );
        return new WP_Query( $query_args );
    }
    
	private function query_synced_products( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'product',
			'post_status' => 'publish',
			// 'post_parent' => 0,
			'meta_query' => array(
				'relation' => 'AND',  
				array(
					'key'		=>'_quickbooks_sync',
					'compare' 	=> 'EXISTS',
				),
			),
				
		);

		if ( ! empty( $args['type'] ) ) {

			$types = explode( ',', $args['type'] );

			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $types,
				),
			);

			unset( $args['type'] );
		}

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}
	/**
	 * Get standard product data that applies to every product type
	 *
	 * @since 2.1
	 * @param WC_Product $product
	 * @return array
	 */
	private function get_product_data( $product ) {

		return array(
			'title'              => $product->get_title(),
			'id'                 => (int) $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id,
			'created_at'         => $this->server->format_datetime( $product->get_post_data()->post_date_gmt ),
			'updated_at'         => $this->server->format_datetime( $product->get_post_data()->post_modified_gmt ),
			'type'               => $product->product_type,
			'status'             => $product->get_post_data()->post_status,
			'downloadable'       => $product->is_downloadable(),
			'virtual'            => $product->is_virtual(),
			'permalink'          => $product->get_permalink(),
			'sku'                => $product->get_sku(),
			'price'              => wc_format_decimal( $product->get_price(), 2 ),
			'regular_price'      => wc_format_decimal( $product->get_regular_price(), 2 ),
			'sale_price'         => $product->get_sale_price() ? wc_format_decimal( $product->get_sale_price(), 2 ) : null,
			'price_html'         => $product->get_price_html(),
			'taxable'            => $product->is_taxable(),
			'tax_status'         => $product->get_tax_status(),
			'tax_class'          => $product->get_tax_class(),
			'managing_stock'     => $product->managing_stock(),
			'stock_quantity'     => (int) $product->get_stock_quantity(),
			'in_stock'           => $product->is_in_stock(),
			'backorders_allowed' => $product->backorders_allowed(),
			'backordered'        => $product->is_on_backorder(),
			'sold_individually'  => $product->is_sold_individually(),
			'purchaseable'       => $product->is_purchasable(),
			'featured'           => $product->is_featured(),
			'visible'            => $product->is_visible(),
			'catalog_visibility' => $product->visibility,
			'on_sale'            => $product->is_on_sale(),
			'weight'             => $product->get_weight() ? wc_format_decimal( $product->get_weight(), 2 ) : null,
			'dimensions'         => array(
				'length' => $product->length,
				'width'  => $product->width,
				'height' => $product->height,
				'unit'   => get_option( 'woocommerce_dimension_unit' ),
			),
			'shipping_required'  => $product->needs_shipping(),
			'shipping_taxable'   => $product->is_shipping_taxable(),
			'shipping_class'     => $product->get_shipping_class(),
			'shipping_class_id'  => ( 0 !== $product->get_shipping_class_id() ) ? $product->get_shipping_class_id() : null,
			'description'        => apply_filters( 'the_content', $product->get_post_data()->post_content ),
			'short_description'  => apply_filters( 'woocommerce_short_description', $product->get_post_data()->post_excerpt ),
			'reviews_allowed'    => ( 'open' === $product->get_post_data()->comment_status ),
			'average_rating'     => wc_format_decimal( $product->get_average_rating(), 2 ),
			'rating_count'       => (int) $product->get_rating_count(),
			'related_ids'        => array_map( 'absint', array_values( $product->get_related() ) ),
			'upsell_ids'         => array_map( 'absint', $product->get_upsells() ),
			'cross_sell_ids'     => array_map( 'absint', $product->get_cross_sells() ),
			'categories'         => wp_get_post_terms( $product->id, 'product_cat', array( 'fields' => 'names' ) ),
			'tags'               => wp_get_post_terms( $product->id, 'product_tag', array( 'fields' => 'names' ) ),
			'images'             => $this->get_images( $product ),
			'featured_src'       => wp_get_attachment_url( get_post_thumbnail_id( $product->is_type( 'variation' ) ? $product->variation_id : $product->id ) ),
			'attributes'         => $this->get_attributes( $product ),
			'downloads'          => $this->get_downloads( $product ),
			'download_limit'     => (int) $product->download_limit,
			'download_expiry'    => (int) $product->download_expiry,
			'download_type'      => $product->download_type,
			'purchase_note'      => apply_filters( 'the_content', $product->purchase_note ),
			'total_sales'        => metadata_exists( 'post', $product->id, 'total_sales' ) ? (int) get_post_meta( $product->id, 'total_sales', true ) : 0,
			'variations'         => array(),
			'parent'             => array(),
		);
	}

	/**
	 * Get an individual variation's data
	 *
	 * @since 1.0
	 * @param WC_Product $product
	 * @return array
	 */
	private function get_variation_data( $product ) {

		$variations = array();

		foreach ( $product->get_children() as $child_id ) {

			$variation = $product->get_child( $child_id );

			if ( ! $variation->exists() )
				continue;

			$variations[] = array(
				'id'                => $variation->get_variation_id(),
				'created_at'        => $this->server->format_datetime( $variation->get_post_data()->post_date_gmt ),
				'updated_at'        => $this->server->format_datetime( $variation->get_post_data()->post_modified_gmt ),
				'downloadable'      => $variation->is_downloadable(),
				'virtual'           => $variation->is_virtual(),
				'permalink'         => $variation->get_permalink(),
				'sku'               => $variation->get_sku(),
				'price'             => wc_format_decimal( $variation->get_price(), 2 ),
				'regular_price'     => wc_format_decimal( $variation->get_regular_price(), 2 ),
				'sale_price'        => $variation->get_sale_price() ? wc_format_decimal( $variation->get_sale_price(), 2 ) : null,
				'taxable'           => $variation->is_taxable(),
				'tax_status'        => $variation->get_tax_status(),
				'tax_class'         => $variation->get_tax_class(),
				'managing_stock'    => $variation->managing_stock(),
				'stock_quantity'    => (int) $variation->get_stock_quantity(),
				'in_stock'          => $variation->is_in_stock(),
				'backordered'       => $variation->is_on_backorder(),
				'purchaseable'      => $variation->is_purchasable(),
				'visible'           => $variation->variation_is_visible(),
				'on_sale'           => $variation->is_on_sale(),
				'weight'            => $variation->get_weight() ? wc_format_decimal( $variation->get_weight(), 2 ) : null,
				'dimensions'        => array(
					'length' => $variation->length,
					'width'  => $variation->width,
					'height' => $variation->height,
					'unit'   => get_option( 'woocommerce_dimension_unit' ),
				),
				'shipping_class'    => $variation->get_shipping_class(),
				'shipping_class_id' => ( 0 !== $variation->get_shipping_class_id() ) ? $variation->get_shipping_class_id() : null,
				'image'             => $this->get_images( $variation ),
				'attributes'        => $this->get_attributes( $variation ),
				'downloads'         => $this->get_downloads( $variation ),
				'download_limit'    => (int) $product->download_limit,
				'download_expiry'   => (int) $product->download_expiry,
			);
		}

		return $variations;
	}

	/**
	 * Get the images for a product or product variation
	 *
	 * @since 1.0
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	private function get_images( $product ) {

		$images = $attachment_ids = array();

		if ( $product->is_type( 'variation' ) ) {

			if ( has_post_thumbnail( $product->get_variation_id() ) ) {

				// add variation image if set
				$attachment_ids[] = get_post_thumbnail_id( $product->get_variation_id() );

			} elseif ( has_post_thumbnail( $product->id ) ) {

				// otherwise use the parent product featured image if set
				$attachment_ids[] = get_post_thumbnail_id( $product->id );
			}

		} else {

			// add featured image
			if ( has_post_thumbnail( $product->id ) ) {
				$attachment_ids[] = get_post_thumbnail_id( $product->id );
			}

			// add gallery images
			$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_attachment_ids() );
		}

		// build image data
		foreach ( $attachment_ids as $position => $attachment_id ) {

			$attachment_post = get_post( $attachment_id );

			if ( is_null( $attachment_post ) )
				continue;

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( ! is_array( $attachment ) )
				continue;

			$images[] = array(
				'id'         => (int) $attachment_id,
				'created_at' => $this->server->format_datetime( $attachment_post->post_date_gmt ),
				'updated_at' => $this->server->format_datetime( $attachment_post->post_modified_gmt ),
				'src'        => current( $attachment ),
				'title'      => get_the_title( $attachment_id ),
				'alt'        => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'   => $position,
			);
		}

		// set a placeholder image if the product has no images set
		if ( empty( $images ) ) {

			$images[] = array(
				'id'         => 0,
				'created_at' => $this->server->format_datetime( time() ), // default to now
				'updated_at' => $this->server->format_datetime( time() ),
				'src'        => wc_placeholder_img_src(),
				'title'      => __( 'Placeholder', 'woocommerce' ),
				'alt'        => __( 'Placeholder', 'woocommerce' ),
				'position'   => 0,
			);
		}

		return $images;
	}

	/**
	 * Get the attributes for a product or product variation
	 *
	 * @since 1.0
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	private function get_attributes( $product ) {

		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {

			// variation attributes
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {

				// taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
				$attributes[] = array(
					'name'   => ucwords( str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ) ),
					'option' => $attribute,
				);
			}

		} else {

			foreach ( $product->get_attributes() as $attribute ) {

				// taxonomy-based attributes are comma-separated, others are pipe (|) separated
				if ( $attribute['is_taxonomy'] )
					$options = explode( ',', $product->get_attribute( $attribute['name'] ) );
				else
					$options = explode( '|', $product->get_attribute( $attribute['name'] ) );

				$attributes[] = array(
					'name'      => ucwords( str_replace( 'pa_', '', $attribute['name'] ) ),
					'position'  => $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => array_map( 'trim', $options ),
				);
			}
		}

		return $attributes;
	}

	/**
	 * Get the downloads for a product or product variation
	 *
	 * @since 1.0
	 * @param WC_Product|WC_Product_Variation $product
	 * @return array
	 */
	private function get_downloads( $product ) {

		$downloads = array();

		if ( $product->is_downloadable() ) {

			foreach ( $product->get_files() as $file_id => $file ) {

				$downloads[] = array(
					'id'   => $file_id, // do not cast as int as this is a hash
					'name' => $file['name'],
					'file' => $file['file'],
				);
			}
		} 

		return $downloads;
	}
	public function update_price( $data){
		if(isset($data['FullName'])){
			$_product 	= $this->get_products_by_sku( $data['FullName'] );
			if($_products){
				foreach($_products as $_product){
					if(isset( $_product->variation_id) &&  $_product->variation_id > 0){
						$id =  $_product->variation_id;
					}else{
						$id =  $_product->id;
					}
                    $sync_status = get_post_meta($id, '_quickbooks_sync', true);
                    if( !$sync_status ){
                        $sync_status = CPD()->settings->default_sync == "yes" ? "yes" : "no";
                    }
                    if( $sync_status == 'yes' ){
    					if($_product->is_on_sale() ):
                            $old_regular_price = get_post_meta( $id, '_regular_price', true);
                            update_post_meta( $id, '_regular_price', 	$data['SalesPrice'] );
                            update_post_meta( $id, '_quickbooks_pricing_messages', sprintf( 'Price updated from %s to %s', $old_regular_price, $data['SalesPrice']) );
                            update_post_meta( $id, '_quickbooks_last_updated', time() );
                            
    					else:
                            $old_price         = get_post_meta( $id, '_price', true);
     						update_post_meta( $id, '_quickbooks_previous_price', $old_price );
                            update_post_meta( $id, '_quickbooks_last_updated', time() );
                            update_post_meta( $id, '_quickbooks_pricing_messages', sprintf( 'Price updated from %s to %s', $old_price, $data['SalesPrice']) );
                            update_post_meta( $id, '_regular_price', 	$data['SalesPrice'] );
    						update_post_meta( $id, '_price',			$data['SalesPrice'] );
                            
    					endif;
                        if(isset($_product->parent->id)){
                            WC_Product_Variable::sync( $_product->parent->id );   
                        }
                        update_post_meta( $id, '_quickbooks_data', $data);
                        wc_delete_product_transients( $id );
    					wp_set_object_terms(  $id, 'synced', 'qb_status', false );
					}
				}
			}
		}else{
			$skus = array();
			$qtys = array();
			foreach($data as $key=>$value){
				if(isset($value['FullName'])){
					$skus[] 					= $value['FullName'];
					$prices[$value['FullName']] = $value['SalesPrice'];
				}	
			}
			
			$_products = $this->get_products_by_sku( $skus );
			
			if($_products){
				foreach($_products as $_product){
					$price = $prices[$_product->get_sku()];
					if(isset( $_product->variation_id) &&  $_product->variation_id > 0){
						$id =  $_product->variation_id;
					}else{
						$id =  $_product->id;
					}
                    $sync_status = get_post_meta($id, '_quickbooks_sync', true);
                    if( !$sync_status ){
                        $sync_status = CPD()->settings->default_sync == "yes" ? "yes" : "no";
                    }
                    
                    if( $sync_status == 'yes' ){
                         $date =   date( 'F j, Y g:i a', time() );
                        if($_product->is_on_sale() ):
    					    $old_regular_price = get_post_meta( $id, '_regular_price', true);
                            update_post_meta( $id, '_quickbooks_last_updated', $date );
                            update_post_meta( $id, '_quickbooks_pricing', sprintf( 'Price updated from %s to %s on $s', wc_price($old_regular_price), wc_price($price), $date ) );
                            update_post_meta( $id, '_regular_price', $price );
                            
    					else:
                            $old_price         = get_post_meta( $id, '_price', true);
                            update_post_meta( $id, '_quickbooks_last_updated', $date );
                            update_post_meta( $id, '_quickbooks_pricing', sprintf( 'Price updated from %s to %s on %s', wc_price($old_price), wc_price($price), $date ) );
                            update_post_meta( $id, '_regular_price', $price );
    						update_post_meta( $id, '_price', $price );
    					endif;
    					//update_post_meta( $id, '_quickbooks_data', $data);
                        if(isset($_product->parent->id)){
                            WC_Product_Variable::sync( $_product->parent->id );   
                        }
                        update_post_meta( $id, '_quickbooks_data', $data);
                        wc_delete_product_transients( $id );
    					wp_set_object_terms(  $id, 'synced', 'qb_product_status', false );
    				}
    			}
			}
		}
	}
	public function update_stock( $data ){
		if(isset($data['FullName'])){
			$_products = $this->get_products_by_sku( $data['FullName'] );
			if($_products){
				foreach($_products as $_product){
					$qty = $data['QtyOnHand'];
					$old_qty = $_product->get_stock_quantity();
					$_product->set_stock( $qty );
                        
					if(isset( $_product->variation_id) &&  $_product->variation_id > 0){
						$id =  $_product->variation_id;
					}else{
						$id =  $_product->id;
					}
					$date = date( 'F j, Y g:i a', time() );
                    update_post_meta( $id, '_quickbooks_last_updated', $date );
                    update_post_meta( $id, '_quickbooks_stock', sprintf( 'Stock quantity updated from %d to %d on %s', $old_qty, $qty, $date ) );
                    
					update_post_meta( $id, '_quickbooks_data', $data);
					wp_set_object_terms(  $id, 'synced', 'qb_product_status', false );
				}
			}
		}else{
			$skus = array();
			$qtys = array();
			foreach($data as $key=>$value){
				if(isset($value['FullName'])){
					$skus[] = $value['FullName'];
					$qtys[$value['FullName']] = $value['QtyOnHand'];
				}	
			}
			$_products = $this->get_products_by_sku( $skus );
			if($_products){
				foreach($_products as $_product){
				    if(isset( $_product->variation_id) &&  $_product->variation_id > 0){
                        $id =  $_product->variation_id;
                    }else{
                        $id =  $_product->id;
                    }
                    $sync_status = get_post_meta($id, '_quickbooks_sync', true);
                    if( !$sync_status ){
                        $sync_status = CPD()->settings->default_sync == "yes" ? "yes" : "no";
                    }
                    
                    if( $sync_status == 'yes' ){
                   
    					$qty = $qtys[$_product->get_sku()];
    					$old_qty = $_product->get_stock_quantity();
                        
                        $_product->set_stock( $qty );
    					
    					$date = date( 'F j, Y g:i a', time() );
                        update_post_meta( $id, '_quickbooks_last_updated', $date );
                        update_post_meta( $id, '_quickbooks_stock', sprintf( 'Stock quantity updated from %d to %d on %s', $old_qty, $qty, $date ) );
                        update_post_meta( $id, '_quickbooks_data', $data);
    					wp_set_object_terms(  $id, 'synced', 'qb_product_status', false );
				
                    }
                 }
			}
		}
	}
    public function update_stock_price( $data ){
        $this->update_stock( $data );
        $this->update_price( $data );
    }
	public function add_qb_item ( $product ){
		$data = (array)json_decode(stripslashes($product));
		//return ($product);
		if ( ! current_user_can( 'publish_products' ) ) {
			return new WP_Error( 'woocommerce_api_user_cannot_create_product', __( 'You do not have permission to create products', 'woocommerce' ), array( 'status' => 401 ) );
		}

		$data = apply_filters( 'woocommerce_api_create_product_data', $data, $this );
		
		// Check if product title is specified
		if ( ! isset( $data['product']->title ) ) {
			return new WP_Error( 'woocommerce_api_missing_product_title', sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'title' ), array( 'status' => 400 ) );
		}

		// Check product type
		//if ( ! isset( $data['product']->type ) ) {
		$data['product']->type = 'simple';
		//}

		// Validate the product type
		if ( ! in_array( wc_clean( $data['product']->type ), array_keys( wc_get_product_types() ) ) ) {
			return new WP_Error( 'woocommerce_api_invalid_product_type', sprintf( __( 'Invalid product type - the product type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_product_types() ) ) ), array( 'status' => 400 ) );
		}
        $id = $this->get_products_by_sku($data['product']->sku);
        if(!$id){
    		$new_product = array(
    			'post_title'   => wc_clean( $data['product']->title ),
    			'post_status'  => ( isset(  CPD()->settings->import_status ) ? wc_clean( CPD()->settings->import_status ) : 'publish' ),
    			'post_type'    => 'product',
    			'post_excerpt' => ( isset( $data['product']->short_description ) ? wc_clean( $data['product']->short_description ) : '' ),
    			'post_content' => ( isset( $data['product']->description ) ? wc_clean( $data['product']->description ) : '' ),
    			'post_author'  => get_current_user_id(),
    		);
    
    		// Attempts to create the new product
    		$id = wp_insert_post( $new_product, true );
            
    		// Checks for an error in the product creation
    		if ( is_wp_error( $id ) ) {
    			return new WP_Error( 'woocommerce_api_cannot_create_product', $id->get_error_message(), array( 'status' => 400 ) );
    		}
		}
		// Check for featured/gallery images, upload it and set it
		if ( isset( $data['product']->images ) ) {
			$images = $this->save_product_images( $id, $data['product']->images );

			if ( is_wp_error( $images ) ) {
				return $images;
			}
		}

		// Save product meta fields
		
		$meta = $this->save_product_meta( $id, $data );
		if ( is_wp_error( $meta ) ) {
			return $meta;
		}

		

		do_action( 'woocommerce_api_create_product', $id, $data );

		// Clear cache/transients
		wc_delete_product_transients( $id );

		$this->server->send_status( 201 );
		
		return $this->get_product( $id );
	}
	protected function save_product_meta( $id, $data ) {
		// Product Type
		//error_log(print_r($id, true), 3, plugin_dir_path(__FILE__) . "/products.log");
        update_post_meta( $id, '_quickbooks_sync', "yes");
        update_post_meta( $id, '_quickbooks_stock', $data['product']->stock );
        update_post_meta( $id, '_quickbooks_pricing', $data['product']->regular_price );
        wp_set_object_terms( $id, 'synced', 'qb_product_status');
		$product_type = null;
		if ( isset( $data['product']->type ) ) {
			$product_type = wc_clean( $data['product']->type );
			wp_set_object_terms( $id, $product_type, 'product_type' );
		} else {
			$_product_type = get_the_terms( $id, 'product_type' );
			if ( is_array( $_product_type ) ) {
				$_product_type = current( $_product_type );
				$product_type  = $_product_type->slug;
			}
		}

		// Virtual
		if ( isset( $data['product']->virtual ) ) {
			update_post_meta( $id, '_virtual', ( true === $data['product']->virtual ) ? 'yes' : 'no' );
		}

		// Tax status
		if ( isset( $data['product']->tax_status ) ) {
			update_post_meta( $id, '_tax_status', wc_clean( $data['product']->tax_status ) );
		}

		// Tax Class
		if ( isset( $data['product']->tax_class ) ) {
			update_post_meta( $id, '_tax_class', wc_clean( $data['product']->tax_class ) );
		}

		// Catalog Visibility
		if ( isset( $data['product']->catalog_visibility ) ) {
			update_post_meta( $id, '_visibility', wc_clean( $data['product']->catalog_visibility ) );
		}

		// Purchase Note
		if ( isset( $data['product']->purchase_note) ) {
			update_post_meta( $id, '_purchase_note', wc_clean( $data['product']->purchase_note ) );
		}

		// Featured Product
		if ( isset( $data['product']->featured ) ) {
			update_post_meta( $id, '_featured', ( true === $data['product']->featured ) ? 'yes' : 'no' );
		}

		// Shipping data
		//$this->save_product_shipping_data( $id, $data );

		// SKU
		if ( isset( $data['product']->sku ) ) {
			$sku     = get_post_meta( $id, '_sku', true );
			$new_sku = wc_clean( $data['product']->sku );

			if ( '' == $new_sku ) {
				update_post_meta( $id, '_sku', '' );
			} elseif ( $new_sku !== $sku ) {
				if ( ! empty( $new_sku ) ) {
					$unique_sku = wc_product_has_unique_sku( $id, $new_sku );
					if ( ! $unique_sku ) {
						return new WP_Error( 'woocommerce_api_product_sku_already_exists', __( 'The SKU already exists on another product', 'woocommerce' ), array( 'status' => 400 ) );
					} else {
						update_post_meta( $id, '_sku', $new_sku );
					}
				} else {
					update_post_meta( $id, '_sku', '' );
				}
			}
		}

		// Attributes
		if ( isset( $data['attributes'] ) ) {
			$attributes = array();

			foreach ( $data['attributes'] as $attribute ) {
				$is_taxonomy = 0;

				if ( ! isset( $attribute['name'] ) ) {
					continue;
				}

				$taxonomy = $this->get_attribute_taxonomy_by_label( $attribute['name'] );
				if ( $taxonomy ) {
					$is_taxonomy = 1;
				}

				if ( $is_taxonomy ) {

					if ( isset( $attribute['options'] ) ) {
						// Select based attributes - Format values (posted values are slugs)
						if ( is_array( $attribute['options'] ) ) {
							$values = array_map( 'sanitize_title', $attribute['options'] );

						// Text based attributes - Posted values are term names - don't change to slugs
						} else {
							$values = array_map( 'stripslashes', array_map( 'strip_tags', explode( WC_DELIMITER, $attribute['options'] ) ) );
						}

						$values = array_filter( $values, 'strlen' );
					} else {
						$values = array();
					}

					// Update post terms
					if ( taxonomy_exists( $taxonomy ) ) {
						wp_set_object_terms( $id, $values, $taxonomy );
					}

					if ( $values ) {
						// Add attribute to array, but don't set values
						$attributes[ $taxonomy ] = array(
							'name'         => $taxonomy,
							'value'        => '',
							'position'     => isset( $attribute['position'] ) ? absint( $attribute['position'] ) : 0,
							'is_visible'   => ( isset( $attribute['position'] ) && $attribute['position'] ) ? 1 : 0,
							'is_variation' => ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0,
							'is_taxonomy'  => $is_taxonomy
						);
					}

				} elseif ( isset( $attribute['options'] ) ) {
					// Array based
					if ( is_array( $attribute['options'] ) ) {
						$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'wc_clean', $attribute['options'] ) );

					// Text based, separate by pipe
					} else {
						$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'wc_clean', explode( WC_DELIMITER, $attribute['options'] ) ) );
					}

					// Custom attribute - Add attribute to array and set the values
					$attributes[ sanitize_title( $attribute['name'] ) ] = array(
						'name'         => wc_clean( $attribute['name'] ),
						'value'        => $values,
						'position'     => isset( $attribute['position'] ) ? absint( $attribute['position'] ) : 0,
						'is_visible'   => ( isset( $attribute['position'] ) && $attribute['position'] ) ? 1 : 0,
						'is_variation' => ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0,
						'is_taxonomy'  => $is_taxonomy
					);
				}
			}

			if ( ! function_exists( 'attributes_cmp' ) ) {
				function attributes_cmp( $a, $b ) {
					if ( $a['position'] == $b['position'] ) {
						return 0;
					}

					return ( $a['position'] < $b['position'] ) ? -1 : 1;
				}
			}
			uasort( $attributes, 'attributes_cmp' );

			update_post_meta( $id, '_product_attributes', $attributes );
		}

		// Sales and prices
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) ) {

			// Variable and grouped products have no prices
			update_post_meta( $id, '_regular_price', '' );
			update_post_meta( $id, '_sale_price', '' );
			update_post_meta( $id, '_sale_price_dates_from', '' );
			update_post_meta( $id, '_sale_price_dates_to', '' );
			update_post_meta( $id, '_price', '' );

		} else {

			// Regular Price
			if ( isset( $data['product']->regular_price ) ) {
				$regular_price = ( '' === $data['product']->regular_price ) ? '' : wc_format_decimal( $data['product']->regular_price );
				update_post_meta( $id, '_regular_price', $regular_price );
			} else {
				$regular_price = get_post_meta( $id, '_regular_price', true );
			}

			// Sale Price
			if ( isset( $data['product']->sale_price ) ) {
				$sale_price = ( '' === $data['product']->sale_price ) ? '' : wc_format_decimal( $data['product']->sale_price );
				update_post_meta( $id, '_sale_price', $sale_price );
			} else {
				$sale_price = get_post_meta( $id, '_sale_price', true );
			}

			$date_from = isset( $data['product']->sale_price_dates_from ) ? $data['product']->sale_price_dates_from : get_post_meta( $id, '_sale_price_dates_from', true );
			$date_to   = isset( $data['product']->sale_price_dates_to ) ? $data['product']->sale_price_dates_to : get_post_meta( $id, '_sale_price_dates_to', true );

			// Dates
			if ( $date_from ) {
				update_post_meta( $id, '_sale_price_dates_from', strtotime( $date_from ) );
			} else {
				update_post_meta( $id, '_sale_price_dates_from', '' );
			}

			if ( $date_to ) {
				update_post_meta( $id, '_sale_price_dates_to', strtotime( $date_to ) );
			} else {
				update_post_meta( $id, '_sale_price_dates_to', '' );
			}

			if ( $date_to && ! $date_from ) {
				update_post_meta( $id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );
			}

			// Update price if on sale
			if ( '' !== $sale_price && '' == $date_to && '' == $date_from ) {
				update_post_meta( $id, '_price', wc_format_decimal( $sale_price ) );
			} else {
				update_post_meta( $id, '_price', $regular_price );
			}

			if ( '' !== $sale_price && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $id, '_price', wc_format_decimal( $sale_price ) );
			}

			if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $id, '_price', $regular_price );
				update_post_meta( $id, '_sale_price_dates_from', '' );
				update_post_meta( $id, '_sale_price_dates_to', '' );
			}
		}

		// Update parent if grouped so price sorting works and stays in sync with the cheapest child
		$_product = wc_get_product( $id );
		if ( $_product->post->post_parent > 0 || $product_type == 'grouped' ) {

			$clear_parent_ids = array();

			if ( $_product->post->post_parent > 0 ) {
				$clear_parent_ids[] = $_product->post->post_parent;
			}

			if ( $product_type == 'grouped' ) {
				$clear_parent_ids[] = $id;
			}

			if ( $clear_parent_ids ) {
				foreach ( $clear_parent_ids as $clear_id ) {

					$children_by_price = get_posts( array(
						'post_parent'    => $clear_id,
						'orderby'        => 'meta_value_num',
						'order'          => 'asc',
						'meta_key'       => '_price',
						'posts_per_page' => 1,
						'post_type'      => 'product',
						'fields'         => 'ids'
					) );

					if ( $children_by_price ) {
						foreach ( $children_by_price as $child ) {
							$child_price = get_post_meta( $child, '_price', true );
							update_post_meta( $clear_id, '_price', $child_price );
						}
					}
				}
			}
		}

		// Sold Individually
		if ( isset( $data['product']->sold_individually ) ) {
			update_post_meta( $id, '_sold_individually', ( true === $data['product']->sold_individually ) ? 'yes' : '' );
		}

		// Stock status
		if ( isset( $data['product']->in_stock ) ) {
			$stock_status = ( true === $data['product']->in_stock ) ? 'instock' : 'outofstock';
		} else {
			$stock_status = get_post_meta( $id, '_stock_status', true );

			if ( '' === $stock_status ) {
				$stock_status = 'instock';
			}
		}

		// Stock Data
		if ( 'yes' == get_option( 'woocommerce_manage_stock' ) ) {
			// Manage stock
			if ( isset( $data['product']->managing_stock ) ) {
				$managing_stock = ( "yes" === $data['product']->managing_stock ) ? 'yes' : 'no';
				update_post_meta( $id, '_manage_stock', $managing_stock );
			}elseif ( isset( $data['product']->stock ) && intval($data['product']->stock) > 0 ) {
				$managing_stock = 'yes';
				update_post_meta( $id, '_manage_stock', $managing_stock );
			} else {
				$managing_stock = get_post_meta( $id, '_manage_stock', true );
			}

			// Backorders
			if ( isset( $data['backorders'] ) ) {
				if ( 'notify' == $data['backorders'] ) {
					$backorders = 'notify';
				} else {
					$backorders = ( true === $data['backorders'] ) ? 'yes' : 'no';
				}

				update_post_meta( $id, '_backorders', $backorders );
			} else {
				$backorders = get_post_meta( $id, '_backorders', true );
			}

			if ( 'grouped' == $product_type ) {

				update_post_meta( $id, '_manage_stock', 'no' );
				update_post_meta( $id, '_backorders', 'no' );
				update_post_meta( $id, '_stock', '' );

				wc_update_product_stock_status( $id, $stock_status );

			} elseif ( 'external' == $product_type ) {

				update_post_meta( $id, '_manage_stock', 'no' );
				update_post_meta( $id, '_backorders', 'no' );
				update_post_meta( $id, '_stock', '' );

				wc_update_product_stock_status( $id, 'instock' );

			} elseif ( 'yes' == $managing_stock ) {
				update_post_meta( $id, '_backorders', $backorders );

				wc_update_product_stock_status( $id, $stock_status );

				// Stock quantity
				if ( isset( $data['product']->stock ) ) {
					wc_update_product_stock( $id, intval( $data['product']->stock ) );
				}
			} else {

				// Don't manage stock
				update_post_meta( $id, '_manage_stock', 'no' );
				update_post_meta( $id, '_backorders', $backorders );
				update_post_meta( $id, '_stock', '' );

				wc_update_product_stock_status( $id, $stock_status );
			}

		} else {
			wc_update_product_stock_status( $id, $stock_status );
		}

		

		return true;
	}
	function get_products_by_sku( $sku ) {

	  global $wpdb;
	  $products = array();
	  $query_args = array(
			'fields'      => 'ids',
			'post_type'   => array( 'product' , 'product_variation'),
			'post_status' => 'publish',
			// 'post_parent' => 0,
			'meta_query' => array(
				array(
					'key'		=>'_sku',
					'value'		=>	$sku,
					'compare'	=> 'IN'
					)
				),
				
		);
		$query = new WP_Query( $query_args );
		if($query->found_posts > 0){
			foreach($query->posts as $product_id){
				$products[] = get_product( $product_id );
			}
		}
	  if ( sizeof($products) > 0 ) return $products; 
	
	  return null;
	
	}
}
