<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACF_Field_Repeater_Lite extends acf_field {
	public function __construct() {
		$this->name     = 'repeater_lite';
		$this->label    = __( 'Repeater Lite', 'acf-repeater-lite' );
		$this->category = 'layout';
		$this->defaults = array(
			'sub_fields_config' => "label|Label|text\nvalue|Value|text",
			'button_label'      => __( 'Add Row', 'acf-repeater-lite' ),
			'min_rows'          => 0,
			'max_rows'          => 0,
			'layout'            => 'table',
		);

		$this->l10n = array(
			'error' => __( 'Error! Please enter a valid value', 'acf-repeater-lite' ),
		);

		parent::__construct();
	}

	public function render_field_settings( $field ) {
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Sub Fields', 'acf-repeater-lite' ),
				'instructions' => __( 'One field per line: name|Label|type. Supported types: text, textarea, number, url, email, select, image. For select: name|Label|select|value:Label,value2:Label 2', 'acf-repeater-lite' ),
				'type'         => 'textarea',
				'name'         => 'sub_fields_config',
				'rows'         => 8,
			)
		);

		acf_render_field_setting(
			$field,
			array(
				'label' => __( 'Add Row Button Label', 'acf-repeater-lite' ),
				'type'  => 'text',
				'name'  => 'button_label',
			)
		);

		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Minimum Rows', 'acf-repeater-lite' ),
				'instructions' => __( '0 = no minimum', 'acf-repeater-lite' ),
				'type'         => 'number',
				'name'         => 'min_rows',
				'min'          => 0,
			)
		);

		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Maximum Rows', 'acf-repeater-lite' ),
				'instructions' => __( '0 = unlimited', 'acf-repeater-lite' ),
				'type'         => 'number',
				'name'         => 'max_rows',
				'min'          => 0,
			)
		);
	}

	private function parse_sub_fields( $field ) {
		$config = isset( $field['sub_fields_config'] ) ? (string) $field['sub_fields_config'] : '';
		$lines  = preg_split( '/\r\n|\r|\n/', $config );
		$out    = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $line ) );
			$name  = isset( $parts[0] ) ? sanitize_key( $parts[0] ) : '';
			$label = isset( $parts[1] ) && '' !== $parts[1] ? sanitize_text_field( $parts[1] ) : $name;
			$type  = isset( $parts[2] ) ? sanitize_key( $parts[2] ) : 'text';

			if ( '' === $name ) {
				continue;
			}

			$allowed_types = array( 'text', 'textarea', 'number', 'url', 'email', 'select', 'image' );
			if ( ! in_array( $type, $allowed_types, true ) ) {
				$type = 'text';
			}

			$choices = array();
			if ( 'select' === $type && ! empty( $parts[3] ) ) {
				foreach ( explode( ',', $parts[3] ) as $choice ) {
					$choice_parts = array_map( 'trim', explode( ':', $choice, 2 ) );
					$value        = isset( $choice_parts[0] ) ? sanitize_text_field( $choice_parts[0] ) : '';
					$choice_label = isset( $choice_parts[1] ) ? sanitize_text_field( $choice_parts[1] ) : $value;
					if ( '' !== $value ) {
						$choices[ $value ] = $choice_label;
					}
				}
			}

			$out[] = array(
				'name'    => $name,
				'label'   => $label,
				'type'    => $type,
				'choices' => $choices,
			);
		}

		if ( empty( $out ) ) {
			$out[] = array(
				'name'    => 'value',
				'label'   => __( 'Value', 'acf-repeater-lite' ),
				'type'    => 'text',
				'choices' => array(),
			);
		}

		return $out;
	}

	public function render_field( $field ) {
		$rows       = is_array( $field['value'] ) ? $field['value'] : array();
		$sub_fields = $this->parse_sub_fields( $field );
		$button     = ! empty( $field['button_label'] ) ? $field['button_label'] : __( 'Add Row', 'acf-repeater-lite' );
		$min_rows   = isset( $field['min_rows'] ) ? absint( $field['min_rows'] ) : 0;
		$max_rows   = isset( $field['max_rows'] ) ? absint( $field['max_rows'] ) : 0;

		while ( count( $rows ) < $min_rows ) {
			$rows[] = array();
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'acf-repeater-lite',
			plugins_url( 'assets/admin.js', dirname( __DIR__ ) . '/acf-repeater-lite.php' ),
			array( 'jquery' ),
			self::plugin_version(),
			true
		);
		wp_enqueue_style(
			'acf-repeater-lite',
			plugins_url( 'assets/admin.css', dirname( __DIR__ ) . '/acf-repeater-lite.php' ),
			array(),
			self::plugin_version()
		);

		printf(
			'<div class="repeater-lite" data-field-name="%s" data-min-rows="%d" data-max-rows="%d">',
			esc_attr( $field['name'] ),
			$min_rows,
			$max_rows
		);

		echo '<table class="widefat striped repeater-lite__table"><thead><tr>';
		echo '<th class="repeater-lite__handle-col"></th>';
		foreach ( $sub_fields as $sub_field ) {
			echo '<th>' . esc_html( $sub_field['label'] ) . '</th>';
		}
		echo '<th class="repeater-lite__actions-col">' . esc_html__( 'Actions', 'acf-repeater-lite' ) . '</th>';
		echo '</tr></thead><tbody class="repeater-lite__rows">';

		foreach ( $rows as $index => $row ) {
			$this->render_row( $field['name'], $index, $row, $sub_fields );
		}

		echo '</tbody></table>';

		echo '<p><button type="button" class="button button-secondary repeater-lite__add">' . esc_html( $button ) . '</button></p>';

		echo '<script type="text/html" class="repeater-lite__template">';
		$this->render_row( $field['name'], '__INDEX__', array(), $sub_fields );
		echo '</script>';

		echo '</div>';
	}

	private static function plugin_version() {
		return ACF_Repeater_Lite_Plugin::VERSION;
	}

	private function render_row( $field_name, $index, $row, $sub_fields ) {
		$row = is_array( $row ) ? $row : array();
		echo '<tr class="repeater-lite__row">';
		echo '<td class="repeater-lite__handle"><span class="dashicons dashicons-move" aria-hidden="true"></span></td>';

		foreach ( $sub_fields as $sub_field ) {
			$value = isset( $row[ $sub_field['name'] ] ) ? $row[ $sub_field['name'] ] : '';
			$name  = sprintf( '%s[%s][%s]', $field_name, $index, $sub_field['name'] );

			echo '<td class="repeater-lite__cell">';
			$this->render_sub_field_input( $sub_field, $name, $value );
			echo '</td>';
		}

		echo '<td class="repeater-lite__actions"><button type="button" class="button-link-delete repeater-lite__remove">' . esc_html__( 'Remove', 'acf-repeater-lite' ) . '</button></td>';
		echo '</tr>';
	}

	private function render_sub_field_input( $sub_field, $name, $value ) {
		switch ( $sub_field['type'] ) {
			case 'textarea':
				echo '<textarea rows="3" class="widefat" name="' . esc_attr( $name ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
				break;

			case 'number':
				echo '<input type="number" class="widefat" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
				break;

			case 'url':
				echo '<input type="url" class="widefat" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
				break;

			case 'email':
				echo '<input type="email" class="widefat" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
				break;

			case 'select':
				echo '<select class="widefat" name="' . esc_attr( $name ) . '">';
				echo '<option value=""></option>';
				foreach ( $sub_field['choices'] as $choice_value => $choice_label ) {
					echo '<option value="' . esc_attr( $choice_value ) . '" ' . selected( (string) $value, (string) $choice_value, false ) . '>' . esc_html( $choice_label ) . '</option>';
				}
				echo '</select>';
				break;

			case 'image':
				$attachment_id = absint( $value );
				$preview       = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
				echo '<div class="repeater-lite__image-field">';
				echo '<input type="hidden" class="repeater-lite__image-id" name="' . esc_attr( $name ) . '" value="' . esc_attr( $attachment_id ) . '">';
				echo '<div class="repeater-lite__image-preview">';
				if ( $preview ) {
					echo '<img src="' . esc_url( $preview ) . '" alt="">';
				}
				echo '</div>';
				echo '<button type="button" class="button repeater-lite__image-select">' . esc_html__( 'Select Image', 'acf-repeater-lite' ) . '</button> ';
				echo '<button type="button" class="button-link-delete repeater-lite__image-remove">' . esc_html__( 'Remove Image', 'acf-repeater-lite' ) . '</button>';
				echo '</div>';
				break;

			case 'text':
			default:
				echo '<input type="text" class="widefat" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
				break;
		}
	}

	public function update_value( $value, $post_id, $field ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sub_fields = $this->parse_sub_fields( $field );
		$types      = array();
		foreach ( $sub_fields as $sub_field ) {
			$types[ $sub_field['name'] ] = $sub_field['type'];
		}

		$clean = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean_row    = array();
			$row_has_data = false;

			foreach ( $types as $key => $type ) {
				$raw = isset( $row[ $key ] ) ? $row[ $key ] : '';

				switch ( $type ) {
					case 'textarea':
						$sanitized = sanitize_textarea_field( $raw );
						break;
					case 'number':
						$sanitized = '' === $raw ? '' : (string) floatval( $raw );
						break;
					case 'url':
						$sanitized = esc_url_raw( $raw );
						break;
					case 'email':
						$sanitized = sanitize_email( $raw );
						break;
					case 'image':
						$sanitized = absint( $raw );
						break;
					case 'select':
					case 'text':
					default:
						$sanitized = sanitize_text_field( $raw );
						break;
				}

				if ( '' !== $sanitized && 0 !== $sanitized ) {
					$row_has_data = true;
				}
				$clean_row[ $key ] = $sanitized;
			}

			if ( $row_has_data ) {
				$clean[] = $clean_row;
			}
		}

		$max_rows = isset( $field['max_rows'] ) ? absint( $field['max_rows'] ) : 0;
		if ( $max_rows > 0 && count( $clean ) > $max_rows ) {
			$clean = array_slice( $clean, 0, $max_rows );
		}

		return array_values( $clean );
	}

	public function validate_value( $valid, $value, $field, $input ) {
		if ( true !== $valid ) {
			return $valid;
		}

		$row_count = is_array( $value ) ? count( array_filter( $value, 'is_array' ) ) : 0;
		$min_rows  = isset( $field['min_rows'] ) ? absint( $field['min_rows'] ) : 0;
		$max_rows  = isset( $field['max_rows'] ) ? absint( $field['max_rows'] ) : 0;

		if ( $min_rows > 0 && $row_count < $min_rows ) {
			return sprintf(
				/* translators: %d: minimum number of rows */
				__( 'Please add at least %d rows.', 'acf-repeater-lite' ),
				$min_rows
			);
		}

		if ( $max_rows > 0 && $row_count > $max_rows ) {
			return sprintf(
				/* translators: %d: maximum number of rows */
				__( 'Please add no more than %d rows.', 'acf-repeater-lite' ),
				$max_rows
			);
		}

		return $valid;
	}

	public function format_value( $value, $post_id, $field ) {
		return is_array( $value ) ? array_values( $value ) : array();
	}
}

