<?php
/**
 * Event categories and per-event color.
 *
 * @package Ekdiloseis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * #rrggbb or empty. Three-digit hex is expanded. Invalid values are empty.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function ekdiloseis_normalize_hex_color( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$color = sanitize_hex_color( trim( $value ) );
	if ( ! is_string( $color ) || '' === $color ) {
		return '';
	}
	$color = strtolower( $color );
	if ( 4 === strlen( $color ) ) {
		$color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
	}
	if ( ! preg_match( '/^#[0-9a-f]{6}$/', $color ) ) {
		return '';
	}
	return $color;
}

/**
 * White or near-black, whichever stays readable on the given fill.
 *
 * @param string $hex #rrggbb.
 * @return string
 */
function ekdiloseis_contrast_text_color( $hex ) {
	$hex = ltrim( $hex, '#' );
	if ( ! preg_match( '/^[0-9a-f]{6}$/', $hex ) ) {
		return '#ffffff';
	}
	$channels = array(
		hexdec( substr( $hex, 0, 2 ) ) / 255,
		hexdec( substr( $hex, 2, 2 ) ) / 255,
		hexdec( substr( $hex, 4, 2 ) ) / 255,
	);
	$linear   = array();
	foreach ( $channels as $channel ) {
		$linear[] = $channel <= 0.03928 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
	}
	$luminance = ( 0.2126 * $linear[0] ) + ( 0.7152 * $linear[1] ) + ( 0.0722 * $linear[2] );
	return $luminance > 0.179 ? '#111827' : '#ffffff';
}

/**
 * Stored category color, or the historical green when the term has none.
 *
 * @param int $term_id Term ID.
 * @return string
 */
function ekdiloseis_category_color( $term_id ) {
	$raw   = get_term_meta( (int) $term_id, 'color', true );
	$color = ekdiloseis_normalize_hex_color( is_string( $raw ) ? $raw : '' );
	return '' !== $color ? $color : '#1b7f4a';
}

/**
 * Override, else the lowest term_id category color, else neutral gray.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function ekdiloseis_event_color( $post_id ) {
	$override = get_post_meta( (int) $post_id, 'event_color', true );
	$override = ekdiloseis_normalize_hex_color( is_string( $override ) ? $override : '' );
	if ( '' !== $override ) {
		return $override;
	}

	$terms = get_the_terms( (int) $post_id, 'ekdilosi_katigoria' );
	if ( is_array( $terms ) && $terms ) {
		usort(
			$terms,
			static function ( $a, $b ) {
				return (int) $a->term_id <=> (int) $b->term_id;
			}
		);
		return ekdiloseis_category_color( (int) $terms[0]->term_id );
	}

	return '#6b7280';
}

/**
 * Fill and readable text for one event.
 *
 * @param int $post_id Post ID.
 * @return array{color: string, textColor: string}
 */
function ekdiloseis_event_colors( $post_id ) {
	$color = ekdiloseis_event_color( $post_id );
	return array(
		'color'     => $color,
		'textColor' => ekdiloseis_contrast_text_color( $color ),
	);
}

/**
 * Empty is allowed: it means inherit the category color.
 *
 * @param mixed $value Raw meta.
 * @return string
 */
function ekdiloseis_sanitize_event_color_meta( $value ) {
	return ekdiloseis_normalize_hex_color( is_string( $value ) ? $value : '' );
}

/**
 * Category color meta. Missing or invalid becomes the default green.
 *
 * @param mixed $value Raw meta.
 * @return string
 */
function ekdiloseis_sanitize_term_color_meta( $value ) {
	$color = ekdiloseis_normalize_hex_color( is_string( $value ) ? $value : '' );
	return '' !== $color ? $color : '#1b7f4a';
}

/**
 * Font Awesome class: one style (solid, regular, or brands) and one fa-* name.
 * Empty is allowed and means no stored icon.
 *
 * @param mixed $value Raw class string.
 * @return string
 */
function ekdiloseis_sanitize_icon_class( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = strtolower( trim( (string) preg_replace( '/\s+/', ' ', sanitize_text_field( $value ) ) ) );
	if ( '' === $value ) {
		return '';
	}
	if ( ! preg_match( '/^(fa-solid|fa-regular|fa-brands) fa-[a-z0-9-]+$/', $value ) ) {
		return '';
	}
	return $value;
}

/**
 * Stored category icon, or empty when the term has none.
 *
 * @param int $term_id Term ID.
 * @return string
 */
function ekdiloseis_category_icon( $term_id ) {
	$raw = get_term_meta( (int) $term_id, 'icon', true );
	return ekdiloseis_sanitize_icon_class( is_string( $raw ) ? $raw : '' );
}

/**
 * Event override, else the lowest term_id category icon, else the calendar icon.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function ekdiloseis_event_icon( $post_id ) {
	$override = get_post_meta( (int) $post_id, 'event_icon', true );
	$override = ekdiloseis_sanitize_icon_class( is_string( $override ) ? $override : '' );
	if ( '' !== $override ) {
		return $override;
	}

	$terms = get_the_terms( (int) $post_id, 'ekdilosi_katigoria' );
	if ( is_array( $terms ) && $terms ) {
		usort(
			$terms,
			static function ( $a, $b ) {
				return (int) $a->term_id <=> (int) $b->term_id;
			}
		);
		$icon = ekdiloseis_category_icon( (int) $terms[0]->term_id );
		if ( '' !== $icon ) {
			return $icon;
		}
	}

	return 'fa-solid fa-calendar';
}

/**
 * Register the category taxonomy.
 */
function ekdiloseis_register_taxonomy() {
	$labels = array(
		'name'          => 'Event Categories',
		'singular_name' => 'Event Category',
		'search_items'  => 'Search Event Categories',
		'all_items'     => 'All Event Categories',
		'edit_item'     => 'Edit Event Category',
		'update_item'   => 'Update Event Category',
		'add_new_item'  => 'Add New Event Category',
		'new_item_name' => 'New Event Category Name',
		'menu_name'     => 'Event Categories',
		'not_found'     => 'No event categories found',
		'popular_items' => 'Popular Event Categories',
		'separate_items_with_commas' => 'Separate event categories with commas',
		'add_or_remove_items'        => 'Add or remove event categories',
		'choose_from_most_used'      => 'Choose from the most used event categories',
	);

	register_taxonomy(
		'ekdilosi_katigoria',
		'ekdilosi',
		array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'show_in_menu'      => true,
			'rewrite'           => array( 'slug' => 'ekdilosi-katigoria' ),
		)
	);
}
add_action( 'init', 'ekdiloseis_register_taxonomy' );

/**
 * Term and post color meta.
 */
function ekdiloseis_register_color_meta() {
	register_term_meta(
		'ekdilosi_katigoria',
		'color',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'ekdiloseis_sanitize_term_color_meta',
			'auth_callback'     => static function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);

	register_term_meta(
		'ekdilosi_katigoria',
		'icon',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'ekdiloseis_sanitize_icon_class',
			'auth_callback'     => static function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);

	register_post_meta(
		'ekdilosi',
		'event_color',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'ekdiloseis_sanitize_event_color_meta',
			'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
				unset( $allowed, $meta_key );
				return current_user_can( 'edit_post', (int) $post_id );
			},
		)
	);

	register_post_meta(
		'ekdilosi',
		'event_icon',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'ekdiloseis_sanitize_icon_class',
			'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
				unset( $allowed, $meta_key );
				return current_user_can( 'edit_post', (int) $post_id );
			},
		)
	);
}
add_action( 'init', 'ekdiloseis_register_color_meta' );

/**
 * Color field on the add-category screen.
 */
function ekdiloseis_category_add_color_field() {
	wp_nonce_field( 'ekdiloseis_save_term_color', 'ekdiloseis_term_color_nonce' );
	?>
	<div class="form-field">
		<label for="ekdiloseis_term_color"><?php echo esc_html( 'Color' ); ?></label>
		<input type="color" name="ekdiloseis_term_color" id="ekdiloseis_term_color" value="#1b7f4a">
		<p><?php echo esc_html( 'Category color in the calendar.' ); ?></p>
	</div>
	<div class="form-field">
		<label for="ekdiloseis_term_icon"><?php echo esc_html( 'Icon' ); ?></label>
		<input type="text" name="ekdiloseis_term_icon" id="ekdiloseis_term_icon" value="" placeholder="fa-solid fa-calendar" class="regular-text">
		<p><?php echo esc_html( 'Font Awesome class for the day list when List media is Icon. Example: fa-solid fa-music.' ); ?></p>
	</div>
	<?php
}
add_action( 'ekdilosi_katigoria_add_form_fields', 'ekdiloseis_category_add_color_field' );

/**
 * Color field on the edit-category screen.
 *
 * @param WP_Term $term Term.
 */
function ekdiloseis_category_edit_color_field( $term ) {
	$color = '#1b7f4a';
	if ( $term instanceof WP_Term ) {
		$color = ekdiloseis_category_color( (int) $term->term_id );
	}
	wp_nonce_field( 'ekdiloseis_save_term_color', 'ekdiloseis_term_color_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ekdiloseis_term_color"><?php echo esc_html( 'Color' ); ?></label></th>
		<td>
			<input type="color" name="ekdiloseis_term_color" id="ekdiloseis_term_color" value="<?php echo esc_attr( $color ); ?>">
			<p class="description"><?php echo esc_html( 'Category color in the calendar.' ); ?></p>
		</td>
	</tr>
	<?php
	$icon = '';
	if ( $term instanceof WP_Term ) {
		$icon = ekdiloseis_category_icon( (int) $term->term_id );
	}
	?>
	<tr class="form-field">
		<th scope="row"><label for="ekdiloseis_term_icon"><?php echo esc_html( 'Icon' ); ?></label></th>
		<td>
			<input type="text" name="ekdiloseis_term_icon" id="ekdiloseis_term_icon" value="<?php echo esc_attr( $icon ); ?>" placeholder="fa-solid fa-calendar" class="regular-text">
			<p class="description"><?php echo esc_html( 'Font Awesome class for the day list when List media is Icon. Example: fa-solid fa-music.' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'ekdilosi_katigoria_edit_form_fields', 'ekdiloseis_category_edit_color_field' );

/**
 * Save the category color. Quick edit has no nonce, so it leaves the color alone.
 *
 * @param int $term_id Term ID.
 */
function ekdiloseis_save_term_color( $term_id ) {
	if ( ! isset( $_POST['ekdiloseis_term_color_nonce'] ) || ! is_string( $_POST['ekdiloseis_term_color_nonce'] ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['ekdiloseis_term_color_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'ekdiloseis_save_term_color' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_term', (int) $term_id ) ) {
		return;
	}

	$raw = $_POST['ekdiloseis_term_color'] ?? '';
	if ( ! is_string( $raw ) ) {
		$raw = '';
	}
	$color = ekdiloseis_normalize_hex_color( sanitize_text_field( wp_unslash( $raw ) ) );
	if ( '' === $color ) {
		$color = '#1b7f4a';
	}
	update_term_meta( (int) $term_id, 'color', $color );

	if ( ! isset( $_POST['ekdiloseis_term_icon'] ) ) {
		return;
	}
	$icon_raw = $_POST['ekdiloseis_term_icon'];
	if ( ! is_string( $icon_raw ) ) {
		$icon_raw = '';
	}
	$icon = ekdiloseis_sanitize_icon_class( sanitize_text_field( wp_unslash( $icon_raw ) ) );
	if ( '' === $icon ) {
		delete_term_meta( (int) $term_id, 'icon' );
	} else {
		update_term_meta( (int) $term_id, 'icon', $icon );
	}
}
add_action( 'created_ekdilosi_katigoria', 'ekdiloseis_save_term_color' );
add_action( 'edited_ekdilosi_katigoria', 'ekdiloseis_save_term_color' );

/**
 * Category add/edit and the event editor, where an icon class is typed.
 *
 * @return bool
 */
function ekdiloseis_is_icon_picker_screen() {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return false;
	}
	$screen = get_current_screen();
	if ( ! $screen instanceof WP_Screen ) {
		return false;
	}
	if ( 'ekdilosi_katigoria' === $screen->taxonomy && in_array( $screen->base, array( 'edit-tags', 'term' ), true ) ) {
		return true;
	}
	return 'ekdilosi' === $screen->post_type && 'post' === $screen->base;
}

/**
 * Icon picker and Font Awesome, only on the screens that edit an icon class.
 */
function ekdiloseis_enqueue_icon_picker() {
	if ( ! ekdiloseis_is_icon_picker_screen() ) {
		return;
	}

	$css = EKDILOSEIS_DIR . 'assets/ekdiloseis-icon-picker.css';
	$js  = EKDILOSEIS_DIR . 'assets/ekdiloseis-icon-picker.js';
	if ( is_readable( $css ) ) {
		wp_enqueue_style(
			'ekdiloseis-icon-picker',
			EKDILOSEIS_URL . 'assets/ekdiloseis-icon-picker.css',
			array(),
			(string) filemtime( $css )
		);
	}
	if ( is_readable( $js ) ) {
		wp_enqueue_script(
			'ekdiloseis-icon-picker',
			EKDILOSEIS_URL . 'assets/ekdiloseis-icon-picker.js',
			array(),
			(string) filemtime( $js ),
			true
		);
	}

	if ( ! wp_style_is( 'ekdiloseis-font-awesome', 'registered' ) ) {
		wp_register_style(
			'ekdiloseis-font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
			array(),
			'6.7.2'
		);
	}
	wp_enqueue_style( 'ekdiloseis-font-awesome' );
}
add_action( 'admin_enqueue_scripts', 'ekdiloseis_enqueue_icon_picker' );
