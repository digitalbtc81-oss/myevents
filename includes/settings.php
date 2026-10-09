<?php
/**
 * Calendar engine setting.
 *
 * @package Ekdiloseis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Known engines. Anything else stays on the custom calendar, which is the default.
 *
 * @param mixed $value Raw option or submitted value.
 * @return string custom|fullcalendar|toast|eventcalendar|schedulex
 */
function ekdiloseis_normalize_engine( $value ) {
	if ( ! is_string( $value ) ) {
		return 'custom';
	}
	$value = sanitize_key( $value );
	if ( in_array( $value, array( 'fullcalendar', 'toast', 'eventcalendar', 'schedulex' ), true ) ) {
		return $value;
	}
	return 'custom';
}

/**
 * Engine stored in ekdiloseis_engine. Missing or unknown values stay on the custom calendar.
 *
 * @return string custom|fullcalendar|toast|eventcalendar|schedulex
 */
function ekdiloseis_get_engine() {
	return ekdiloseis_normalize_engine( get_option( 'ekdiloseis_engine', 'custom' ) );
}

/**
 * Accept only the known engines.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function ekdiloseis_sanitize_engine( $value ) {
	return ekdiloseis_normalize_engine( $value );
}

/**
 * Public calendar language. Missing or unknown values stay English.
 *
 * @param mixed $value Raw option or submitted value.
 * @return string en|el
 */
function ekdiloseis_normalize_locale( $value ) {
	if ( ! is_string( $value ) ) {
		return 'en';
	}
	$value = sanitize_key( $value );
	if ( 'el' === $value ) {
		return 'el';
	}
	return 'en';
}

/**
 * Language stored in ekdiloseis_locale. Missing values stay English.
 *
 * @return string en|el
 */
function ekdiloseis_get_locale() {
	return ekdiloseis_normalize_locale( get_option( 'ekdiloseis_locale', 'en' ) );
}

/**
 * Accept only English or Greek.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function ekdiloseis_sanitize_locale( $value ) {
	return ekdiloseis_normalize_locale( $value );
}

/**
 * Public layout of the day event list. Missing or unknown values stay stacked.
 *
 * @param mixed $value Raw option or submitted value.
 * @return string stacked|columns
 */
function ekdiloseis_normalize_layout( $value ) {
	if ( ! is_string( $value ) ) {
		return 'stacked';
	}
	$value = sanitize_key( $value );
	if ( 'columns' === $value ) {
		return 'columns';
	}
	return 'stacked';
}

/**
 * Layout stored in ekdiloseis_layout. An unset option stays stacked.
 *
 * @return string stacked|columns
 */
function ekdiloseis_get_layout() {
	return ekdiloseis_normalize_layout( get_option( 'ekdiloseis_layout', 'stacked' ) );
}

/**
 * Accept only stacked or columns.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function ekdiloseis_sanitize_layout( $value ) {
	return ekdiloseis_normalize_layout( $value );
}

/**
 * Class on every shortcode root. stacked is the current block flow.
 *
 * @return string
 */
function ekdiloseis_layout_class() {
	return 'ekdiloseis--layout-' . ekdiloseis_get_layout();
}

/**
 * What the day list shows beside each event. Missing or unknown values stay on the photo.
 *
 * @param mixed $value Raw option or submitted value.
 * @return string image|icon|none
 */
function ekdiloseis_normalize_list_media( $value ) {
	if ( ! is_string( $value ) ) {
		return 'image';
	}
	$value = sanitize_key( $value );
	if ( in_array( $value, array( 'icon', 'none' ), true ) ) {
		return $value;
	}
	return 'image';
}

/**
 * List media stored in ekdiloseis_list_media. An unset option stays on the featured image.
 *
 * @return string image|icon|none
 */
function ekdiloseis_get_list_media() {
	return ekdiloseis_normalize_list_media( get_option( 'ekdiloseis_list_media', 'image' ) );
}

/**
 * Accept only image, icon, or none.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function ekdiloseis_sanitize_list_media( $value ) {
	return ekdiloseis_normalize_list_media( $value );
}

/**
 * Engines that keep their own appearance settings.
 *
 * @return string[]
 */
function ekdiloseis_style_engines() {
	return array( 'custom', 'fullcalendar', 'toast', 'eventcalendar', 'schedulex' );
}

/**
 * English names shown on the settings screen.
 *
 * @return array<string, string>
 */
function ekdiloseis_engine_labels() {
	return array(
		'custom'        => 'Default',
		'fullcalendar'  => 'FullCalendar',
		'toast'         => 'Toast UI Calendar',
		'eventcalendar' => 'Event Calendar',
		'schedulex'     => 'Schedule-X',
	);
}

/**
 * Safe font stacks. The stored value is the CSS font-family list, not a URL.
 *
 * @return array<string, string>
 */
function ekdiloseis_font_stacks() {
	return array(
		'System'  => 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
		'Arial'   => 'Arial, Helvetica, sans-serif',
		'Georgia' => 'Georgia, "Times New Roman", serif',
		'Courier' => '"Courier New", Courier, monospace',
		'Times'   => '"Times New Roman", Times, serif',
	);
}

/**
 * Values shown in the form when an engine has no saved style yet.
 *
 * @return array<string, int|string>
 */
function ekdiloseis_default_style() {
	$stacks = ekdiloseis_font_stacks();
	return array(
		'font_family' => $stacks['System'],
		'google_font' => '',
		'font_size'   => 14,
		'color_text'  => '#1c1c1c',
		'color_bg'    => '#ffffff',
	);
}

/**
 * Default fill for the selected day on the Default (custom) calendar.
 *
 * @return string
 */
function ekdiloseis_default_selected_color() {
	return '#1e40af';
}

/**
 * Selected-day color as lowercase #rrggbb, or empty when invalid. #rgb is expanded.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function ekdiloseis_sanitize_selected_color( $value ) {
	$color = ekdiloseis_sanitize_style_color( is_string( $value ) ? trim( $value ) : $value );
	if ( '' === $color ) {
		return '';
	}
	$hex = strtolower( ltrim( $color, '#' ) );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	return 6 === strlen( $hex ) ? '#' . $hex : '';
}

/**
 * Google Font family, or empty when missing or not allowed.
 * Letters, numbers, spaces, and hyphens only. Anything else is rejected.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function ekdiloseis_sanitize_google_font( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( wp_unslash( $value ) );
	if ( '' === $value ) {
		return '';
	}
	if ( ! preg_match( '/^[A-Za-z0-9 \-]+$/', $value ) ) {
		return '';
	}
	$value = preg_replace( '/\s+/', ' ', $value );
	if ( ! is_string( $value ) || '' === $value ) {
		return '';
	}
	if ( strlen( $value ) > 80 ) {
		return '';
	}
	return $value;
}

/**
 * Stylesheet for one family. Spaces in the name are + in the query.
 *
 * @param string $family Sanitized family name.
 * @return string
 */
function ekdiloseis_google_font_url( $family ) {
	$family = ekdiloseis_sanitize_google_font( $family );
	if ( '' === $family ) {
		return '';
	}
	$name = str_replace( ' ', '+', $family );
	return 'https://fonts.googleapis.com/css2?family=' . $name . ':wght@400;600;700&display=swap';
}

/**
 * Quoted family plus a generic fallback. Empty when the Google field is empty.
 *
 * @param string $family Sanitized family name.
 * @return string
 */
function ekdiloseis_google_font_css( $family ) {
	$family = ekdiloseis_sanitize_google_font( $family );
	if ( '' === $family ) {
		return '';
	}
	return '"' . $family . '", sans-serif';
}

/**
 * Keep only a known stack. Anything else is empty so the current font stays.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function ekdiloseis_sanitize_font_family( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = wp_unslash( $value );
	$value = str_replace( array( '\"', "\'" ), array( '"', "'" ), $value );
	if ( in_array( $value, ekdiloseis_font_stacks(), true ) ) {
		return $value;
	}
	return '';
}

/**
 * #rgb or #rrggbb, or empty when the value is missing or invalid.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function ekdiloseis_sanitize_style_color( $value ) {
	if ( ! is_string( $value ) || '' === $value ) {
		return '';
	}
	$color = sanitize_hex_color( $value );
	return is_string( $color ) ? $color : '';
}

/**
 * One engine row. Invalid pieces are dropped so the front end can keep the current look.
 *
 * @param mixed $row Submitted or stored row.
 * @param bool  $fill_defaults When true (settings save), missing pieces become the form defaults.
 * @param string $engine Engine key. Only custom keeps color_selected.
 * @return array<string, int|string>
 */
function ekdiloseis_sanitize_style_row( $row, $fill_defaults, $engine = '' ) {
	$defaults = ekdiloseis_default_style();
	if ( ! is_array( $row ) ) {
		return $fill_defaults ? $defaults : array();
	}

	$font = ekdiloseis_sanitize_font_family( $row['font_family'] ?? '' );
	if ( '' === $font && $fill_defaults ) {
		$font = $defaults['font_family'];
	}

	$google = ekdiloseis_sanitize_google_font( $row['google_font'] ?? '' );

	$size = null;
	if ( isset( $row['font_size'] ) && '' !== $row['font_size'] && is_numeric( $row['font_size'] ) ) {
		$size = (int) $row['font_size'];
		if ( $size < 10 || $size > 32 ) {
			$size = $fill_defaults ? 14 : null;
		}
	} elseif ( $fill_defaults ) {
		$size = 14;
	}

	$colors = array();
	// Event color comes from categories, not a calendar-wide accent.
	foreach ( array( 'color_text', 'color_bg' ) as $key ) {
		$color = ekdiloseis_sanitize_style_color( $row[ $key ] ?? '' );
		if ( '' === $color && $fill_defaults ) {
			$color = $defaults[ $key ];
		}
		if ( '' !== $color ) {
			$colors[ $key ] = $color;
		}
	}
	// Selected-day fill exists only on the Default (custom) calendar.
	if ( 'custom' === $engine ) {
		$selected = ekdiloseis_sanitize_selected_color( $row['color_selected'] ?? '' );
		if ( '' === $selected && $fill_defaults ) {
			$selected = ekdiloseis_default_selected_color();
		}
		if ( '' !== $selected ) {
			$colors['color_selected'] = $selected;
		}
		// Optional selected-day number color. Empty means automatic (contrast with the fill).
		// The settings form sends color_selected_text_on (hidden 0 + checkbox 1); imports omit it.
		$text_on = true;
		if ( array_key_exists( 'color_selected_text_on', $row ) ) {
			$text_on = '1' === (string) ( is_scalar( $row['color_selected_text_on'] ) ? $row['color_selected_text_on'] : '' );
		}
		if ( $text_on ) {
			$selected_text = ekdiloseis_sanitize_selected_color( $row['color_selected_text'] ?? '' );
			if ( '' !== $selected_text ) {
				$colors['color_selected_text'] = $selected_text;
			}
		}
	}

	$clean = array();
	if ( '' !== $font ) {
		$clean['font_family'] = $font;
	}
	if ( '' !== $google ) {
		$clean['google_font'] = $google;
	}
	if ( null !== $size ) {
		$clean['font_size'] = $size;
	}
	return array_merge( $clean, $colors );
}

/**
 * Settings API sanitizer for ekdiloseis_styles. Unknown engines are dropped.
 *
 * @param mixed $value Submitted value.
 * @return array<string, array<string, int|string>>
 */
function ekdiloseis_sanitize_styles( $value ) {
	$clean = array();
	if ( ! is_array( $value ) ) {
		return $clean;
	}
	foreach ( ekdiloseis_style_engines() as $engine ) {
		if ( ! isset( $value[ $engine ] ) ) {
			continue;
		}
		$row = ekdiloseis_sanitize_style_row( $value[ $engine ], true, $engine );
		if ( $row ) {
			$clean[ $engine ] = $row;
		}
	}
	return $clean;
}

/**
 * Saved style for one engine, or null when nothing usable is stored.
 *
 * @param string $engine Engine key.
 * @return array<string, int|string>|null
 */
function ekdiloseis_get_engine_style( $engine ) {
	if ( ! in_array( $engine, ekdiloseis_style_engines(), true ) ) {
		return null;
	}
	$all = get_option( 'ekdiloseis_styles', array() );
	if ( ! is_array( $all ) || ! isset( $all[ $engine ] ) || ! is_array( $all[ $engine ] ) ) {
		return null;
	}
	$row = ekdiloseis_sanitize_style_row( $all[ $engine ], false, $engine );
	return $row ? $row : null;
}

/**
 * Inline custom properties for one engine root. Empty when that engine has no saved style.
 *
 * @param string $engine Engine key.
 * @return string
 */
function ekdiloseis_root_style_attr( $engine ) {
	$row = ekdiloseis_get_engine_style( $engine );
	if ( null === $row ) {
		return '';
	}
	$parts = array();
	$google_css = '';
	if ( isset( $row['google_font'] ) && is_string( $row['google_font'] ) ) {
		$google_css = ekdiloseis_google_font_css( $row['google_font'] );
	}
	if ( '' !== $google_css ) {
		$parts[] = '--ekd-font-family:' . $google_css;
	} elseif ( isset( $row['font_family'] ) && is_string( $row['font_family'] ) ) {
		$parts[] = '--ekd-font-family:' . $row['font_family'];
	}
	if ( isset( $row['font_size'] ) ) {
		$parts[] = '--ekd-font-size:' . (int) $row['font_size'] . 'px';
	}
	$map = array(
		'color_text' => '--ekd-color',
		'color_bg'   => '--ekd-bg',
	);
	foreach ( $map as $key => $var ) {
		if ( isset( $row[ $key ] ) && is_string( $row[ $key ] ) ) {
			$parts[] = $var . ':' . $row[ $key ];
		}
	}
	if ( 'custom' === $engine && isset( $row['color_selected'] ) && is_string( $row['color_selected'] ) ) {
		$parts[] = '--ekd-selected:' . $row['color_selected'];
		$selected_text = ( isset( $row['color_selected_text'] ) && is_string( $row['color_selected_text'] ) )
			? $row['color_selected_text']
			: ekdiloseis_contrast_text_color( $row['color_selected'] );
		$parts[] = '--ekd-selected-text:' . $selected_text;
	} elseif ( 'custom' === $engine && isset( $row['color_selected_text'] ) && is_string( $row['color_selected_text'] ) ) {
		$parts[] = '--ekd-selected-text:' . $row['color_selected_text'];
	}
	if ( ! $parts ) {
		return '';
	}
	return ' style="' . esc_attr( implode( ';', $parts ) ) . '"';
}

/**
 * Form values for one engine. Missing storage uses the form defaults and does not write them.
 *
 * @param string $engine Engine key.
 * @return array<string, int|string>
 */
function ekdiloseis_style_form_values( $engine ) {
	$values = ekdiloseis_default_style();
	if ( 'custom' === $engine ) {
		$values['color_selected'] = ekdiloseis_default_selected_color();
	}
	$row    = ekdiloseis_get_engine_style( $engine );
	if ( null === $row ) {
		return $values;
	}
	return array_merge( $values, $row );
}


/**
 * Custom CSS stored in ekdiloseis_custom_css. Empty when missing.
 * Dangerous sequences are removed. The value is not printed in wp-admin.
 *
 * @return string
 */
function ekdiloseis_get_custom_css() {
	$css = get_option( 'ekdiloseis_custom_css', '' );
	if ( ! is_string( $css ) ) {
		return '';
	}
	$css = ekdiloseis_sanitize_custom_css( $css );
	if ( '' === trim( $css ) ) {
		return '';
	}
	return $css;
}

/**
 * Settings API sanitizer for ekdiloseis_custom_css.
 * Drops style breakouts, javascript, expression(, @import, and the behavior property.
 * scroll-behavior and other normal CSS stay. Expects an unslashed string, as options.php provides.
 *
 * @param mixed $value Submitted or imported value.
 * @return string
 */
function ekdiloseis_sanitize_custom_css( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$css = wp_check_invalid_utf8( $value );
	if ( ! is_string( $css ) ) {
		return '';
	}
	$css = str_replace( "\0", '', $css );

	$patterns = array(
		'#<\s*/\s*style#i',
		'#javascript#i',
		'#expression\s*\(#i',
		'#@\s*import\b[^;]*;?#i',
		'#(?<![\w-])behavior(?![\w-])#i',
	);

	$previous = null;
	$guard    = 0;
	while ( $previous !== $css && $guard < 8 ) {
		$previous = $css;
		foreach ( $patterns as $pattern ) {
			$replaced = preg_replace( $pattern, '', $css );
			if ( is_string( $replaced ) ) {
				$css = $replaced;
			}
		}
		++$guard;
	}

	if ( strlen( $css ) > 100000 ) {
		$css = substr( $css, 0, 100000 );
	}
	return $css;
}

/**
 * Settings API registration. Default is the existing custom calendar.
 */
function ekdiloseis_register_settings() {
	register_setting(
		'ekdiloseis_settings',
		'ekdiloseis_engine',
		array(
			'type'              => 'string',
			'description'       => 'Calendar engine for the [ekdiloseis] shortcode.',
			'sanitize_callback' => 'ekdiloseis_sanitize_engine',
			'default'           => 'custom',
			'show_in_rest'      => false,
		)
	);

	register_setting(
		'ekdiloseis_settings',
		'ekdiloseis_locale',
		array(
			'type'              => 'string',
			'description'       => 'Language of month and weekday names on the public calendar.',
			'sanitize_callback' => 'ekdiloseis_sanitize_locale',
			'default'           => 'en',
			'show_in_rest'      => false,
		)
	);

	register_setting(
		'ekdiloseis_settings',
		'ekdiloseis_layout',
		array(
			'type'              => 'string',
			'description'       => 'Where the day event list sits relative to the calendar.',
			'sanitize_callback' => 'ekdiloseis_sanitize_layout',
			'default'           => 'stacked',
			'show_in_rest'      => false,
		)
	);

	register_setting(
		'ekdiloseis_settings',
		'ekdiloseis_list_media',
		array(
			'type'              => 'string',
			'description'       => 'Featured image, icon, or nothing on each event in the day list.',
			'sanitize_callback' => 'ekdiloseis_sanitize_list_media',
			'default'           => 'image',
			'show_in_rest'      => false,
		)
	);

	register_setting(
		'ekdiloseis_settings',
		'ekdiloseis_styles',
		array(
			'type'              => 'array',
			'description'       => 'Appearance for each calendar engine.',
			'sanitize_callback' => 'ekdiloseis_sanitize_styles',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);

	register_setting(
		'ekdiloseis_settings',
		'ekdiloseis_custom_css',
		array(
			'type'              => 'string',
			'description'       => 'Custom CSS printed on the public calendar after the plugin stylesheet.',
			'sanitize_callback' => 'ekdiloseis_sanitize_custom_css',
			'default'           => '',
			'show_in_rest'      => false,
		)
	);

	add_settings_section(
		'ekdiloseis_engine_section',
		'Calendar',
		'ekdiloseis_engine_section_cb',
		'ekdiloseis-settings'
	);

	add_settings_field(
		'ekdiloseis_engine',
		'Calendar engine',
		'ekdiloseis_engine_field_cb',
		'ekdiloseis-settings',
		'ekdiloseis_engine_section'
	);

	add_settings_field(
		'ekdiloseis_locale',
		'Language',
		'ekdiloseis_locale_field_cb',
		'ekdiloseis-settings',
		'ekdiloseis_engine_section'
	);

	add_settings_field(
		'ekdiloseis_layout',
		'Layout',
		'ekdiloseis_layout_field_cb',
		'ekdiloseis-settings',
		'ekdiloseis_engine_section'
	);

	add_settings_field(
		'ekdiloseis_list_media',
		'List media',
		'ekdiloseis_list_media_field_cb',
		'ekdiloseis-settings',
		'ekdiloseis_engine_section'
	);

	add_settings_field(
		'ekdiloseis_styles',
		'Appearance',
		'ekdiloseis_styles_field_cb',
		'ekdiloseis-settings',
		'ekdiloseis_engine_section'
	);

	add_settings_field(
		'ekdiloseis_custom_css',
		'Custom CSS',
		'ekdiloseis_custom_css_field_cb',
		'ekdiloseis-settings',
		'ekdiloseis_engine_section'
	);
}
add_action( 'admin_init', 'ekdiloseis_register_settings' );

/**
 * Settings form is limited to administrators.
 *
 * @return string
 */
function ekdiloseis_settings_capability() {
	return 'manage_options';
}
add_filter( 'option_page_capability_ekdiloseis_settings', 'ekdiloseis_settings_capability' );

/**
 * Section description.
 */
function ekdiloseis_engine_section_cb() {
	echo '<p>';
	echo esc_html( 'Choose which calendar the [ekdiloseis] shortcode displays. Default keeps the built-in calendar so existing pages do not change on their own.' );
	echo '</p>';
}

/**
 * Engine radio field.
 */
function ekdiloseis_engine_field_cb() {
	$engine = ekdiloseis_get_engine();
	$labels = ekdiloseis_engine_labels();
	?>
	<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( 'Calendar engine' ); ?></legend>
		<?php foreach ( $labels as $value => $label ) : ?>
			<label>
				<input type="radio" name="ekdiloseis_engine" value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $engine ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<br>
		<?php endforeach; ?>
	</fieldset>
	<?php
}

/**
 * Public calendar language. Does not change admin menu or post type labels.
 */
function ekdiloseis_locale_field_cb() {
	$locale  = ekdiloseis_get_locale();
	$options = array(
		'en' => 'English',
		'el' => 'Greek',
	);
	?>
	<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( 'Language' ); ?></legend>
		<?php foreach ( $options as $value => $label ) : ?>
			<label>
				<input type="radio" name="ekdiloseis_locale" value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $locale ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<br>
		<?php endforeach; ?>
	</fieldset>
	<p class="description"><?php echo esc_html( 'Month and weekday names on the public calendar. Admin screens stay in English.' ); ?></p>
	<?php
}

/**
 * Day event list placement. Does not change the calendar engine.
 */
function ekdiloseis_layout_field_cb() {
	$layout  = ekdiloseis_get_layout();
	$options = array(
		'stacked' => 'Below the calendar',
		'columns' => 'Two columns',
	);
	?>
	<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( 'Layout' ); ?></legend>
		<?php foreach ( $options as $value => $label ) : ?>
			<label>
				<input type="radio" name="ekdiloseis_layout" value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $layout ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<br>
		<?php endforeach; ?>
	</fieldset>
	<p class="description"><?php echo esc_html( 'Below the calendar keeps the day list under the month. Two columns places the calendar on the left and the day list on the right on wide screens.' ); ?></p>
	<?php
}

/**
 * Featured image, Font Awesome icon, or nothing on the shared day list.
 */
function ekdiloseis_list_media_field_cb() {
	$media   = ekdiloseis_get_list_media();
	$options = array(
		'image' => 'Featured image',
		'icon'  => 'Icon',
		'none'  => 'None',
	);
	?>
	<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( 'List media' ); ?></legend>
		<?php foreach ( $options as $value => $label ) : ?>
			<label>
				<input type="radio" name="ekdiloseis_list_media" value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $media ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<br>
		<?php endforeach; ?>
	</fieldset>
	<p class="description"><?php echo esc_html( 'Featured image keeps the photo. Icon shows a Font Awesome icon instead. None shows neither.' ); ?></p>
	<?php
}

/**
 * Per-engine font, size, and colors in tabs. Each group writes its own key in ekdiloseis_styles.
 * Inactive panels stay in the DOM (CSS-hidden, not disabled) so Save posts every engine.
 */
function ekdiloseis_styles_field_cb() {
	$labels       = ekdiloseis_engine_labels();
	$stacks       = ekdiloseis_font_stacks();
	$active       = ekdiloseis_get_engine();
	$first_engine = array_key_first( $labels );
	if ( ! isset( $labels[ $active ] ) ) {
		$active = $first_engine;
	}
	?>
	<p><?php echo esc_html( 'Event colors come from categories, with an optional color on each event. These settings do not set the event color.' ); ?></p>
	<div class="ekdiloseis-style-tabs">
		<div class="ekdiloseis-style-tablist" role="tablist" aria-label="<?php echo esc_attr( 'Appearance' ); ?>">
			<?php foreach ( $labels as $engine => $label ) : ?>
				<?php
				$is_active = ( $engine === $active );
				$tab_id    = 'ekdiloseis-style-tab-' . $engine;
				$panel_id  = 'ekdiloseis-style-panel-' . $engine;
				?>
				<button
					type="button"
					class="ekdiloseis-style-tab<?php echo $is_active ? ' is-active' : ''; ?>"
					role="tab"
					id="<?php echo esc_attr( $tab_id ); ?>"
					data-engine="<?php echo esc_attr( $engine ); ?>"
					aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
					aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
				><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
		<?php foreach ( $labels as $engine => $label ) : ?>
			<?php
			$values    = ekdiloseis_style_form_values( $engine );
			$is_active = ( $engine === $active );
			$tab_id    = 'ekdiloseis-style-tab-' . $engine;
			$panel_id  = 'ekdiloseis-style-panel-' . $engine;
			?>
			<fieldset
				class="ekdiloseis-style-panel<?php echo $is_active ? ' is-active' : ''; ?>"
				role="tabpanel"
				id="<?php echo esc_attr( $panel_id ); ?>"
				data-engine="<?php echo esc_attr( $engine ); ?>"
				aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
			>
				<legend class="screen-reader-text"><?php echo esc_html( $label ); ?></legend>
				<div class="ekdiloseis-font-fields">
					<p>
						<label for="<?php echo esc_attr( 'ekdiloseis-font-' . $engine ); ?>"><?php echo esc_html( 'Font' ); ?></label><br>
						<select id="<?php echo esc_attr( 'ekdiloseis-font-' . $engine ); ?>" name="<?php echo esc_attr( 'ekdiloseis_styles[' . $engine . '][font_family]' ); ?>">
							<?php foreach ( $stacks as $stack_label => $stack ) : ?>
								<option value="<?php echo esc_attr( $stack ); ?>" <?php selected( $stack, $values['font_family'] ); ?>><?php echo esc_html( $stack_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p>
						<label for="<?php echo esc_attr( 'ekdiloseis-google-font-' . $engine ); ?>"><?php echo esc_html( 'Google Font' ); ?></label><br>
						<input id="<?php echo esc_attr( 'ekdiloseis-google-font-' . $engine ); ?>" name="<?php echo esc_attr( 'ekdiloseis_styles[' . $engine . '][google_font]' ); ?>" type="text" value="<?php echo esc_attr( (string) $values['google_font'] ); ?>" maxlength="80" autocomplete="off" spellcheck="false">
					</p>
				</div>
				<p class="description"><?php echo esc_html( 'Leave Google Font empty to use Font. A family name such as Roboto or Open Sans replaces it for this calendar.' ); ?></p>
				<p>
					<label for="<?php echo esc_attr( 'ekdiloseis-size-' . $engine ); ?>"><?php echo esc_html( 'Size' ); ?></label><br>
					<input id="<?php echo esc_attr( 'ekdiloseis-size-' . $engine ); ?>" name="<?php echo esc_attr( 'ekdiloseis_styles[' . $engine . '][font_size]' ); ?>" type="number" min="10" max="32" step="1" value="<?php echo esc_attr( (string) $values['font_size'] ); ?>">
					<?php echo esc_html( 'px' ); ?>
				</p>
				<p>
					<label for="<?php echo esc_attr( 'ekdiloseis-text-' . $engine ); ?>"><?php echo esc_html( 'Text color' ); ?></label><br>
					<input id="<?php echo esc_attr( 'ekdiloseis-text-' . $engine ); ?>" name="<?php echo esc_attr( 'ekdiloseis_styles[' . $engine . '][color_text]' ); ?>" type="color" value="<?php echo esc_attr( $values['color_text'] ); ?>">
				</p>
				<p>
					<label for="<?php echo esc_attr( 'ekdiloseis-bg-' . $engine ); ?>"><?php echo esc_html( 'Background color' ); ?></label><br>
					<input id="<?php echo esc_attr( 'ekdiloseis-bg-' . $engine ); ?>" name="<?php echo esc_attr( 'ekdiloseis_styles[' . $engine . '][color_bg]' ); ?>" type="color" value="<?php echo esc_attr( $values['color_bg'] ); ?>">
				</p>
				<?php if ( 'custom' === $engine ) : ?>
					<p>
						<label for="ekdiloseis-selected-custom"><?php echo esc_html( 'Selected day color' ); ?></label><br>
						<input id="ekdiloseis-selected-custom" name="<?php echo esc_attr( 'ekdiloseis_styles[custom][color_selected]' ); ?>" type="color" value="<?php echo esc_attr( (string) $values['color_selected'] ); ?>">
					</p>
					<p class="description"><?php echo esc_html( 'Fill and border of the selected day. The day number switches to white or dark automatically for contrast, unless Selected day text color is set below.' ); ?></p>
					<?php
					$has_selected_text    = isset( $values['color_selected_text'] ) && is_string( $values['color_selected_text'] ) && '' !== $values['color_selected_text'];
					$selected_text_picker = $has_selected_text ? $values['color_selected_text'] : ekdiloseis_contrast_text_color( (string) $values['color_selected'] );
					$selected_text_picker = ekdiloseis_sanitize_selected_color( $selected_text_picker );
					if ( '' === $selected_text_picker ) {
						$selected_text_picker = '#ffffff';
					}
					?>
					<p>
						<input type="hidden" name="<?php echo esc_attr( 'ekdiloseis_styles[custom][color_selected_text_on]' ); ?>" value="0">
						<label>
							<input type="checkbox" id="ekdiloseis-selected-text-on" name="<?php echo esc_attr( 'ekdiloseis_styles[custom][color_selected_text_on]' ); ?>" value="1" <?php checked( $has_selected_text ); ?>>
							<?php echo esc_html( 'Custom text color' ); ?>
						</label>
					</p>
					<p>
						<label for="ekdiloseis-selected-text-custom"><?php echo esc_html( 'Selected day text color' ); ?></label><br>
						<input id="ekdiloseis-selected-text-custom" name="<?php echo esc_attr( 'ekdiloseis_styles[custom][color_selected_text]' ); ?>" type="color" value="<?php echo esc_attr( $selected_text_picker ); ?>">
					</p>
					<p class="description"><?php echo esc_html( 'Color of the day number on the selected day. Empty (Custom text color not checked) = automatic: white or dark, whichever is easier to read on Selected day color.' ); ?></p>
				<?php endif; ?>
			</fieldset>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Custom CSS textarea. The value is saved with the rest of Event settings.
 * It is shown as text here and is not applied in wp-admin.
 */
function ekdiloseis_custom_css_field_cb() {
	$css = ekdiloseis_get_custom_css();
	?>
	<textarea id="ekdiloseis_custom_css" name="ekdiloseis_custom_css" rows="10" cols="60" class="large-text code" spellcheck="false"><?php echo esc_textarea( $css ); ?></textarea>
	<p class="description"><?php echo esc_html( 'Printed on the public page after the plugin stylesheet when that page contains [myevents]. It is not applied in admin.' ); ?></p>
	<?php
}

/**
 * Submenu under the events list.
 */
function ekdiloseis_add_settings_page() {
	$hook = add_submenu_page(
		'edit.php?post_type=ekdilosi',
		'Event settings',
		'Settings',
		'manage_options',
		'ekdiloseis-settings',
		'ekdiloseis_render_settings_page'
	);
	if ( is_string( $hook ) && '' !== $hook ) {
		add_action( 'load-' . $hook, 'ekdiloseis_load_settings_assets' );
	}
}
add_action( 'admin_menu', 'ekdiloseis_add_settings_page', 20 );

/**
 * Enqueue settings-page tabs CSS/JS only on this screen.
 */
function ekdiloseis_load_settings_assets() {
	add_action( 'admin_enqueue_scripts', 'ekdiloseis_enqueue_settings_assets' );
}

/**
 * Local admin CSS/JS for appearance tabs (no CDN).
 */
function ekdiloseis_enqueue_settings_assets() {
	$css = EKDILOSEIS_DIR . 'assets/ekdiloseis-settings.css';
	$js  = EKDILOSEIS_DIR . 'assets/ekdiloseis-settings.js';
	$ver = EKDILOSEIS_VERSION;
	if ( is_readable( $css ) ) {
		$ver = (string) filemtime( $css );
		wp_enqueue_style(
			'ekdiloseis-settings',
			EKDILOSEIS_URL . 'assets/ekdiloseis-settings.css',
			array(),
			$ver
		);
	}
	if ( is_readable( $js ) ) {
		$ver = (string) filemtime( $js );
		wp_enqueue_script(
			'ekdiloseis-settings',
			EKDILOSEIS_URL . 'assets/ekdiloseis-settings.js',
			array(),
			$ver,
			true
		);
	}
}

/**
 * English label for the Settings API success notice on this screen only.
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 * @return string
 */
function ekdiloseis_settings_saved_label( $translated, $text, $domain ) {
	unset( $domain );
	if ( 'Settings saved.' !== $text ) {
		return $translated;
	}
	if ( ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) || 'ekdiloseis-settings' !== $_GET['page'] ) {
		return $translated;
	}
	return 'Settings saved.';
}
add_filter( 'gettext', 'ekdiloseis_settings_saved_label', 10, 3 );

/**
 * Settings screen.
 */
function ekdiloseis_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'You do not have permission to access this page.' ), 403 );
	}
	$export_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=ekdiloseis_export_settings' ),
		'ekdiloseis_export_settings'
	);
	?>
	<div class="wrap">
		<h1><?php echo esc_html( 'Event settings' ); ?></h1>
		<?php settings_errors(); ?>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'ekdiloseis_settings' );
			do_settings_sections( 'ekdiloseis-settings' );
			submit_button( 'Save' );
			?>
		</form>
		<div class="ekdiloseis-transfer">
			<h2><?php echo esc_html( 'Import and export' ); ?></h2>
			<p class="description"><?php echo esc_html( 'Download the plugin settings, or replace them from a file exported here. Events are not included and are not changed.' ); ?></p>
			<p>
				<a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php echo esc_html( 'Export' ); ?></a>
			</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'ekdiloseis_import_settings' ); ?>
				<input type="hidden" name="action" value="ekdiloseis_import_settings">
				<p>
					<label for="ekdiloseis-settings-file"><?php echo esc_html( 'Settings file' ); ?></label><br>
					<input type="file" id="ekdiloseis-settings-file" name="ekdiloseis_settings_file" accept="application/json,.json">
				</p>
				<?php submit_button( 'Replace settings', 'secondary', 'ekdiloseis_replace_settings', false ); ?>
			</form>
		</div>
	</div>
	<?php
}

/**
 * Plugin options that export and import may read or write. No posts, terms, or secrets.
 *
 * @return string[]
 */
function ekdiloseis_settings_transfer_keys() {
	return array(
		'ekdiloseis_engine',
		'ekdiloseis_locale',
		'ekdiloseis_layout',
		'ekdiloseis_list_media',
		'ekdiloseis_styles',
		'ekdiloseis_custom_css',
	);
}

/**
 * JSON body for events-settings.json. Does not write options.
 *
 * @return array<string, mixed>
 */
function ekdiloseis_export_settings_payload() {
	$styles = get_option( 'ekdiloseis_styles', array() );
	if ( ! is_array( $styles ) ) {
		$styles = array();
	}
	return array(
		'ekdiloseis_engine'     => ekdiloseis_get_engine(),
		'ekdiloseis_locale'     => ekdiloseis_get_locale(),
		'ekdiloseis_layout'     => ekdiloseis_get_layout(),
		'ekdiloseis_list_media' => ekdiloseis_get_list_media(),
		'ekdiloseis_styles'     => ekdiloseis_sanitize_styles( $styles ),
		'ekdiloseis_custom_css' => ekdiloseis_get_custom_css(),
	);
}

/**
 * True when the array is a JSON list with at least one element. An empty array is not a list.
 *
 * @param array<mixed> $value Decoded value.
 * @return bool
 */
function ekdiloseis_is_json_list( $value ) {
	if ( ! is_array( $value ) || array() === $value ) {
		return false;
	}
	$index = 0;
	foreach ( array_keys( $value ) as $key ) {
		if ( $key !== $index ) {
			return false;
		}
		++$index;
	}
	return true;
}

/**
 * Check a settings JSON document and sanitize each known value.
 * Unknown keys and the wrong shape are rejected. Nothing is written here.
 *
 * @param string $raw File contents.
 * @return array<string, mixed>|WP_Error
 */
function ekdiloseis_parse_settings_import( $raw ) {
	if ( ! is_string( $raw ) ) {
		return new WP_Error( 'ekdiloseis_import', 'The settings file is not valid JSON.' );
	}
	if ( str_starts_with( $raw, "\xEF\xBB\xBF" ) ) {
		$raw = substr( $raw, 3 );
	}
	$data = json_decode( $raw, true );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
		return new WP_Error( 'ekdiloseis_import', 'The settings file is not valid JSON.' );
	}
	foreach ( array_keys( $data ) as $key ) {
		if ( ! is_string( $key ) ) {
			return new WP_Error( 'ekdiloseis_import', 'The settings file has an unexpected shape.' );
		}
	}

	$allowed = ekdiloseis_settings_transfer_keys();
	$unknown = array_diff( array_keys( $data ), $allowed );
	if ( $unknown ) {
		return new WP_Error( 'ekdiloseis_import', 'The settings file contains unknown settings.' );
	}
	$missing = array_diff( $allowed, array_keys( $data ) );
	if ( $missing ) {
		return new WP_Error( 'ekdiloseis_import', 'The settings file has an unexpected shape.' );
	}

	$string_keys = array(
		'ekdiloseis_engine',
		'ekdiloseis_locale',
		'ekdiloseis_layout',
		'ekdiloseis_list_media',
		'ekdiloseis_custom_css',
	);
	foreach ( $string_keys as $key ) {
		if ( ! is_string( $data[ $key ] ) ) {
			return new WP_Error( 'ekdiloseis_import', 'The settings file has an unexpected shape.' );
		}
	}
	if ( ! is_array( $data['ekdiloseis_styles'] ) || ekdiloseis_is_json_list( $data['ekdiloseis_styles'] ) ) {
		return new WP_Error( 'ekdiloseis_import', 'The settings file has an unexpected shape.' );
	}

	return array(
		'ekdiloseis_engine'     => ekdiloseis_sanitize_engine( $data['ekdiloseis_engine'] ),
		'ekdiloseis_locale'     => ekdiloseis_sanitize_locale( $data['ekdiloseis_locale'] ),
		'ekdiloseis_layout'     => ekdiloseis_sanitize_layout( $data['ekdiloseis_layout'] ),
		'ekdiloseis_list_media' => ekdiloseis_sanitize_list_media( $data['ekdiloseis_list_media'] ),
		'ekdiloseis_styles'     => ekdiloseis_sanitize_styles( $data['ekdiloseis_styles'] ),
		'ekdiloseis_custom_css' => ekdiloseis_sanitize_custom_css( $data['ekdiloseis_custom_css'] ),
	);
}

/**
 * Store a notice and return to Event settings. Does not change options.
 *
 * @param string $message English notice.
 * @param string $type    success|error.
 */
function ekdiloseis_redirect_settings_notice( $message, $type ) {
	add_settings_error( 'ekdiloseis_settings', 'ekdiloseis_import', $message, $type );
	set_transient( 'settings_errors', get_settings_errors(), 30 );
	wp_safe_redirect( admin_url( 'edit.php?post_type=ekdilosi&page=ekdiloseis-settings' ) );
	exit;
}

/**
 * Download events-settings.json. Administrators only.
 */
function ekdiloseis_handle_export_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'You do not have permission to access this page.' ), 403 );
	}
	check_admin_referer( 'ekdiloseis_export_settings' );

	$json = wp_json_encode(
		ekdiloseis_export_settings_payload(),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	if ( ! is_string( $json ) || '' === $json ) {
		ekdiloseis_redirect_settings_notice( 'The settings file could not be exported.', 'error' );
	}

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="events-settings.json"' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Length: ' . (string) strlen( $json ) );
	echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download, not HTML.
	exit;
}
add_action( 'admin_post_ekdiloseis_export_settings', 'ekdiloseis_handle_export_settings' );

/**
 * Replace plugin settings from an uploaded JSON file. Events are not touched.
 * Options are updated only after the whole file is valid.
 */
function ekdiloseis_handle_import_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'You do not have permission to access this page.' ), 403 );
	}
	check_admin_referer( 'ekdiloseis_import_settings' );

	if ( ! isset( $_POST['ekdiloseis_replace_settings'] ) ) {
		ekdiloseis_redirect_settings_notice( 'Settings were not replaced.', 'error' );
	}

	if ( ! isset( $_FILES['ekdiloseis_settings_file'] ) || ! is_array( $_FILES['ekdiloseis_settings_file'] ) ) {
		ekdiloseis_redirect_settings_notice( 'Choose a JSON settings file.', 'error' );
	}

	$file  = $_FILES['ekdiloseis_settings_file'];
	$error = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
	if ( UPLOAD_ERR_NO_FILE === $error ) {
		ekdiloseis_redirect_settings_notice( 'Choose a JSON settings file.', 'error' );
	}
	if ( UPLOAD_ERR_OK !== $error ) {
		ekdiloseis_redirect_settings_notice( 'The settings file could not be uploaded.', 'error' );
	}

	$name = isset( $file['name'] ) && is_string( $file['name'] ) ? $file['name'] : '';
	if ( ! preg_match( '/\.json$/i', $name ) ) {
		ekdiloseis_redirect_settings_notice( 'Upload a .json settings file.', 'error' );
	}

	$tmp = isset( $file['tmp_name'] ) && is_string( $file['tmp_name'] ) ? $file['tmp_name'] : '';
	if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
		ekdiloseis_redirect_settings_notice( 'The settings file could not be uploaded.', 'error' );
	}

	$size = isset( $file['size'] ) ? (int) $file['size'] : 0;
	if ( $size < 1 || $size > 512000 ) {
		ekdiloseis_redirect_settings_notice( 'The settings file is empty or too large.', 'error' );
	}

	$raw = file_get_contents( $tmp );
	if ( ! is_string( $raw ) ) {
		ekdiloseis_redirect_settings_notice( 'The settings file could not be read.', 'error' );
	}

	$clean = ekdiloseis_parse_settings_import( $raw );
	if ( is_wp_error( $clean ) ) {
		$message = $clean->get_error_message();
		if ( ! is_string( $message ) || '' === $message ) {
			$message = 'The settings file could not be imported.';
		}
		ekdiloseis_redirect_settings_notice( $message, 'error' );
	}

	foreach ( ekdiloseis_settings_transfer_keys() as $key ) {
		update_option( $key, $clean[ $key ] );
	}

	ekdiloseis_redirect_settings_notice( 'Settings replaced.', 'success' );
}
add_action( 'admin_post_ekdiloseis_import_settings', 'ekdiloseis_handle_import_settings' );
