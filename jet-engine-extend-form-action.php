<?php
/**
 * Plugin Name: JetEngine - Extend form actions
 * Plugin URI: Allow to insert additional data on Insert/Update post action
 * Description:
 * Version:     1.1.0
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * Text Domain: jet-appointments-booking
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

/*
 Config example:

 add_filter( 'jet-engine-extend-form-actions/config', function() {
	return array(
		123 => array(
			'_form_field_1' => array(
				'prop'   => 'post_meta',
				'key'    => '_meta_key',
			),
			'_form_field_2' => array(
				'prop' => 'post_terms',
				'tax'  => 'taxonomy_slug',
				'by'   => 'name',
			),
			'_form_field_3' => array(
				'prop'   => 'post_data',
				'key'    => 'post_title',
				'suffix' => ' -',
			),
			'_form_field_4' => array(
				'prop'   => 'post_data',
				'key'    => 'post_title',
				'prefix' => ' ',
			),
			'_form_field_5' => array(
				'prop'   => 'post_meta',
				'key'    => '_meta_key',
				'tax'    => 'taxonomy_slug',
			),
			'_form_field_5' => array(
				'props' => array(
					array(
						'prop'   => 'post_meta',
						'key'    => '_meta_key',
						'tax'    => 'taxonomy_slug',
					),
					array(
						'prop'   => 'post_data',
						'key'    => 'post_title',
						'prefix' => ' ',
					),
				),
			),
		),
	);
 } );

 Where:

 - 123 - required form ID (you can find it in the address bar on the edit for screen).
 - _form_field_1, _form_field_2, ... - names of the submitted form field to get data from.
 - prop - post_data, post_meta or post_terms - type of data to set.
 - key - for post_meta prop is the meta key name to set, for post_data is the property of the post object to set
 - tax - for post_terms prop - taxonomy name to insert new terms into. You can also set 'tax' argument for *post_meta* in cases when you need to duplicate selected term from current taxonomy into meta field.
 - prefix, suffix - this arguments are used when you combining multiple fiels values into the same post field or meta key. Prefix is what need to be added before combined field, suffix - after
 - by - for the terms input is way how terms will be processed - if value is set to 'id' - passed terms IDs will be attaqched to post, with any other values - plugin will create term at first and than attach it to post
 */

class Jet_Engine_Extend_Form_Actions {

	public $config = array();

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {

		define( 'JET_EXT_FA__FILE__', __FILE__ );
		define( 'JET_EXT_FA_PLUGIN_BASE', plugin_basename( JET_EXT_FA__FILE__ ) );
		define( 'JET_EXT_FA_PATH', plugin_dir_path( JET_EXT_FA__FILE__ ) );

		$this->config = apply_filters( 'jet-engine-extend-form-actions/config', array() );

		if ( empty( $this->config ) ) {
			return;
		}

		add_action(
			'jet-engine/forms/booking/notification/insert_post',
			array( $this, 'update_post_je' ), 20, 2
		);

		add_action(
			'jet-form-builder/form-handler/after-send',
			array( $this, 'update_post_jfb' )
		);
	}

	public function update_post( $post_id, $form_id, $request, ...$term_args_payload ) {

		if ( ! isset( $this->config[ $form_id ] ) ) {
			return;
		}

		if ( empty( $post_id ) ) {
			return;
		}

		$postarr     = array();
		$meta_input  = array();
		$terms_input = array();

		foreach ( $this->config[ $form_id ] as $field => $props_set ) {

			if ( ! isset( $request[ $field ] ) ) {
				continue;
			}

			if ( empty( $props_set['props'] ) ) {
				$props_set['props'] = array( $props_set );
			}

			foreach ( $props_set['props'] as $data ) {

				switch ( $data['prop'] ) {

					case 'post_data':

						if ( ! empty( $data['key'] ) ) {

							$value = $request[ $field ];
							$value = $this->prepare_value( $value, $data );

							if ( ! empty( $postarr[ $data['key'] ] ) ) {
								$postarr[ $data['key'] ] .= $value;
							} else {
								$postarr[ $data['key'] ] = $value;
							}

						}

						break;

					case 'post_meta':

						if ( ! empty( $data['key'] ) ) {

							$value = $request[ $field ];
							$value = $this->prepare_value( $value, $data );

							if ( ! empty( $meta_input[ $data['key'] ] ) ) {
								$meta_input[ $data['key'] ] .= $value;
							} else {
								$meta_input[ $data['key'] ] = $value;
							}

						}

						break;

					case 'post_terms':

						if ( ! empty( $data['tax'] ) ) {

							$tax   = $data['tax'];
							$value = $request[ $field ];

							if ( ! isset( $terms_input[ $tax ] ) ) {
								$terms_input[ $tax ] = array();
							}

							$by = ! empty( $data['by'] ) ? $data['by'] : 'name';

							if ( 'id' === $by ) {
								$terms_list = $value;
							} else {

								if ( ! is_array( $value ) ) {
									$value = array( $value );
								}

								$terms_list = array();

								foreach ( $value as $term ) {

									$term_id = term_exists( $term, $tax );

									if ( ! empty( $term_id ) && is_array( $term_id ) ) {
										$terms_list[] = $term_id['term_id'];
									} else {

										$term_args = apply_filters(
											'jet-engine-extend-form-actions/insert-term-args',
											array(),
											$data,
											...$term_args_payload
										);

										$term_id = wp_insert_term( $term, $tax, $term_args );

										if ( ! empty( $term_id ) && is_array( $term_id ) ) {
											$terms_list[] = $term_id['term_id'];
										}

									}

								}

							}

							if ( ! is_array( $terms_list ) ) {
								$terms_input[ $tax ][] = absint( $terms_list );
							} else {
								$terms_input[ $tax ] = array_merge(
									$terms_input[ $tax ],
									array_map( 'absint', $terms_list )
								);
							}

						}

						break;

				}
			}

		}

		if ( ! empty( $postarr ) ) {
			$postarr['ID'] = $post_id;
			wp_update_post( $postarr );
		}

		if ( ! empty( $meta_input ) ) {
			foreach ( $meta_input as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}

		if ( ! empty( $terms_input ) ) {
			foreach ( $terms_input as $tax => $terms ) {
				$res = wp_set_post_terms( $post_id, $terms, $tax );
			}
		}
	}

	public function update_post_je( $args, $notifications ) {
		$post_id = absint( $notifications->data['inserted_post_id'] ?? 0 );
		$form_id = $notifications->form;

		$this->update_post( $post_id, $form_id, $notifications->data, $args, $notifications );
	}


	public function update_post_jfb() {
		$post_id = jet_form_builder()->form_handler->action_handler->get_inserted_post_id();
		$form_id = jet_form_builder()->form_handler->form_id;
		$request = jet_form_builder()->form_handler->action_handler->request_data;

		$this->update_post( $post_id, $form_id, $request );
	}

	public function prepare_value( $value = '', $data = array() ) {

		if ( ! empty( $data['tax'] ) ) {

			$is_single = false;

			if ( ! is_array( $value ) ) {
				$is_single = true;
				$value     = array( $value );
			}

			$terms = array();

			foreach ( $value as $term_id ) {

				$term = get_term_by( 'term_id', $term_id, $data['tax'] );

				if ( $term ) {
					$terms[] = $term->name;
				}

			}

			if ( $is_single ) {
				$value = isset( $terms[0] ) ? $terms[0] : false;
			} else {
				$value = $terms;
			}

		}

		if ( ! empty( $data['prefix'] ) && ! is_array( $value ) ) {
			$value = $data['prefix'] . $value;
		}

		if ( ! empty( $data['suffix'] ) && ! is_array( $value ) ) {
			$value .= $data['suffix'];
		}

		return $value;
	}

}

new Jet_Engine_Extend_Form_Actions();
