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
 * Whether a native event is stored as all day (no start time).
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function ekdiloseis_event_is_all_day( $post_id ) {
	return '1' === (string) get_post_meta( (int) $post_id, 'event_all_day', true );
}

/**
 * Whether a native event has a start time but no end time.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function ekdiloseis_event_has_no_end_time( $post_id ) {
	return '1' === (string) get_post_meta( (int) $post_id, 'event_no_end_time', true );
}

/**
 * Build the stored range from the four Schedule fields. Times are optional.
 *
 * - No start time: all day. Start is the start date at 00:00:00, end is the end date
 *   (or the start date when empty) at 23:59:59.
 * - Start time without end time: end is the end date at 23:59:59 when it is a later day,
 *   otherwise one hour after the start (never past the end of that day).
 * - Both times: the usual range. An empty end date means the start date.
 *
 * @param string $start_date Start date field (dd/mm/yyyy).
 * @param string $start_time Start time field (HH:MM, optional).
 * @param string $end_date   End date field (dd/mm/yyyy, optional).
 * @param string $end_time   End time field (HH:MM, optional).
 * @return array{error: string, start: string, end: string, all_day: bool, no_end_time: bool}
 *         error is '' on success, 'invalid' for unreadable input, '1' when end is before start.
 */
function ekdiloseis_build_range( $start_date, $start_time, $end_date, $end_time ) {
	$result = array(
		'error'       => '',
		'start'       => '',
		'end'         => '',
		'all_day'     => false,
		'no_end_time' => false,
	);

	$start_date = is_string( $start_date ) ? trim( $start_date ) : '';
	$start_time = is_string( $start_time ) ? trim( $start_time ) : '';
	$end_date   = is_string( $end_date ) ? trim( $end_date ) : '';
	$end_time   = is_string( $end_time ) ? trim( $end_time ) : '';

	$sd = ekdiloseis_parse_admin_date( $start_date );
	if ( '' === $sd ) {
		$result['error'] = 'invalid';
		return $result;
	}
	$ed = $sd;
	if ( '' !== $end_date ) {
		$ed = ekdiloseis_parse_admin_date( $end_date );
		if ( '' === $ed ) {
			$result['error'] = 'invalid';
			return $result;
		}
	}
	$st = '';
	if ( '' !== $start_time ) {
		$st = ekdiloseis_parse_admin_time( $start_time );
		if ( '' === $st ) {
			$result['error'] = 'invalid';
			return $result;
		}
	}
	$et = '';
	if ( '' !== $end_time ) {
		$et = ekdiloseis_parse_admin_time( $end_time );
		if ( '' === $et ) {
			$result['error'] = 'invalid';
			return $result;
		}
	}

	if ( '' === $st && '' !== $et ) {
		// An end time without a start time is ambiguous; ask for a start time instead of dropping it.
		$result['error'] = 'invalid';
		return $result;
	}
	if ( $ed < $sd ) {
		$result['error'] = '1';
		return $result;
	}

	if ( '' === $st ) {
		// All day.
		$result['start']   = ekdiloseis_normalize_datetime( $sd . ' 00:00:00' );
		$result['end']     = ekdiloseis_normalize_datetime( $ed . ' 23:59:59' );
		$result['all_day'] = true;
	} elseif ( '' === $et ) {
		$result['start']       = ekdiloseis_normalize_datetime( $sd . ' ' . $st );
		$result['no_end_time'] = true;
		if ( $ed > $sd ) {
			$result['end'] = ekdiloseis_normalize_datetime( $ed . ' 23:59:59' );
		} else {
			$tz       = new DateTimeZone( 'Europe/Athens' );
			$start_dt = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $sd . ' ' . $st, $tz );
			$day_end  = $sd . ' 23:59:59';
			$hour     = $start_dt instanceof DateTimeImmutable ? $start_dt->modify( '+1 hour' )->format( 'Y-m-d H:i:s' ) : $day_end;
			$result['end'] = ekdiloseis_normalize_datetime( $hour < $day_end ? $hour : $day_end );
		}
	} else {
		$result['start'] = ekdiloseis_normalize_datetime( $sd . ' ' . $st );
		$result['end']   = ekdiloseis_normalize_datetime( $ed . ' ' . $et );
	}

	if ( '' === $result['start'] || '' === $result['end'] ) {
		$result['error'] = 'invalid';
		return $result;
	}
	if ( $result['end'] <= $result['start'] ) {
		// 23:59:59 start with no end time would land here; so would a reversed range.
		$result['error'] = '1';
	}
	return $result;
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
 * Event location: plain text, single line, at most 200 characters. Empty means no location.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function ekdiloseis_sanitize_location( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( sanitize_text_field( $value ) );
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $value, 0, 200 );
	}
	return substr( $value, 0, 200 );
}

/**
 * Register event_location so it is sanitized however it is written.
 */
function ekdiloseis_register_location_meta() {
	register_post_meta(
		'ekdilosi',
		'event_location',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'ekdiloseis_sanitize_location',
			'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
				unset( $allowed, $meta_key );
				return current_user_can( 'edit_post', (int) $post_id );
			},
		)
	);
}
add_action( 'init', 'ekdiloseis_register_location_meta' );

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
	$location_raw = get_post_meta( $post->ID, 'event_location', true );
	$location     = ekdiloseis_sanitize_location( is_string( $location_raw ) ? $location_raw : '' );
	$all_day      = ekdiloseis_event_is_all_day( $post->ID );
	$no_end_time  = $all_day || ekdiloseis_event_has_no_end_time( $post->ID );
	$start_time   = $all_day ? '' : ekdiloseis_admin_time_value( $start );
	$end_time     = $no_end_time ? '' : ekdiloseis_admin_time_value( $end );
	wp_nonce_field( 'ekdiloseis_save_event', 'ekdiloseis_nonce' );
	?>
	<p>
		<label for="ekdiloseis_event_start_date"><strong>Start</strong></label><br>
		<input type="text" id="ekdiloseis_event_start_date" name="ekdiloseis_event_start_date" value="<?php echo esc_attr( ekdiloseis_admin_date_value( $start ) ); ?>" placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off" maxlength="10" size="10">
		<input type="text" id="ekdiloseis_event_start_time" name="ekdiloseis_event_start_time" value="<?php echo esc_attr( $start_time ); ?>" placeholder="HH:MM" inputmode="numeric" autocomplete="off" maxlength="8" size="8" aria-label="Start time (optional)">
	</p>
	<p>
		<label for="ekdiloseis_event_end_date"><strong>End</strong></label><br>
		<input type="text" id="ekdiloseis_event_end_date" name="ekdiloseis_event_end_date" value="<?php echo esc_attr( ekdiloseis_admin_date_value( $end ) ); ?>" placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off" maxlength="10" size="10">
		<input type="text" id="ekdiloseis_event_end_time" name="ekdiloseis_event_end_time" value="<?php echo esc_attr( $end_time ); ?>" placeholder="HH:MM" inputmode="numeric" autocomplete="off" maxlength="8" size="8" aria-label="End time (optional)">
		<br>
		<span class="description"><?php echo esc_html( 'Date as dd/mm/yyyy or dd/mm/yy. Time (HH:MM) is optional: leave the start time empty for an all-day event, or the end time empty to show only the start time. An empty end date means the same day as the start.' ); ?></span>
	</p>
	<p>
		<label for="ekdiloseis_event_location"><strong><?php echo esc_html( 'Location (optional)' ); ?></strong></label><br>
		<input type="text" id="ekdiloseis_event_location" name="ekdiloseis_event_location" value="<?php echo esc_attr( $location ); ?>" placeholder="<?php echo esc_attr( 'e.g. Athens' ); ?>" class="regular-text" maxlength="200">
		<br>
		<span class="description"><?php echo esc_html( 'Plain text. Shown on the event card of the Default calendar when not empty.' ); ?></span>
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
 * Save start and end. Times are optional (see ekdiloseis_build_range()). An unreadable
 * range, or an end before the start, is left untouched.
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

	$location = ekdiloseis_sanitize_location( ekdiloseis_posted_text( 'ekdiloseis_event_location' ) );
	if ( '' === $location ) {
		delete_post_meta( $post_id, 'event_location' );
	} else {
		update_post_meta( $post_id, 'event_location', $location );
	}

	$range = ekdiloseis_build_range(
		ekdiloseis_posted_text( 'ekdiloseis_event_start_date' ),
		ekdiloseis_posted_text( 'ekdiloseis_event_start_time' ),
		ekdiloseis_posted_text( 'ekdiloseis_event_end_date' ),
		ekdiloseis_posted_text( 'ekdiloseis_event_end_time' )
	);
	if ( '' !== $range['error'] ) {
		ekdiloseis_flag_range_error( $range['error'] );
		return;
	}

	update_post_meta( $post_id, 'event_start', $range['start'] );
	update_post_meta( $post_id, 'event_end', $range['end'] );
	if ( $range['all_day'] ) {
		update_post_meta( $post_id, 'event_all_day', '1' );
	} else {
		delete_post_meta( $post_id, 'event_all_day' );
	}
	if ( $range['no_end_time'] ) {
		update_post_meta( $post_id, 'event_no_end_time', '1' );
	} else {
		delete_post_meta( $post_id, 'event_no_end_time' );
	}
}
add_action( 'save_post_ekdilosi', 'ekdiloseis_save_event_meta' );

/**
 * Text for a rejected range.
 *
 * @param string $code '1' or 'invalid'.
 * @return string
 */
function ekdiloseis_range_error_message( $code ) {
	if ( 'invalid' === $code ) {
		return 'Enter a valid start date as dd/mm/yyyy (or dd/mm/yy). Times (HH:MM) are optional, but an end time needs a start time. The range was not saved.';
	}
	return 'End must be after start (an all-day event may end on its start date). The range was not saved.';
}

/**
 * Read and clear the stored range error for the current user.
 *
 * @return string '1', 'invalid' or ''.
 */
function ekdiloseis_take_range_error() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return '';
	}
	$transient = get_transient( 'ekdiloseis_range_error_' . $user_id );
	if ( false === $transient ) {
		return '';
	}
	delete_transient( 'ekdiloseis_range_error_' . $user_id );
	return is_string( $transient ) && in_array( $transient, array( '1', 'invalid' ), true ) ? $transient : '';
}

/**
 * Admin error when a bad range was submitted (classic editor and list screens).
 * The block editor hides classic notices, so there the error is shown by ekdiloseis_block_editor_range_notice().
 */
function ekdiloseis_admin_notices() {
	if ( ! is_admin() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'ekdilosi' !== $screen->post_type ) {
		return;
	}
	if ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
		// Leave the transient for the block editor script (this page may be the meta box save redirect).
		return;
	}

	$query_raw = isset( $_GET['ekdiloseis_range_error'] ) ? sanitize_text_field( wp_unslash( $_GET['ekdiloseis_range_error'] ) ) : '';
	$code      = ekdiloseis_take_range_error();
	if ( '' === $code && in_array( $query_raw, array( '1', 'invalid' ), true ) ) {
		$code = $query_raw;
	}
	if ( '' === $code ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html( ekdiloseis_range_error_message( $code ) );
	echo '</p></div>';
}
add_action( 'admin_notices', 'ekdiloseis_admin_notices' );

/**
 * AJAX: return and clear the current user's range error (block editor only reads it).
 */
function ekdiloseis_ajax_range_error() {
	check_ajax_referer( 'ekdiloseis_range_error', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( null, 403 );
	}
	$code = ekdiloseis_take_range_error();
	wp_send_json_success(
		array(
			'code'    => $code,
			'message' => '' !== $code ? ekdiloseis_range_error_message( $code ) : '',
		)
	);
}
add_action( 'wp_ajax_ekdiloseis_range_error', 'ekdiloseis_ajax_range_error' );

/**
 * Block editor: after the Schedule meta box is saved (and once on load), show a rejected range as an editor notice.
 */
function ekdiloseis_block_editor_range_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'ekdilosi' !== $screen->post_type ) {
		return;
	}
	$config = wp_json_encode(
		array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'ekdiloseis_range_error' ),
		)
	);
	$script = <<<'JS'
(function (config) {
	if (!window.wp || !wp.data || !config) {
		return;
	}
	function check() {
		var body = new URLSearchParams();
		body.append("action", "ekdiloseis_range_error");
		body.append("nonce", config.nonce);
		window.fetch(config.url, { method: "POST", credentials: "same-origin", body: body })
			.then(function (response) { return response.json(); })
			.then(function (json) {
				if (json && json.success && json.data && json.data.message) {
					wp.data.dispatch("core/notices").createErrorNotice(json.data.message, { id: "ekdiloseis-range-error", isDismissible: true });
				} else {
					wp.data.dispatch("core/notices").removeNotice("ekdiloseis-range-error");
				}
			})
			.catch(function () {});
	}
	var wasSaving = false;
	wp.data.subscribe(function () {
		var store = wp.data.select("core/edit-post");
		if (!store || typeof store.isSavingMetaBoxes !== "function") {
			return;
		}
		var saving = store.isSavingMetaBoxes();
		if (wasSaving && !saving) {
			check();
		}
		wasSaving = saving;
	});
	if (window.wp.domReady) {
		wp.domReady(check);
	} else {
		check();
	}
})
JS;
	wp_add_inline_script( 'wp-edit-post', $script . '(' . $config . ');' );
}
add_action( 'enqueue_block_editor_assets', 'ekdiloseis_block_editor_range_notice' );
