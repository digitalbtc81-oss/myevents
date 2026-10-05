<?php
/**
 * Start and end meta box.
 *
 * @package Ekdiloseis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse a stored or submitted datetime into MySQL format in Europe/Athens wall time.
 *
 * @param mixed $raw Raw value.
 * @return string Y-m-d H:i:s or empty string when invalid.
 */
function ekdiloseis_normalize_datetime( $raw ) {
	if ( ! is_string( $raw ) ) {
		return '';
	}
	$raw = trim( str_replace( 'T', ' ', wp_strip_all_tags( $raw ) ) );
	if ( preg_match( '/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})$/', $raw, $matches ) ) {
		$raw = $matches[1] . ' ' . $matches[2] . ':00';
	}
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw ) ) {
		return '';
	}

	$tz = new DateTimeZone( 'Europe/Athens' );
	$dt = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $raw, $tz );
	if ( ! $dt instanceof DateTimeImmutable ) {
		return '';
	}
	$errors = DateTimeImmutable::getLastErrors();
	if ( is_array( $errors ) && ( ( $errors['warning_count'] ?? 0 ) > 0 || ( $errors['error_count'] ?? 0 ) > 0 ) ) {
		return '';
	}
	if ( $dt->format( 'Y-m-d H:i:s' ) !== $raw ) {
		return '';
	}
	return $raw;
}

/**
 * Parse an admin date as dd/mm/yyyy or dd/mm/yy.
 *
 * Two-digit years use the common pivot: 00-69 are 2000-2069, 70-99 are 1970-1999.
 *
 * @param string $raw Date text.
 * @return string Y-m-d or empty when invalid.
 */
function ekdiloseis_parse_admin_date( $raw ) {
	if ( ! is_string( $raw ) ) {
		return '';
	}
	$raw = trim( $raw );
	if ( ! preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{2}|\d{4})$/', $raw, $matches ) ) {
		return '';
	}
	$day       = (int) $matches[1];
	$month     = (int) $matches[2];
	$year_text = $matches[3];
	if ( 2 === strlen( $year_text ) ) {
		$yy   = (int) $year_text;
		$year = $yy >= 70 ? 1900 + $yy : 2000 + $yy;
	} else {
		$year = (int) $year_text;
	}
	if ( ! checkdate( $month, $day, $year ) ) {
		return '';
	}
	return sprintf( '%04d-%02d-%02d', $year, $month, $day );
}

/**
 * Parse an admin time as H:MM or H:MM:SS (24-hour).
 *
 * @param string $raw Time text.
 * @return string H:i:s or empty when invalid.
 */
function ekdiloseis_parse_admin_time( $raw ) {
	if ( ! is_string( $raw ) ) {
		return '';
	}
	$raw = trim( $raw );
	if ( ! preg_match( '/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $matches ) ) {
		return '';
	}
	$hour   = (int) $matches[1];
	$minute = (int) $matches[2];
	$second = isset( $matches[3] ) && '' !== $matches[3] ? (int) $matches[3] : 0;
	if ( $hour > 23 || $minute > 59 || $second > 59 ) {
		return '';
	}
	return sprintf( '%02d:%02d:%02d', $hour, $minute, $second );
}

/**
 * Turn submitted dd/mm/y date and time into stored Y-m-d H:i:s.
 *
 * @param string $date_raw Date field.
 * @param string $time_raw Time field.
 * @return string
 */
function ekdiloseis_submitted_datetime( $date_raw, $time_raw ) {
	$date = ekdiloseis_parse_admin_date( $date_raw );
	$time = ekdiloseis_parse_admin_time( $time_raw );
	if ( '' === $date || '' === $time ) {
		return '';
	}
	return ekdiloseis_normalize_datetime( $date . ' ' . $time );
}

/**
 * Stored datetime as dd/mm/yyyy for the meta box.
 *
 * @param string $stored Stored MySQL datetime.
 * @return string
 */
function ekdiloseis_admin_date_value( $stored ) {
	$normalized = ekdiloseis_normalize_datetime( $stored );
	if ( '' === $normalized ) {
		return '';
	}
	$dt = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $normalized, new DateTimeZone( 'Europe/Athens' ) );
	if ( ! $dt instanceof DateTimeImmutable ) {
		return '';
	}
	return $dt->format( 'd/m/Y' );
}

/**
 * Stored datetime as HH:MM for the meta box.
 *
 * @param string $stored Stored MySQL datetime.
 * @return string
 */
function ekdiloseis_admin_time_value( $stored ) {
	$normalized = ekdiloseis_normalize_datetime( $stored );
	if ( '' === $normalized ) {
		return '';
	}
	return substr( $normalized, 11, 5 );
}

/**
 * One posted text field, unslashed and sanitized.
 *
 * @param string $key Field name.
 * @return string
 */
function ekdiloseis_posted_text( $key ) {
	if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
		return '';
	}
	return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
}

/**
 * Add the schedule meta box.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Post.
 */
function ekdiloseis_add_meta_box( $post_type, $post ) {
	if ( 'ekdilosi' !== $post_type ) {
		return;
	}
	if ( $post instanceof WP_Post && $post->ID && ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}
	add_meta_box(
		'ekdiloseis_schedule',
		'Schedule',
		'ekdiloseis_render_meta_box',
		'ekdilosi',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ekdiloseis_add_meta_box', 10, 2 );

/**
 * Render start and end inputs.
 *
 * @param WP_Post $post Post.
 */
function ekdiloseis_render_meta_box( $post ) {
	$start = get_post_meta( $post->ID, 'event_start', true );
	$end   = get_post_meta( $post->ID, 'event_end', true );
	if ( ! is_string( $start ) ) {
		$start = '';
	}
	if ( ! is_string( $end ) ) {
		$end = '';
	}
	$color_raw    = get_post_meta( $post->ID, 'event_color', true );
	$event_color  = ekdiloseis_normalize_hex_color( is_string( $color_raw ) ? $color_raw : '' );
	$has_override = '' !== $event_color;
	$picker       = $has_override ? $event_color : '#6b7280';
	$icon_raw     = get_post_meta( $post->ID, 'event_icon', true );
	$event_icon   = ekdiloseis_sanitize_icon_class( is_string( $icon_raw ) ? $icon_raw : '' );
	wp_nonce_field( 'ekdiloseis_save_event', 'ekdiloseis_nonce' );
	?>
	<p>
		<label for="ekdiloseis_event_start_date"><strong>Start</strong></label><br>
		<input type="text" id="ekdiloseis_event_start_date" name="ekdiloseis_event_start_date" value="<?php echo esc_attr( ekdiloseis_admin_date_value( $start ) ); ?>" placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off" maxlength="10" size="10">
		<input type="text" id="ekdiloseis_event_start_time" name="ekdiloseis_event_start_time" value="<?php echo esc_attr( ekdiloseis_admin_time_value( $start ) ); ?>" placeholder="HH:MM" inputmode="numeric" autocomplete="off" maxlength="8" size="8" aria-label="Start time">
	</p>
	<p>
		<label for="ekdiloseis_event_end_date"><strong>End</strong></label><br>
		<input type="text" id="ekdiloseis_event_end_date" name="ekdiloseis_event_end_date" value="<?php echo esc_attr( ekdiloseis_admin_date_value( $end ) ); ?>" placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off" maxlength="10" size="10">
		<input type="text" id="ekdiloseis_event_end_time" name="ekdiloseis_event_end_time" value="<?php echo esc_attr( ekdiloseis_admin_time_value( $end ) ); ?>" placeholder="HH:MM" inputmode="numeric" autocomplete="off" maxlength="8" size="8" aria-label="End time">
		<br>
		<span class="description"><?php echo esc_html( 'Date as dd/mm/yyyy or dd/mm/yy, then the time (HH:MM).' ); ?></span>
	</p>
	<p>
		<label>
			<input type="checkbox" id="ekdiloseis_event_color_on" name="ekdiloseis_event_color_on" value="1" <?php checked( $has_override ); ?>>
			<?php echo esc_html( 'Custom color' ); ?>
		</label>
	</p>
	<p>
		<label for="ekdiloseis_event_color"><strong><?php echo esc_html( 'Color (optional)' ); ?></strong></label><br>
		<input type="color" id="ekdiloseis_event_color" name="ekdiloseis_event_color" value="<?php echo esc_attr( $picker ); ?>">
		<br>
		<span class="description"><?php echo esc_html( 'If "Custom color" is not selected, the category color is used.' ); ?></span>
	</p>
	<p>
		<label for="ekdiloseis_event_icon"><strong><?php echo esc_html( 'Icon (optional)' ); ?></strong></label><br>
		<input type="text" id="ekdiloseis_event_icon" name="ekdiloseis_event_icon" value="<?php echo esc_attr( $event_icon ); ?>" placeholder="fa-solid fa-calendar" class="regular-text">
		<br>
		<span class="description"><?php echo esc_html( 'Overrides the category icon when List media is Icon. Leave empty to use the category icon.' ); ?></span>
	</p>
	<script>
	(function () {
		var toggle = document.getElementById("ekdiloseis_event_color_on");
		var input = document.getElementById("ekdiloseis_event_color");
		if (!toggle || !input) {
			return;
		}
		function sync() {
			input.disabled = !toggle.checked;
		}
		toggle.addEventListener("change", sync);
		sync();
	})();
	</script>
	<?php
}

/**
 * Remember that the submitted range was rejected.
 */
function ekdiloseis_flag_range_error( $code = '1' ) {
	if ( 'invalid' !== $code ) {
		$code = '1';
	}
	$user_id = get_current_user_id();
	if ( $user_id ) {
		set_transient( 'ekdiloseis_range_error_' . $user_id, $code, MINUTE_IN_SECONDS );
	}
	$GLOBALS['ekdiloseis_range_error_code'] = $code;
	add_filter( 'redirect_post_location', 'ekdiloseis_redirect_with_range_error' );
}

/**
 * Keep the error visible after the post-save redirect.
 *
 * @param string $location Redirect target.
 * @return string
 */
function ekdiloseis_redirect_with_range_error( $location ) {
	$code = $GLOBALS['ekdiloseis_range_error_code'] ?? '1';
	if ( 'invalid' !== $code ) {
		$code = '1';
	}
	return add_query_arg( 'ekdiloseis_range_error', $code, $location );
}

/**
 * Save start and end. A range that is missing or not strictly increasing is left untouched.
 *
 * @param int $post_id Post ID.
 */
function ekdiloseis_save_event_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['ekdiloseis_nonce'] ) || ! is_string( $_POST['ekdiloseis_nonce'] ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['ekdiloseis_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'ekdiloseis_save_event' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$use_override = isset( $_POST['ekdiloseis_event_color_on'] ) && '1' === (string) wp_unslash( $_POST['ekdiloseis_event_color_on'] );
	if ( ! $use_override ) {
		delete_post_meta( $post_id, 'event_color' );
	} else {
		$color_raw = $_POST['ekdiloseis_event_color'] ?? '';
		if ( ! is_string( $color_raw ) ) {
			$color_raw = '';
		}
		$event_color = ekdiloseis_normalize_hex_color( sanitize_text_field( wp_unslash( $color_raw ) ) );
		if ( '' === $event_color ) {
			delete_post_meta( $post_id, 'event_color' );
		} else {
			update_post_meta( $post_id, 'event_color', $event_color );
		}
	}

	$icon_raw = $_POST['ekdiloseis_event_icon'] ?? '';
	if ( ! is_string( $icon_raw ) ) {
		$icon_raw = '';
	}
	$event_icon = ekdiloseis_sanitize_icon_class( sanitize_text_field( wp_unslash( $icon_raw ) ) );
	if ( '' === $event_icon ) {
		delete_post_meta( $post_id, 'event_icon' );
	} else {
		update_post_meta( $post_id, 'event_icon', $event_icon );
	}

	$start = ekdiloseis_submitted_datetime(
		ekdiloseis_posted_text( 'ekdiloseis_event_start_date' ),
		ekdiloseis_posted_text( 'ekdiloseis_event_start_time' )
	);
	$end   = ekdiloseis_submitted_datetime(
		ekdiloseis_posted_text( 'ekdiloseis_event_end_date' ),
		ekdiloseis_posted_text( 'ekdiloseis_event_end_time' )
	);

	if ( '' === $start || '' === $end ) {
		ekdiloseis_flag_range_error( 'invalid' );
		return;
	}
	if ( $end <= $start ) {
		ekdiloseis_flag_range_error( '1' );
		return;
	}

	update_post_meta( $post_id, 'event_start', $start );
	update_post_meta( $post_id, 'event_end', $end );
}
add_action( 'save_post_ekdilosi', 'ekdiloseis_save_event_meta' );

/**
 * Admin error when a bad range was submitted.
 */
function ekdiloseis_admin_notices() {
	if ( ! is_admin() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'ekdilosi' !== $screen->post_type ) {
		return;
	}

	$user_id   = get_current_user_id();
	$transient = $user_id ? get_transient( 'ekdiloseis_range_error_' . $user_id ) : false;
	$query_raw = isset( $_GET['ekdiloseis_range_error'] ) ? sanitize_text_field( wp_unslash( $_GET['ekdiloseis_range_error'] ) ) : '';
	$code      = '';
	if ( is_string( $transient ) && in_array( $transient, array( '1', 'invalid' ), true ) ) {
		$code = $transient;
	} elseif ( in_array( $query_raw, array( '1', 'invalid' ), true ) ) {
		$code = $query_raw;
	}
	if ( '' === $code ) {
		return;
	}
	if ( $transient && $user_id ) {
		delete_transient( 'ekdiloseis_range_error_' . $user_id );
	}

	if ( 'invalid' === $code ) {
		$message = 'Enter a valid date as dd/mm/yyyy (or dd/mm/yy) and a time (HH:MM). The range was not saved.';
	} else {
		$message = 'End must be after start. The range was not saved.';
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html( $message );
	echo '</p></div>';
}
add_action( 'admin_notices', 'ekdiloseis_admin_notices' );
