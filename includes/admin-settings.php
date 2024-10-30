<?php
/**
 * CP Admin Settings Class.
 *
 * @author 		CartPipe
 * @category 	Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CPD_Admin_Settings' ) ) :

/**
 * WC_Admin_Settings
 */
class CPD_Admin_Settings {

	private static $settings = array();
	private static $errors   = array();
	private static $messages = array();

	/**
	 * Include the settings page classes
	 */
	public static function get_settings_pages() {
		if ( empty( self::$settings ) ) {
			$settings = array();

						//
			$settings[] = include( 'settings/class-qb-settings-page.php' );
			$settings[] = include( 'settings/class-cp-settings.php' );		
			$settings[] = include( 'settings/class-cartpipe.php' );				
			self::$settings = apply_filters( 'cpd_get_settings_pages', $settings );
			
		}

		return self::$settings;
	}

	/**
	 * Save the settings
	 */
	public static function save() {
		global $current_tab;

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'cartpipe' ) ) {
			die( __( 'Action failed. Please refresh the page and retry.', 'cartpipe' ) );
		}

		// Trigger actions
		do_action( 'cpd_settings_save_' . $current_tab );
		do_action( 'cpd_update_options_' . $current_tab );
		do_action( 'cpd_update_options' );

		self::add_message( __( 'Your settings have been saved.', 'cartpipe' ) );
				
		flush_rewrite_rules();

		do_action( 'qb_settings_saved' );
	}

	/**
	 * Add a message
	 * @param string $text
	 */
	public static function add_message( $text ) {
		self::$messages[] = $text;
	}

	/**
	 * Add an error
	 * @param string $text
	 */
	public static function add_error( $text ) {
		self::$errors[] = $text;
	}

	/**
	 * Output messages + errors
	 * @return string
	 */
	public static function show_messages() {
		if ( sizeof( self::$errors ) > 0 ) {
			foreach ( self::$errors as $error ) {
				echo '<div id="message" class="error fade"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
			}
		} elseif ( sizeof( self::$messages ) > 0 ) {
			foreach ( self::$messages as $message ) {
				echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
		}
	}

	/**
	 * Settings page.
	 *
	 * Handles the display of the main woocommerce settings page in admin.
	 *
	 * @return void
	 */
	public static function output() {
		global $current_section, $current_tab;

		do_action( 'cpd_settings_start' );

		
		wp_localize_script( 'cpd_settings', 'cpd_settings_params', array(
			'i18n_nav_warning' => __( 'The changes you made will be lost if you navigate away from this page.', 'cartpipe' )
		) );

		// Include settings pages
		self::get_settings_pages();

		// Get current tab/section
		$current_tab     = empty( $_GET['tab'] ) ? 'settings' : sanitize_title( $_GET['tab'] );
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( $_REQUEST['section'] );

		// Save settings if data has been posted
		if ( ! empty( $_POST ) ) {
			self::save();
		}

		// Add any posted messages
		if ( ! empty( $_GET['cpd_error'] ) ) {
			self::add_error( stripslashes( $_GET['cpd_error'] ) );
		}

		if ( ! empty( $_GET['cpd_message'] ) ) {
			self::add_message( stripslashes( $_GET['cpd_message'] ) );
		}

		self::show_messages();
		
		// Get tabs for the settings page
		$tabs = apply_filters( 'cpd_settings_tabs_array', array() );

		include 'views/cp-admin-settings-html.php';
	}

	/**
	 * Get a setting from the settings API.
	 *
	 * @param mixed $option_name
	 * @return string
	 */
	public static function get_option( $option_name, $default = '' ) {
		// Array value
		if ( strstr( $option_name, '[' ) ) {

			parse_str( $option_name, $option_array );

			// Option name is first key
			$option_name = current( array_keys( $option_array ) );

			// Get value
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}

		// Single value
		} else {
			$option_value = get_option( $option_name, null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = array_map( 'stripslashes', $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return $option_value === null ? $default : $option_value;
	}

	/**
	 * Output admin fields.
	 *
	 * Loops though the woocommerce options array and outputs each field.
	 *
	 * @param array $options Opens array to output
	 */
	public static function output_fields( $options ) {
		foreach ( $options as $value ) {
			if ( ! isset( $value['type'] ) ) {
				continue;
			}
			if ( ! isset( $value['id'] ) ) {
				$value['id'] = '';
			}
			if ( ! isset( $value['title'] ) ) {
				$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
			}
			if ( ! isset( $value['class'] ) ) {
				$value['class'] = '';
			}
			if ( ! isset( $value['css'] ) ) {
				$value['css'] = '';
			}
			if ( ! isset( $value['default'] ) ) {
				$value['default'] = '';
			}
			if ( ! isset( $value['desc'] ) ) {
				$value['desc'] = '';
			}
			if ( ! isset( $value['desc_tip'] ) ) {
				$value['desc_tip'] = false;
			}

			// Custom attribute handling
			$custom_attributes = array();

			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			// Description handling
			if ( true === $value['desc_tip'] ) {
				$description = '';
				$tip = $value['desc'];
			} elseif ( ! empty( $value['desc_tip'] ) ) {
				$description = $value['desc'];
				$tip = $value['desc_tip'];
			} elseif ( ! empty( $value['desc'] ) ) {
				$description = $value['desc'];
				$tip = '';
			} else {
				$description = $tip = '';
			}

			if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ) ) ) {
				$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
			} elseif ( $description && in_array( $value['type'], array( 'checkbox' ) ) ) {
				$description =  wp_kses_post( $description );
			} elseif ( $description ) {
				$description = '<span class="description">' . wp_kses_post( $description ) . '</span>';
			}

			if ( $tip && in_array( $value['type'], array( 'checkbox' ) ) ) {

				$tip = '<p class="description">' . $tip . '</p>';

			} elseif ( $tip ) {

				$tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . WC()->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			}

			// Switch based on type
			switch ( $value['type'] ) {

				// Section Titles
				case 'title':
					if ( ! empty( $value['title'] ) ) {
						echo '<h3>' . esc_html( $value['title'] ) . '</h3>';
					}
					if ( ! empty( $value['desc'] ) ) {
						echo wpautop( wptexturize( wp_kses_post( $value['desc'] ) ) );
					}
					echo '<table class="form-table cpd">'. "\n\n";
					if ( ! empty( $value['id'] ) ) {
						do_action( 'cpd_settings_' . sanitize_title( $value['id'] ) );
					}
					break;

				// Section Ends
				case 'sectionend':
					if ( ! empty( $value['id'] ) ) {
						do_action( 'cpd_settings_' . sanitize_title( $value['id'] ) . '_end' );
					}
					echo '</table>';
					if ( ! empty( $value['id'] ) ) {
						do_action( 'cpd_settings_' . sanitize_title( $value['id'] ) . '_after' );
					}
					break;

				// Standard text inputs and subtypes like 'number'
				case 'text':
				case 'email':
				case 'number':
				
					$type         	= $value['type'];
					$class        	= '';
					$option_value 	= self::get_option( $value['id'], $value['default'] );
					$dependency 	= isset( $value['dependency'] ) ? $value['dependency'] : '';
				
					?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
						<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
							<p><input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="<?php echo esc_attr( $type ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								value="<?php echo esc_attr( $option_value ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); ?>
								/>
								</p> <?php echo $description; ?>
						</td>
					</tr><?php
					break;
				case 'content':
					$option_value 	= self::get_option( $value['id'], $value['default'] );
					$dependency 	= isset( $value['dependency'] ) ? $value['dependency'] : '';
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
						<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
							<?php echo $description; ?>
						</td>
					</tr><?php
					break;
				// Textarea

				case 'textarea':
			
					$option_value 	= self::get_option( $value['id'], $value['default'] );
					$dependency 	= isset( $value['dependency'] ) ? $value['dependency'] : '';
					?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
						<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
							<?php echo $description; ?>

							<textarea
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); ?>
								><?php echo esc_textarea( $option_value );  ?></textarea>
						</td>
					</tr><?php
					break;
				case 'data':
					echo $description; 
					switch ($value['sub-type']) {
						case 'posted':
						case 'unposted':
						case 'failed':	
							if(isset($value['data'])){?>
								<tr valign="top">
										<th scope="row" class="titledesc">
											<label><?php _e('Number of Orders', 'cartpipe'); ?></label>
										</th>
										
									</tr>
									<tr valign="top">
										<td class="forminp">
											<p><?php echo ucwords( str_replace( '-', ' ', sanitize_title( $value['data']->total_orders ) ) ); ?></p>
										</td>
									</tr>
								<?php
							}
							break;
						
						case 'synced':
						case 'unsynced':
							
							if(isset($value['data'])){?>
								<tr valign="top">
										<th scope="row" class="titledesc">
											<label><?php _e('Number of Products', 'cartpipe'); ?></label>
										</th>
										
									</tr>
									<tr valign="top">
										<td class="forminp">
											<p><?php echo ucwords( str_replace( '-', ' ', sanitize_title( $value['data']->total_products ) ) ); ?></p>
										</td>
									</tr>
								<?php
							}
							break;
							break;
						
						case 'tax_classes':
							if(isset($value['data'])){?>
								<tr valign="top">
										<th scope="row" class="titledesc">
											<label><?php _e('ID', 'cartpipe'); ?></label>
										</th>
										<th  scope="row" class="titledesc">
											<label><?php _e('Label', 'cartpipe'); ?></label>
										</th>
									</tr>
								<?php 
								foreach ($value['data'] as $key => $value) {?>
									<tr valign="top">
										<td class="forminp">
											<p><?php echo esc_html(  sanitize_title( $key ) ); ?></p>
										</td>
										<td class="forminp">
										<p><?php echo ucwords( str_replace( '-', ' ', sanitize_title( $value ) ) ); ?></p>
										</td>
									</tr>
								<?php
								}	
							}
							break;
						case 'tax_rates':	
							if(isset($value['data'])){?>
								<tr valign="top">
										<th scope="row" class="titledesc">
											<label><?php _e('Code', 'cartpipe'); ?></label>
										</th>
										<th  scope="row" class="titledesc">
											<label><?php _e('Title', 'cartpipe'); ?></label>
										</th>
										<th  scope="row" class="titledesc">
											<label><?php _e('Tax Rate', 'cartpipe'); ?></label>
										</th>
									</tr>
								<?php 
								foreach ($value['data'] as $key => $value) {?>
									<tr valign="top">
										<td class="forminp">
											<p><?php echo sanitize_title( $value->code ); ?></p>
										</td>
										<td class="forminp">
											<p><?php echo ucwords( str_replace( '-', ' ', sanitize_title( $value->title ) ) ); ?></p>
										</td>
										<td class="forminp">
											<p><?php echo ucwords( str_replace( '-', ' ', sanitize_title( $value->tax_rate ) ) ); ?></p>
										</td>
									</tr>
								<?php
								}	
							}
							break;
						case 'payment_methods':
							if(isset($value['data'])){
								foreach ($value['data'] as $key => $value) {
									//	foreach($value as $p_key=>$p_value){
									?>
											<tr valign="top">
												<th scope="row" class="titledesc">
													<label for="<?php echo esc_attr( $value->id ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', sanitize_title( $value->id ) ) ) ); ?></label>
												</th>
												<td class="forminp">
												<p><?php echo ucwords( str_replace( '-', ' ', sanitize_title( $value->title ) ) ); ?></p>
												</td>
											</tr>
								<?php
										//}
									
								}	
							}
							break;
						case 'license':	
							$type         	= $value['type'];
							$label 			= $value['label'];
							$class        	= '';
							
							if(isset($value['data'])){
								foreach ($value['data'] as $key => $value) {?>
									<tr valign="top">
										<th scope="row" class="titledesc">
											<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', sanitize_title( $key ) ) ) ); ?></label>
										</th>
										<td class="forminp">
										<p><?php echo ucwords( str_replace( '_', ' ', sanitize_title( $value ) ) ); ?></p>
										</td>
									</tr>
								<?php
								}	
							}
							break;
					}
					break;
				// Select boxes
				case 'select' :
				case 'multiselect' :

					$option_value 	= self::get_option( $value['id'], $value['default'] );
					$dependency 	= isset( $value['dependency'] ) ? $value['dependency'] : '';
					?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
						<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
							<p><select
								name="<?php echo esc_attr( $value['id'] ); ?><?php if ( $value['type'] == 'multiselect' ) echo '[]'; ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); ?>
								<?php echo ( 'multiselect' == $value['type'] ) ? 'multiple="multiple"' : ''; ?>
								>
								<?php
									foreach ( $value['options'] as $key => $val ) {
										?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php

											if ( is_array( $option_value ) ) {
												selected( in_array( $key, $option_value ), true );
											} else {
												selected( $option_value, $key );
											}

										?>><?php echo $val ?></option>
										<?php
									}
								?>
						   </select></p> <?php echo $description; ?>
						</td>
					</tr><?php
					break;

				// Radio inputs
				case 'radio' :

					$option_value	= self::get_option( $value['id'], $value['default'] );
					$dependency 	= isset( $value['dependency'] ) ? $value['dependency'] : '';
					?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
						<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
							<fieldset>
								<?php echo $description; ?>
								<ul>
								<?php
									foreach ( $value['options'] as $key => $val ) {
										?>
										<li>
											<label><input
												name="<?php echo esc_attr( $value['id'] ); ?>"
												value="<?php echo $key; ?>"
												type="radio"
												style="<?php echo esc_attr( $value['css'] ); ?>"
												class="<?php echo esc_attr( $value['class'] ); ?>"
												<?php echo implode( ' ', $custom_attributes ); ?>
												<?php checked( $key, $option_value ); ?>
												/> <?php echo $val ?></label>
										</li>
										<?php
									}
								?>
								</ul>
							</fieldset>
						</td>
					</tr><?php
					break;

				// Checkbox input
				case 'checkbox' :
					
					$option_value   = self::get_option( $value['id'], $value['default'] );
					$dependency 	= isset( $value['dependency'] ) ? $value['dependency'] : '';
					
					$visbility_class= array();

					if ( ! isset( $value['hide_if_checked'] ) ) {
						$value['hide_if_checked'] = false;
					}
					if ( ! isset( $value['show_if_checked'] ) ) {
						$value['show_if_checked'] = false;
					}
					if ( 'yes' == $value['hide_if_checked'] || 'yes' == $value['show_if_checked'] ) {
						$visbility_class[] = 'hidden_option';
					}
					if ( 'option' == $value['hide_if_checked'] ) {
						$visbility_class[] = 'hide_options_if_checked';
					}
					if ( 'option' == $value['show_if_checked'] ) {
						$visbility_class[] = 'show_options_if_checked';
					}
					
					if ( ! isset( $value['checkboxgroup'] ) || 'start' == $value['checkboxgroup'] ) {
						
						?>
							<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
								<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?></th>
								<td class="forminp forminp-checkbox">
									<fieldset>
						<?php
					} else {
						
						?>
							<fieldset class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
						<?php
					}

					if ( ! empty( $value['title'] ) ) {
						?>
							<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ) ?></span></legend>
						<?php
					}

					?>
						<p><label for="<?php echo $value['id'] ?>">
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								value="1"
								<?php checked( $option_value, 'yes'); ?>
								<?php echo implode( ' ', $custom_attributes ); ?>
							/> 
						</label></p><span class="description"><?php echo $description ?></span> <?php echo $tip; ?>
					<?php

					if ( ! isset( $value['checkboxgroup'] ) || 'end' == $value['checkboxgroup'] ) {
									?>
									</fieldset>
								</td>
							</tr>
						<?php
					} else {
						?>
							</fieldset>
						<?php
					}
					break;
				case 'button':
					$type         	= $value['type'];
					$label 			= $value['label'];
					$class        	= '';
					$link 			= isset($value['url']) ? $value['url'] : '#';
					$option_value 	= self::get_option( $value['id'], $value['default'] );
					$data_type 		= isset($value['data-type']) ? ' data-type="'. $value['data-type'].'"' : '';
					$linked 		= isset($value['linked']) ? ' data-link="'. $value['linked'].'"' : '';
					?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
						<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
							<a
								id="<?php echo esc_attr( $value['id'] ); ?>"
								href="<?php echo esc_attr( $link ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); ?>
								<?php echo $data_type; ?>
								<?php echo $linked; ?>
								/> <?php echo $label;?></a>
								
								<p><?php echo $description; ?></p>
						</td>
					</tr><?php
					break;
				// Default: run an action
				default:
					do_action( 'cpd_admin_field_' . $value['type'], $value );
					break;
			}
		}
	}

	/**
	 * Save admin fields.
	 *
	 * Loops though the woocommerce options array and outputs each field.
	 *
	 * @param array $options Opens array to output
	 * @return bool
	 */
	public static function save_fields( $options ) {
		if ( empty( $_POST ) ) {
			return false;
		}

		// Options to update will be stored here
		$update_options = array();

		// Loop options and get values to save
		foreach ( $options as $value ) {
			if ( ! isset( $value['id'] ) || ! isset( $value['type'] ) ) {
				continue;
			}

			// Get posted value
			if ( strstr( $value['id'], '[' ) ) {
				parse_str( $value['id'], $option_name_array );

				$option_name  = current( array_keys( $option_name_array ) );
				$setting_name = key( $option_name_array[ $option_name ] );

				$option_value = isset( $_POST[ $option_name ][ $setting_name ] ) ? stripslashes_deep( $_POST[ $option_name ][ $setting_name ] ) : null;
			} else {
				$option_name  = $value['id'];
				$setting_name = '';
				$option_value = isset( $_POST[ $value['id'] ] ) ? stripslashes_deep( $_POST[ $value['id'] ] ) : null;
			}

			// Format value
			switch ( sanitize_title( $value['type'] ) ) {
				case 'checkbox' :
					$option_value = is_null( $option_value ) ? 'no' : 'yes';
					break;
				case 'textarea' :
					$option_value = wp_kses_post( trim( $option_value ) );
					break;
				case 'text' :
				case 'email':
				case 'number':
				case 'select' :
				case 'radio' :
					$option_value = sanitize_text_field( $option_value );
					break;
				case 'multiselect' :
					$option_value = array_filter( array_map( 'sanitize_text_field', (array) $option_value ) );
					break;
				default :
					do_action( 'cpd_update_option_' . sanitize_title( $value['type'] ), $value );
					break;
			}

			if ( ! is_null( $option_value ) ) {
				// Check if option is an array
				if ( $option_name && $setting_name ) {
					// Get old option value
					if ( ! isset( $update_options[ $option_name ] ) ) {
						$update_options[ $option_name ] = get_option( $option_name, array() );
					}

					if ( ! is_array( $update_options[ $option_name ] ) ) {
						$update_options[ $option_name ] = array();
					}

					$update_options[ $option_name ][ $setting_name ] = $option_value;

				// Single value
				} else {
					$update_options[ $option_name ] = $option_value;
				}
			}

			// Custom handling
			do_action( 'cpd_update_option', $value );
		}

		// Now save the options
		foreach ( $update_options as $name => $value ) {
			update_option( $name, $value );
		}

		return true;
		}
	}
endif;

