<?php
/**
 * Front-end month calendar shortcode.
 *
 * @package Ekdiloseis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register assets. Printing happens only after enqueue.
 */
function ekdiloseis_register_assets() {
	wp_register_style(
		'ekdiloseis',
		EKDILOSEIS_URL . 'assets/ekdiloseis.css',
		array(),
		EKDILOSEIS_VERSION
	);
	wp_register_script(
		'ekdiloseis-list',
		EKDILOSEIS_URL . 'assets/ekdiloseis-list.js',
		array(),
		EKDILOSEIS_VERSION,
		true
	);
	wp_register_script(
		'ekdiloseis',
		EKDILOSEIS_URL . 'assets/ekdiloseis.js',
		array( 'ekdiloseis-list' ),
		EKDILOSEIS_VERSION,
		true
	);
}

/**
 * Shared day-list script. Font Awesome CSS only when List media is Icon.
 * Callers enqueue this only with the shortcode.
 */
function ekdiloseis_enqueue_list_media() {
	ekdiloseis_register_assets();
	wp_enqueue_script( 'ekdiloseis-list' );
	if ( 'icon' !== ekdiloseis_get_list_media() ) {
		return;
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
add_action( 'wp_enqueue_scripts', 'ekdiloseis_register_assets' );

/**
 * Enqueue calendar assets. Safe to call more than once.
 */
function ekdiloseis_enqueue_assets() {
	ekdiloseis_register_assets();
	wp_enqueue_style( 'ekdiloseis' );
	ekdiloseis_attach_custom_css();
	wp_enqueue_script( 'ekdiloseis' );
	ekdiloseis_enqueue_google_font( 'custom' );
	ekdiloseis_enqueue_list_media();
}

/**
 * Print saved custom CSS in a style tag immediately after the plugin stylesheet.
 * Only when that stylesheet is enqueued for [myevents]. Never in wp-admin.
 */
function ekdiloseis_attach_custom_css() {
	static $done = false;
	if ( $done || is_admin() ) {
		return;
	}
	$css = ekdiloseis_get_custom_css();
	if ( '' === $css ) {
		$done = true;
		return;
	}
	if ( wp_add_inline_style( 'ekdiloseis', $css ) ) {
		$done = true;
	}
}

/**
 * Google Font for the selected engine only, and only when this shortcode enqueues that engine.
 *
 * @param string $engine Engine key.
 */
function ekdiloseis_enqueue_google_font( $engine ) {
	if ( ! is_string( $engine ) || ekdiloseis_get_engine() !== $engine ) {
		return;
	}
	$row = ekdiloseis_get_engine_style( $engine );
	if ( null === $row || empty( $row['google_font'] ) || ! is_string( $row['google_font'] ) ) {
		return;
	}
	$url = ekdiloseis_google_font_url( $row['google_font'] );
	if ( '' === $url ) {
		return;
	}
	if ( ! wp_style_is( 'ekdiloseis-google-font', 'registered' ) ) {
		wp_register_style( 'ekdiloseis-google-font', $url, array(), null );
	}
	wp_enqueue_style( 'ekdiloseis-google-font' );
}

/**
 * Enqueue on singular content that contains the shortcode, before wp_head.
 */
function ekdiloseis_enqueue_for_singular() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_queried_object();
	if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'myevents' ) ) {
		$engine = ekdiloseis_get_engine();
		if ( 'fullcalendar' === $engine ) {
			ekdiloseis_enqueue_fullcalendar_assets();
		} elseif ( 'toast' === $engine ) {
			ekdiloseis_enqueue_toast_assets();
		} elseif ( 'eventcalendar' === $engine ) {
			ekdiloseis_enqueue_eventcalendar_assets();
		} elseif ( 'schedulex' === $engine ) {
			ekdiloseis_enqueue_schedulex_assets();
		} else {
			ekdiloseis_enqueue_assets();
		}
	}
}
add_action( 'wp', 'ekdiloseis_enqueue_for_singular' );

/**
 * If the shortcode is rendered after wp_head, still print the stylesheet.
 */
function ekdiloseis_print_late_style() {
	if ( wp_style_is( 'ekdiloseis', 'enqueued' ) && ! wp_style_is( 'ekdiloseis', 'done' ) ) {
		ekdiloseis_attach_custom_css();
		wp_print_styles( 'ekdiloseis' );
	}
	if ( wp_style_is( 'ekdiloseis-google-font', 'enqueued' ) && ! wp_style_is( 'ekdiloseis-google-font', 'done' ) ) {
		wp_print_styles( 'ekdiloseis-google-font' );
	}
	if ( wp_style_is( 'ekdiloseis-font-awesome', 'enqueued' ) && ! wp_style_is( 'ekdiloseis-font-awesome', 'done' ) ) {
		wp_print_styles( 'ekdiloseis-font-awesome' );
	}
}
add_action( 'wp_footer', 'ekdiloseis_print_late_style', 5 );

/**
 * Public calendar chrome for the saved language. One source for every engine.
 *
 * @return array<string, mixed>
 */
function ekdiloseis_calendar_copy() {
	if ( 'el' === ekdiloseis_get_locale() ) {
		return array(
			'locale'          => 'el',
			'empty'           => 'Δεν υπάρχουν εκδηλώσεις αυτή την ημέρα',
			'hint'            => 'Επιλέξτε μια ημέρα για να δείτε τις εκδηλώσεις.',
			'previous'        => 'Προηγούμενο',
			'next'            => 'Επόμενο',
			'today'           => 'Σήμερα',
			'previousMonth'   => 'Προηγούμενος μήνας',
			'nextMonth'       => 'Επόμενος μήνας',
			'withEvents'      => 'με εκδηλώσεις',
			'withoutEvents'   => 'χωρίς εκδηλώσεις',
			'months'          => array(
				1  => 'Ιανουάριος',
				2  => 'Φεβρουάριος',
				3  => 'Μάρτιος',
				4  => 'Απρίλιος',
				5  => 'Μάιος',
				6  => 'Ιούνιος',
				7  => 'Ιούλιος',
				8  => 'Αύγουστος',
				9  => 'Σεπτέμβριος',
				10 => 'Οκτώβριος',
				11 => 'Νοέμβριος',
				12 => 'Δεκέμβριος',
			),
			'monthsGenitive'  => array(
				1  => 'Ιανουαρίου',
				2  => 'Φεβρουαρίου',
				3  => 'Μαρτίου',
				4  => 'Απριλίου',
				5  => 'Μαΐου',
				6  => 'Ιουνίου',
				7  => 'Ιουλίου',
				8  => 'Αυγούστου',
				9  => 'Σεπτεμβρίου',
				10 => 'Οκτωβρίου',
				11 => 'Νοεμβρίου',
				12 => 'Δεκεμβρίου',
			),
			'weekdays'        => array( 'Δευ', 'Τρί', 'Τετ', 'Πέμ', 'Παρ', 'Σάβ', 'Κυρ' ),
			'weekdaysFull'    => array( 'Δευτέρα', 'Τρίτη', 'Τετάρτη', 'Πέμπτη', 'Παρασκευή', 'Σάββατο', 'Κυριακή' ),
			'countOne'        => '1 προγραμματισμένη εκδήλωση',
			'countMany'       => '%d προγραμματισμένες εκδηλώσεις',
			'legend'          => 'Κατηγορίες εκδηλώσεων',
			'openEvent'       => 'Άνοιγμα εκδήλωσης',
			'weekdaysSunday'  => array( 'Κυρ', 'Δευ', 'Τρί', 'Τετ', 'Πέμ', 'Παρ', 'Σάβ' ),
			'sxTranslations'  => array(
				'Today'                               => 'Σήμερα',
				'Previous period'                     => 'Προηγούμενη περίοδος',
				'Next period'                         => 'Επόμενη περίοδος',
				'+ {{n}} events'                      => '+ {{n}} εκδηλώσεις',
				'+ 1 event'                           => '+ 1 εκδήλωση',
				'No events'                           => 'Δεν υπάρχουν εκδηλώσεις',
				'Link to {{n}} more events on {{date}}' => 'Σύνδεσμος σε {{n}} ακόμη εκδηλώσεις στις {{date}}',
				'Link to 1 more event on {{date}}'    => 'Σύνδεσμος σε 1 ακόμη εκδήλωση στις {{date}}',
				'Month'                               => 'Μήνας',
				'to'                                  => 'έως',
			),
		);
	}

	return array(
		'locale'         => 'en',
		'empty'          => 'No events on this day',
		'hint'           => 'Select a day to view events.',
		'previous'       => 'Previous',
		'next'           => 'Next',
		'today'          => 'Today',
		'previousMonth'  => 'Previous month',
		'nextMonth'      => 'Next month',
		'withEvents'     => 'with events',
		'withoutEvents'  => 'without events',
		'months'         => array(
			1  => 'January',
			2  => 'February',
			3  => 'March',
			4  => 'April',
			5  => 'May',
			6  => 'June',
			7  => 'July',
			8  => 'August',
			9  => 'September',
			10 => 'October',
			11 => 'November',
			12 => 'December',
		),
		'monthsGenitive' => array(
			1  => 'January',
			2  => 'February',
			3  => 'March',
			4  => 'April',
			5  => 'May',
			6  => 'June',
			7  => 'July',
			8  => 'August',
			9  => 'September',
			10 => 'October',
			11 => 'November',
			12 => 'December',
		),
		'weekdays'       => array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' ),
		'weekdaysFull'   => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ),
		'countOne'       => '1 scheduled event',
		'countMany'      => '%d scheduled events',
		'legend'         => 'Event categories',
		'openEvent'      => 'Open event',
		'weekdaysSunday' => array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ),
		'sxTranslations' => null,
	);
}

/**
 * Month name in the public calendar language.
 *
 * @param int $month Month number.
 * @return string
 */
function ekdiloseis_month_name( $month ) {
	$names = ekdiloseis_calendar_copy()['months'];
	return $names[ (int) $month ] ?? '';
}

/**
 * Month name for accessible day labels. Greek uses the genitive.
 *
 * @param int $month Month number.
 * @return string
 */
function ekdiloseis_month_name_genitive( $month ) {
	$names = ekdiloseis_calendar_copy()['monthsGenitive'];
	return $names[ (int) $month ] ?? '';
}

/**
 * Today's calendar date in the WordPress timezone.
 *
 * The site timezone is Europe/Athens. wp_date() follows that setting, including DST.
 *
 * @return string Y-m-d.
 */
function ekdiloseis_today_date() {
	$today = wp_date( 'Y-m-d' );
	if ( is_string( $today ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $today ) ) {
		return $today;
	}
	return ( new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Athens' ) ) )->format( 'Y-m-d' );
}

/**
 * JSON payload shared by every engine. Extra keys are merged on top.
 *
 * @param array<int, array<string, mixed>> $events Events.
 * @param array<string, mixed>             $extra  Engine-specific keys.
 * @return string
 */
function ekdiloseis_calendar_json( $events, $extra = array() ) {
	$copy    = ekdiloseis_calendar_copy();
	$payload = array_merge(
		array(
			'locale'        => $copy['locale'],
			'emptyMessage'  => $copy['empty'],
			'hint'          => $copy['hint'],
			'previous'      => $copy['previous'],
			'next'          => $copy['next'],
			'today'         => $copy['today'],
			'todayDate'     => ekdiloseis_today_date(),
			'previousMonth' => $copy['previousMonth'],
			'nextMonth'     => $copy['nextMonth'],
			'listMedia'     => ekdiloseis_get_list_media(),
			'events'        => $events,
		),
		$extra
	);
	$json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	if ( ! is_string( $json ) ) {
		return '{"locale":"en","emptyMessage":"No events on this day","listMedia":"image","events":[]}';
	}
	return $json;
}

/**
 * First instant of the requested month, or the current month in Athens.
 *
 * @return DateTimeImmutable
 */
function ekdiloseis_requested_month() {
	$tz  = new DateTimeZone( 'Europe/Athens' );
	$now = new DateTimeImmutable( 'now', $tz );
	$raw = '';
	if ( isset( $_GET['ekd_month'] ) && is_string( $_GET['ekd_month'] ) ) {
		$raw = sanitize_text_field( wp_unslash( $_GET['ekd_month'] ) );
	}
	$built = ekdiloseis_parse_month( $raw );
	if ( null !== $built ) {
		return $built;
	}
	return $now->modify( 'first day of this month' )->setTime( 0, 0, 0 );
}

/**
 * Strict YYYY-MM (1970-01 to 2100-12) to the first day of that month in Athens, or null.
 *
 * @param mixed $raw Raw value.
 * @return DateTimeImmutable|null
 */
function ekdiloseis_parse_month( $raw ) {
	if ( ! is_string( $raw ) || ! preg_match( '/^(\d{4})-(\d{2})$/D', $raw, $matches ) ) {
		return null;
	}
	$year  = (int) $matches[1];
	$month = (int) $matches[2];
	if ( $year < 1970 || $year > 2100 || $month < 1 || $month > 12 ) {
		return null;
	}
	$built = DateTimeImmutable::createFromFormat( '!Y-m-d', sprintf( '%04d-%02d-01', $year, $month ), new DateTimeZone( 'Europe/Athens' ) );
	return $built instanceof DateTimeImmutable ? $built : null;
}

/**
 * Whether a stored range overlaps a calendar day. Bounds are wall-clock midnights.
 *
 * @param string $start Stored start.
 * @param string $end   Stored end.
 * @param string $day   Y-m-d.
 * @return bool
 */
function ekdiloseis_overlaps_day( $start, $end, $day ) {
	$tz      = new DateTimeZone( 'Europe/Athens' );
	$day_dt  = DateTimeImmutable::createFromFormat( '!Y-m-d', $day, $tz );
	if ( ! $day_dt instanceof DateTimeImmutable ) {
		return false;
	}
	$day_start = $day_dt->format( 'Y-m-d' ) . ' 00:00:00';
	$next      = $day_dt->modify( '+1 day' )->format( 'Y-m-d' ) . ' 00:00:00';
	return $start < $next && $end > $day_start;
}

/**
 * Published events whose range overlaps the given month.
 *
 * @param DateTimeImmutable $month_start First day of the month.
 * @return array<int, array<string, mixed>>
 */
function ekdiloseis_events_for_month( DateTimeImmutable $month_start ) {
	global $wpdb;

	$range_start = $month_start->format( 'Y-m-d H:i:s' );
	$range_end   = $month_start->modify( 'first day of next month' )->format( 'Y-m-d H:i:s' );

	$sql = $wpdb->prepare(
		"SELECT p.ID, p.post_title, s.meta_value AS event_start, e.meta_value AS event_end
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = %s
		INNER JOIN {$wpdb->postmeta} e ON e.post_id = p.ID AND e.meta_key = %s
		WHERE p.post_type = %s
			AND p.post_status = %s
			AND s.meta_value < %s
			AND e.meta_value > %s
		ORDER BY s.meta_value ASC, p.ID ASC",
		'event_start',
		'event_end',
		'ekdilosi',
		'publish',
		$range_end,
		$range_start
	);

	$rows   = $wpdb->get_results( $sql );
	$events = array();
	if ( ! is_array( $rows ) ) {
		return $events;
	}

	foreach ( $rows as $row ) {
		$event = ekdiloseis_event_from_row( $row );
		if ( null !== $event ) {
			$events[] = $event;
		}
	}

	return $events;
}

/**
 * Clock label HH:MM–HH:MM.
 *
 * @param string $start Stored start.
 * @param string $end   Stored end.
 * @return string
 */
function ekdiloseis_time_label( $start, $end ) {
	return substr( $start, 11, 5 ) . '–' . substr( $end, 11, 5 );
}

/**
 * FullCalendar v6 from jsDelivr. Printed only for this engine.
 */
function ekdiloseis_enqueue_fullcalendar_assets() {
	ekdiloseis_register_assets();
	wp_enqueue_style( 'ekdiloseis' );
	ekdiloseis_attach_custom_css();
	wp_enqueue_style(
		'ekdiloseis-fullcalendar',
		EKDILOSEIS_URL . 'assets/ekdiloseis-fullcalendar.css',
		array( 'ekdiloseis' ),
		EKDILOSEIS_VERSION
	);

	if ( ! wp_script_is( 'fullcalendar', 'registered' ) ) {
		wp_register_script(
			'fullcalendar',
			'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js',
			array(),
			'6.1.21',
			true
		);
	}
	$fc_deps = array( 'fullcalendar', 'ekdiloseis-list' );
	// The fullcalendar meta package does not ship locales. Greek lives on @fullcalendar/core at the same version.
	if ( 'el' === ekdiloseis_get_locale() ) {
		if ( ! wp_script_is( 'fullcalendar-el', 'registered' ) ) {
			wp_register_script(
				'fullcalendar-el',
				'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales/el.global.min.js',
				array( 'fullcalendar' ),
				'6.1.21',
				true
			);
		}
		$fc_deps[] = 'fullcalendar-el';
	}
	if ( ! wp_script_is( 'ekdiloseis-fullcalendar', 'registered' ) ) {
		wp_register_script(
			'ekdiloseis-fullcalendar',
			EKDILOSEIS_URL . 'assets/ekdiloseis-fullcalendar.js',
			$fc_deps,
			EKDILOSEIS_VERSION,
			true
		);
	}

	wp_enqueue_script( 'fullcalendar' );
	if ( 'el' === ekdiloseis_get_locale() ) {
		wp_enqueue_script( 'fullcalendar-el' );
	}
	wp_enqueue_script( 'ekdiloseis-fullcalendar' );
	ekdiloseis_enqueue_google_font( 'fullcalendar' );
	ekdiloseis_enqueue_list_media();
}

/**
 * If the shortcode is rendered after wp_head, still print the FullCalendar stylesheet.
 */
function ekdiloseis_print_late_fullcalendar_style() {
	if ( wp_style_is( 'ekdiloseis-fullcalendar', 'enqueued' ) && ! wp_style_is( 'ekdiloseis-fullcalendar', 'done' ) ) {
		wp_print_styles( 'ekdiloseis-fullcalendar' );
	}
}
add_action( 'wp_footer', 'ekdiloseis_print_late_fullcalendar_style', 5 );

/**
 * Map a joined event row to the public payload, or null when the range is unusable.
 *
 * @param object $row Query row.
 * @return array<string, mixed>|null
 */
function ekdiloseis_event_from_row( $row ) {
	$start = ekdiloseis_normalize_datetime( (string) $row->event_start );
	$end   = ekdiloseis_normalize_datetime( (string) $row->event_end );
	if ( '' === $start || '' === $end || $end <= $start ) {
		return null;
	}
	$post_id = (int) $row->ID;
	$thumb   = get_the_post_thumbnail_url( $post_id, 'medium' );
	if ( ! is_string( $thumb ) || '' === $thumb ) {
		$thumb = get_the_post_thumbnail_url( $post_id, 'full' );
	}
	$thumb  = is_string( $thumb ) && '' !== $thumb ? esc_url_raw( $thumb ) : '';
	$colors = ekdiloseis_event_colors( $post_id );

	$permalink = get_permalink( $post_id );
	$permalink = is_string( $permalink ) ? esc_url_raw( $permalink ) : '';

	$event = array(
		'id'        => $post_id,
		'title'     => html_entity_decode( (string) $row->post_title, ENT_QUOTES, 'UTF-8' ),
		'start'     => $start,
		'end'       => $end,
		'time'      => substr( $start, 11, 5 ) . '–' . substr( $end, 11, 5 ),
		'thumb'     => '' !== $thumb ? $thumb : null,
		'url'       => $permalink,
		'color'     => $colors['color'],
		'textColor' => $colors['textColor'],
	);
	// Icon is only part of the payload in icon mode, so image mode keeps the photo and ignores icons.
	if ( 'icon' === ekdiloseis_get_list_media() ) {
		$event['icon'] = ekdiloseis_event_icon( $post_id );
	}
	return $event;
}

/**
 * Every published event. FullCalendar changes month in the browser, so the payload is not limited to one month.
 *
 * @return array<int, array<string, mixed>>
 */
function ekdiloseis_all_events() {
	global $wpdb;

	$sql = $wpdb->prepare(
		"SELECT p.ID, p.post_title, s.meta_value AS event_start, e.meta_value AS event_end
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = %s
		INNER JOIN {$wpdb->postmeta} e ON e.post_id = p.ID AND e.meta_key = %s
		WHERE p.post_type = %s
			AND p.post_status = %s
		ORDER BY s.meta_value ASC, p.ID ASC",
		'event_start',
		'event_end',
		'ekdilosi',
		'publish'
	);

	$rows   = $wpdb->get_results( $sql );
	$events = array();
	if ( ! is_array( $rows ) ) {
		return $events;
	}

	foreach ( $rows as $row ) {
		$event = ekdiloseis_event_from_row( $row );
		if ( null !== $event ) {
			$events[] = $event;
		}
	}

	return $events;
}

/**
 * FullCalendar month view. Day clicks list the same overlapping events as the custom calendar.
 *
 * @return string
 */
function ekdiloseis_render_fullcalendar() {
	ekdiloseis_enqueue_fullcalendar_assets();

	$month  = ekdiloseis_requested_month();
	$events = ekdiloseis_all_events();

	static $instance = 0;
	++$instance;
	$list_id = 'ekdiloseis-fc-list-' . $instance;

	$copy = ekdiloseis_calendar_copy();
	$json = ekdiloseis_calendar_json( $events );

	ob_start();
	?>
	<div class="ekdiloseis-fc <?php echo esc_attr( ekdiloseis_layout_class() ); ?>" data-initial-date="<?php echo esc_attr( $month->format( 'Y-m-d' ) ); ?>"<?php echo ekdiloseis_root_style_attr( 'fullcalendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
		<div class="ekdiloseis__main">
			<div class="ekdiloseis-fc__calendar" aria-label="<?php echo esc_attr( 'Events calendar' ); ?>"></div>
		</div>
		<div class="ekdiloseis__list" id="<?php echo esc_attr( $list_id ); ?>" aria-live="polite">
			<p class="ekdiloseis__hint"><?php echo esc_html( $copy['hint'] ); ?></p>
		</div>
		<script type="application/json" class="ekdiloseis-fc__data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* encoded. ?></script>
	</div>
	<?php
	return (string) ob_get_clean();
}


/**
 * Toast UI Calendar 2.x from jsDelivr, plus its stylesheet. Printed only for this engine.
 */
function ekdiloseis_enqueue_toast_assets() {
	ekdiloseis_register_assets();
	wp_enqueue_style( 'ekdiloseis' );
	ekdiloseis_attach_custom_css();

	if ( ! wp_style_is( 'toastui-calendar', 'registered' ) ) {
		wp_register_style(
			'toastui-calendar',
			'https://cdn.jsdelivr.net/npm/@toast-ui/calendar@2.1.3/dist/toastui-calendar.min.css',
			array(),
			'2.1.3'
		);
	}
	if ( ! wp_style_is( 'ekdiloseis-toast', 'registered' ) ) {
		wp_register_style(
			'ekdiloseis-toast',
			EKDILOSEIS_URL . 'assets/ekdiloseis-toast.css',
			array( 'ekdiloseis', 'toastui-calendar' ),
			EKDILOSEIS_VERSION
		);
	}

	if ( ! wp_script_is( 'toastui-calendar', 'registered' ) ) {
		wp_register_script(
			'toastui-calendar',
			'https://cdn.jsdelivr.net/npm/@toast-ui/calendar@2.1.3/dist/toastui-calendar.min.js',
			array(),
			'2.1.3',
			true
		);
	}
	if ( ! wp_script_is( 'ekdiloseis-toast', 'registered' ) ) {
		wp_register_script(
			'ekdiloseis-toast',
			EKDILOSEIS_URL . 'assets/ekdiloseis-toast.js',
			array( 'toastui-calendar', 'ekdiloseis-list' ),
			EKDILOSEIS_VERSION,
			true
		);
	}

	wp_enqueue_style( 'toastui-calendar' );
	wp_enqueue_style( 'ekdiloseis-toast' );
	wp_enqueue_script( 'toastui-calendar' );
	wp_enqueue_script( 'ekdiloseis-toast' );
	ekdiloseis_enqueue_google_font( 'toast' );
	ekdiloseis_enqueue_list_media();
}

/**
 * If the shortcode is rendered after wp_head, still print the Toast UI stylesheets.
 */
function ekdiloseis_print_late_toast_style() {
	if ( wp_style_is( 'toastui-calendar', 'enqueued' ) && ! wp_style_is( 'toastui-calendar', 'done' ) ) {
		wp_print_styles( 'toastui-calendar' );
	}
	if ( wp_style_is( 'ekdiloseis-toast', 'enqueued' ) && ! wp_style_is( 'ekdiloseis-toast', 'done' ) ) {
		wp_print_styles( 'ekdiloseis-toast' );
	}
}
add_action( 'wp_footer', 'ekdiloseis_print_late_toast_style', 5 );

/**
 * Toast UI Calendar month view. Day clicks list the same overlapping events as the other engines.
 *
 * @return string
 */
function ekdiloseis_render_toast() {
	ekdiloseis_enqueue_toast_assets();

	$month  = ekdiloseis_requested_month();
	$events = ekdiloseis_all_events();

	static $instance = 0;
	++$instance;
	$list_id = 'ekdiloseis-toast-list-' . $instance;

	$copy        = ekdiloseis_calendar_copy();
	$month_names = array();
	for ( $month_num = 1; $month_num <= 12; $month_num++ ) {
		$month_names[] = $copy['months'][ $month_num ];
	}

	$json = ekdiloseis_calendar_json(
		$events,
		array(
			'monthNames' => $month_names,
			'dayNames'   => $copy['weekdaysSunday'],
		)
	);

	$title = ekdiloseis_month_name( (int) $month->format( 'n' ) ) . ' ' . $month->format( 'Y' );

	ob_start();
	?>
	<div class="ekdiloseis-toast <?php echo esc_attr( ekdiloseis_layout_class() ); ?>" data-initial-date="<?php echo esc_attr( $month->format( 'Y-m-d' ) ); ?>"<?php echo ekdiloseis_root_style_attr( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
		<div class="ekdiloseis__main">
		<nav class="ekdiloseis__nav" aria-label="Change month">
			<button type="button" class="ekdiloseis__navlink" data-nav="prev"><?php echo esc_html( $copy['previous'] ); ?></button>
			<h2 class="ekdiloseis__title"><?php echo esc_html( $title ); ?></h2>
			<button type="button" class="ekdiloseis__navlink" data-nav="next"><?php echo esc_html( $copy['next'] ); ?></button>
		</nav>
		<div class="ekdiloseis-toast__calendar" style="height: 640px;" aria-label="<?php echo esc_attr( 'Events calendar' ); ?>"></div>
		</div>
		<div class="ekdiloseis__list" id="<?php echo esc_attr( $list_id ); ?>" aria-live="polite">
			<p class="ekdiloseis__hint"><?php echo esc_html( $copy['hint'] ); ?></p>
		</div>
		<script type="application/json" class="ekdiloseis-toast__data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* encoded. ?></script>
	</div>
	<?php
	return (string) ob_get_clean();
}


/**
 * vkurko Event Calendar standalone build from jsDelivr. Printed only for this engine.
 */
function ekdiloseis_enqueue_eventcalendar_assets() {
	ekdiloseis_register_assets();
	wp_enqueue_style( 'ekdiloseis' );
	ekdiloseis_attach_custom_css();

	if ( ! wp_style_is( 'event-calendar', 'registered' ) ) {
		wp_register_style(
			'event-calendar',
			'https://cdn.jsdelivr.net/npm/@event-calendar/build@5.15.0/dist/event-calendar.min.css',
			array(),
			'5.15.0'
		);
	}
	if ( ! wp_style_is( 'ekdiloseis-eventcalendar', 'registered' ) ) {
		wp_register_style(
			'ekdiloseis-eventcalendar',
			EKDILOSEIS_URL . 'assets/ekdiloseis-eventcalendar.css',
			array( 'ekdiloseis', 'event-calendar' ),
			EKDILOSEIS_VERSION
		);
	}

	if ( ! wp_script_is( 'event-calendar', 'registered' ) ) {
		wp_register_script(
			'event-calendar',
			'https://cdn.jsdelivr.net/npm/@event-calendar/build@5.15.0/dist/event-calendar.min.js',
			array(),
			'5.15.0',
			true
		);
	}
	if ( ! wp_script_is( 'ekdiloseis-eventcalendar', 'registered' ) ) {
		wp_register_script(
			'ekdiloseis-eventcalendar',
			EKDILOSEIS_URL . 'assets/ekdiloseis-eventcalendar.js',
			array( 'event-calendar', 'ekdiloseis-list' ),
			EKDILOSEIS_VERSION,
			true
		);
	}

	wp_enqueue_style( 'event-calendar' );
	wp_enqueue_style( 'ekdiloseis-eventcalendar' );
	wp_enqueue_script( 'event-calendar' );
	wp_enqueue_script( 'ekdiloseis-eventcalendar' );
	ekdiloseis_enqueue_google_font( 'eventcalendar' );
	ekdiloseis_enqueue_list_media();
}

/**
 * If the shortcode is rendered after wp_head, still print the Event Calendar stylesheets.
 */
function ekdiloseis_print_late_eventcalendar_style() {
	if ( wp_style_is( 'event-calendar', 'enqueued' ) && ! wp_style_is( 'event-calendar', 'done' ) ) {
		wp_print_styles( 'event-calendar' );
	}
	if ( wp_style_is( 'ekdiloseis-eventcalendar', 'enqueued' ) && ! wp_style_is( 'ekdiloseis-eventcalendar', 'done' ) ) {
		wp_print_styles( 'ekdiloseis-eventcalendar' );
	}
}
add_action( 'wp_footer', 'ekdiloseis_print_late_eventcalendar_style', 5 );

/**
 * Event Calendar month view. Day clicks list the same overlapping events as the other engines.
 *
 * @return string
 */
function ekdiloseis_render_eventcalendar() {
	ekdiloseis_enqueue_eventcalendar_assets();

	$month  = ekdiloseis_requested_month();
	$events = ekdiloseis_all_events();

	static $instance = 0;
	++$instance;
	$list_id = 'ekdiloseis-ec-list-' . $instance;

	$copy = ekdiloseis_calendar_copy();
	$json = ekdiloseis_calendar_json( $events );

	ob_start();
	?>
	<div class="ekdiloseis-ec <?php echo esc_attr( ekdiloseis_layout_class() ); ?>" data-initial-date="<?php echo esc_attr( $month->format( 'Y-m-d' ) ); ?>"<?php echo ekdiloseis_root_style_attr( 'eventcalendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
		<div class="ekdiloseis__main">
			<div class="ekdiloseis-ec__calendar" aria-label="<?php echo esc_attr( 'Events calendar' ); ?>"></div>
		</div>
		<div class="ekdiloseis__list" id="<?php echo esc_attr( $list_id ); ?>" aria-live="polite">
			<p class="ekdiloseis__hint"><?php echo esc_html( $copy['hint'] ); ?></p>
		</div>
		<script type="application/json" class="ekdiloseis-ec__data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* encoded. ?></script>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Schedule-X month grid from the documented jsDelivr UMD build, plus the Preact globals it expects.
 */
function ekdiloseis_enqueue_schedulex_assets() {
	ekdiloseis_register_assets();
	wp_enqueue_style( 'ekdiloseis' );
	ekdiloseis_attach_custom_css();

	$scripts = array(
		'preact'            => array(
			'src'  => 'https://cdn.jsdelivr.net/npm/preact@10.26.9/dist/preact.umd.js',
			'ver'  => '10.26.9',
			'deps' => array(),
		),
		'preact-hooks'      => array(
			'src'  => 'https://cdn.jsdelivr.net/npm/preact@10.26.9/hooks/dist/hooks.umd.js',
			'ver'  => '10.26.9',
			'deps' => array( 'preact' ),
		),
		'preact-jsx-runtime' => array(
			'src'  => 'https://cdn.jsdelivr.net/npm/preact@10.26.9/jsx-runtime/dist/jsxRuntime.umd.js',
			'ver'  => '10.26.9',
			'deps' => array( 'preact' ),
		),
		'preact-compat'     => array(
			'src'  => 'https://cdn.jsdelivr.net/npm/preact@10.26.9/compat/dist/compat.umd.js',
			'ver'  => '10.26.9',
			'deps' => array( 'preact', 'preact-hooks' ),
		),
		'preact-signals-core' => array(
			'src'  => 'https://cdn.jsdelivr.net/npm/@preact/signals-core@1.11.0/dist/signals-core.min.js',
			'ver'  => '1.11.0',
			'deps' => array(),
		),
		'preact-signals'    => array(
			'src'  => 'https://cdn.jsdelivr.net/npm/@preact/signals@2.3.0/dist/signals.min.js',
			'ver'  => '2.3.0',
			'deps' => array( 'preact', 'preact-hooks', 'preact-signals-core' ),
		),
		'temporal-polyfill' => array(
			'src'  => 'https://cdn.jsdelivr.net/npm/temporal-polyfill@0.3.2/global.min.js',
			'ver'  => '0.3.2',
			'deps' => array(),
		),
		'schedule-x-calendar' => array(
			'src'  => 'https://cdn.jsdelivr.net/npm/@schedule-x/calendar@4.9.1/dist/core.umd.js',
			'ver'  => '4.9.1',
			'deps' => array( 'preact', 'preact-hooks', 'preact-jsx-runtime', 'preact-compat', 'preact-signals', 'temporal-polyfill' ),
		),
	);

	foreach ( $scripts as $handle => $script ) {
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			wp_register_script( $handle, $script['src'], $script['deps'], $script['ver'], true );
		}
	}

	if ( ! wp_style_is( 'schedule-x-theme', 'registered' ) ) {
		wp_register_style(
			'schedule-x-theme',
			'https://cdn.jsdelivr.net/npm/@schedule-x/theme-default@4.9.1/dist/calendar.css',
			array(),
			'4.9.1'
		);
	}
	if ( ! wp_style_is( 'ekdiloseis-schedulex', 'registered' ) ) {
		wp_register_style(
			'ekdiloseis-schedulex',
			EKDILOSEIS_URL . 'assets/ekdiloseis-schedulex.css',
			array( 'ekdiloseis', 'schedule-x-theme' ),
			EKDILOSEIS_VERSION
		);
	}
	if ( ! wp_script_is( 'ekdiloseis-schedulex', 'registered' ) ) {
		wp_register_script(
			'ekdiloseis-schedulex',
			EKDILOSEIS_URL . 'assets/ekdiloseis-schedulex.js',
			array( 'schedule-x-calendar', 'ekdiloseis-list' ),
			EKDILOSEIS_VERSION,
			true
		);
	}

	wp_enqueue_style( 'schedule-x-theme' );
	wp_enqueue_style( 'ekdiloseis-schedulex' );
	foreach ( array_keys( $scripts ) as $handle ) {
		wp_enqueue_script( $handle );
	}
	wp_enqueue_script( 'ekdiloseis-schedulex' );
	ekdiloseis_enqueue_google_font( 'schedulex' );
	ekdiloseis_enqueue_list_media();
}

/**
 * If the shortcode is rendered after wp_head, still print the Schedule-X stylesheets.
 */
function ekdiloseis_print_late_schedulex_style() {
	if ( wp_style_is( 'schedule-x-theme', 'enqueued' ) && ! wp_style_is( 'schedule-x-theme', 'done' ) ) {
		wp_print_styles( 'schedule-x-theme' );
	}
	if ( wp_style_is( 'ekdiloseis-schedulex', 'enqueued' ) && ! wp_style_is( 'ekdiloseis-schedulex', 'done' ) ) {
		wp_print_styles( 'ekdiloseis-schedulex' );
	}
}
add_action( 'wp_footer', 'ekdiloseis_print_late_schedulex_style', 5 );

/**
 * Schedule-X month grid. Day clicks list the same overlapping events as the other engines.
 *
 * @return string
 */
function ekdiloseis_render_schedulex() {
	ekdiloseis_enqueue_schedulex_assets();

	$month  = ekdiloseis_requested_month();
	$events = ekdiloseis_all_events();

	static $instance = 0;
	++$instance;
	$list_id = 'ekdiloseis-sx-list-' . $instance;

	$copy  = ekdiloseis_calendar_copy();
	$extra = array();
	if ( is_array( $copy['sxTranslations'] ) ) {
		$extra['sxTranslations'] = $copy['sxTranslations'];
	}
	$json = ekdiloseis_calendar_json( $events, $extra );

	ob_start();
	?>
	<div class="ekdiloseis-sx <?php echo esc_attr( ekdiloseis_layout_class() ); ?>" data-initial-date="<?php echo esc_attr( $month->format( 'Y-m-d' ) ); ?>"<?php echo ekdiloseis_root_style_attr( 'schedulex' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
		<div class="ekdiloseis__main">
			<div class="ekdiloseis-sx__calendar" aria-label="<?php echo esc_attr( 'Events calendar' ); ?>"></div>
		</div>
		<div class="ekdiloseis__list" id="<?php echo esc_attr( $list_id ); ?>" aria-live="polite">
			<p class="ekdiloseis__hint"><?php echo esc_html( $copy['hint'] ); ?></p>
		</div>
		<script type="application/json" class="ekdiloseis-sx__data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* encoded. ?></script>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Shortcode callback.
 *
 * @return string
 */
function ekdiloseis_shortcode() {
	$engine = ekdiloseis_get_engine();
	if ( 'fullcalendar' === $engine ) {
		return ekdiloseis_render_fullcalendar();
	}
	if ( 'toast' === $engine ) {
		return ekdiloseis_render_toast();
	}
	if ( 'eventcalendar' === $engine ) {
		return ekdiloseis_render_eventcalendar();
	}
	if ( 'schedulex' === $engine ) {
		return ekdiloseis_render_schedulex();
	}

	return ekdiloseis_render_custom();
}

/**
 * Small inline SVG icons for the Default calendar. Decorative only.
 *
 * @param string $name chevron-left|chevron-right|clock|pin|calendar.
 * @return string
 */
function ekdiloseis_svg_icon( $name ) {
	$paths = array(
		'chevron-left'  => '<path d="M15 18l-6-6 6-6"/>',
		'chevron-right' => '<path d="M9 18l6-6-6-6"/>',
		'clock'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'pin'           => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'calendar'      => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="ekdiloseis__svg ekdiloseis__svg--' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Category used for the badge and legend: the one with the lowest term id, like the color.
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null
 */
function ekdiloseis_event_primary_term( $post_id ) {
	$terms = get_the_terms( (int) $post_id, 'ekdilosi_katigoria' );
	if ( ! is_array( $terms ) || ! $terms ) {
		return null;
	}
	usort(
		$terms,
		static function ( $a, $b ) {
			return (int) $a->term_id <=> (int) $b->term_id;
		}
	);
	return $terms[0] instanceof WP_Term ? $terms[0] : null;
}

/**
 * Short plain-text excerpt for an event card.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function ekdiloseis_event_excerpt( $post_id ) {
	$post = get_post( (int) $post_id );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}
	$text = '' !== trim( (string) $post->post_excerpt ) ? (string) $post->post_excerpt : strip_shortcodes( (string) $post->post_content );
	$text = wp_trim_words( wp_strip_all_tags( $text ), 30, '…' );
	return trim( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Build the Default calendar for one month: the inner markup of the month card and the JSON the day panel uses.
 * Shared by the shortcode and the month AJAX endpoint, so both render exactly the same card.
 *
 * @param DateTimeImmutable $month    First day of the month (Europe/Athens).
 * @param string            $page_url Page the no-JS month links point to (without ekd_month).
 * @param string            $list_id  Id of the day panel the day buttons control.
 * @return array{month: string, title: string, main: string, payload: array<string, mixed>, json: string}
 */
function ekdiloseis_custom_month_parts( DateTimeImmutable $month, $page_url, $list_id ) {
	$events      = ekdiloseis_events_for_month( $month );
	$year        = (int) $month->format( 'Y' );
	$month_num   = (int) $month->format( 'n' );
	$days        = (int) $month->format( 't' );
	$pad         = (int) $month->format( 'N' ) - 1;
	$month_label = ekdiloseis_month_name( $month_num );
	$genitive    = ekdiloseis_month_name_genitive( $month_num );
	$today       = ekdiloseis_today_date();

	// Card extras for this engine only: category badge, location, excerpt. Legend from the same categories.
	$legend = array();
	foreach ( $events as $index => $event ) {
		$term = ekdiloseis_event_primary_term( (int) $event['id'] );
		$events[ $index ]['category'] = $term ? html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) : '';
		$location_raw                 = get_post_meta( (int) $event['id'], 'event_location', true );
		$events[ $index ]['location'] = ekdiloseis_sanitize_location( is_string( $location_raw ) ? $location_raw : '' );
		$events[ $index ]['excerpt']  = ekdiloseis_event_excerpt( (int) $event['id'] );
		if ( $term && ! isset( $legend[ (int) $term->term_id ] ) ) {
			$legend[ (int) $term->term_id ] = array(
				'name'  => $term->name,
				'color' => ekdiloseis_category_color( (int) $term->term_id ),
			);
		}
	}
	ksort( $legend );

	$marked = array();
	for ( $day = 1; $day <= $days; $day++ ) {
		$date = sprintf( '%04d-%02d-%02d', $year, $month_num, $day );
		foreach ( $events as $event ) {
			if ( ! ekdiloseis_overlaps_day( $event['start'], $event['end'], $date ) ) {
				continue;
			}
			$color = isset( $event['color'] ) && is_string( $event['color'] ) ? $event['color'] : '#6b7280';
			if ( ! isset( $marked[ $date ] ) ) {
				$marked[ $date ] = array();
			}
			$marked[ $date ][ $color ] = $color;
		}
	}

	$prev_month = $month->modify( '-1 month' );
	$prev_url   = add_query_arg( 'ekd_month', $prev_month->format( 'Y-m' ), $page_url );
	$next_url   = add_query_arg( 'ekd_month', $month->modify( '+1 month' )->format( 'Y-m' ), $page_url );
	$today_url  = add_query_arg( 'ekd_month', substr( $today, 0, 7 ), $page_url );
	$prev_days  = (int) $prev_month->format( 't' );
	$cells      = $pad + $days;
	$trail      = ( 7 - ( $cells % 7 ) ) % 7;

	$copy    = ekdiloseis_calendar_copy();
	$extra   = array(
		'weekdaysFull'   => $copy['weekdaysFull'],
		'monthsGenitive' => array_values( $copy['monthsGenitive'] ),
		'countOne'       => $copy['countOne'],
		'countMany'      => $copy['countMany'],
		'openEvent'      => $copy['openEvent'],
		'month'          => $month->format( 'Y-m' ),
	);
	$json    = ekdiloseis_calendar_json( $events, $extra );
	$payload = json_decode( $json, true );
	if ( ! is_array( $payload ) ) {
		$payload = array();
	}
	$weekdays = $copy['weekdays'];
	$title    = $month_label . ' ' . $year;

	ob_start();
	?>
		<nav class="ekdiloseis__nav" aria-label="Change month">
			<span class="ekdiloseis__arrows">
				<a class="ekdiloseis__navlink" rel="prev" data-month="<?php echo esc_attr( $prev_month->format( 'Y-m' ) ); ?>" href="<?php echo esc_url( $prev_url ); ?>" aria-label="<?php echo esc_attr( $copy['previousMonth'] ); ?>" title="<?php echo esc_attr( $copy['previousMonth'] ); ?>"><?php echo ekdiloseis_svg_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></a>
				<a class="ekdiloseis__navlink" rel="next" data-month="<?php echo esc_attr( $month->modify( '+1 month' )->format( 'Y-m' ) ); ?>" href="<?php echo esc_url( $next_url ); ?>" aria-label="<?php echo esc_attr( $copy['nextMonth'] ); ?>" title="<?php echo esc_attr( $copy['nextMonth'] ); ?>"><?php echo ekdiloseis_svg_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?></a>
			</span>
			<h2 class="ekdiloseis__title"><?php echo esc_html( $title ); ?></h2>
			<a class="ekdiloseis__today" href="<?php echo esc_url( $today_url ); ?>" data-month="<?php echo esc_attr( substr( $today, 0, 7 ) ); ?>" data-today="<?php echo esc_attr( $today ); ?>"><?php echo esc_html( $copy['today'] ); ?></a>
		</nav>
		<div class="ekdiloseis__grid">
			<?php foreach ( $weekdays as $weekday ) : ?>
				<div class="ekdiloseis__dow" aria-hidden="true"><?php echo esc_html( $weekday ); ?></div>
			<?php endforeach; ?>
			<?php for ( $i = $pad; $i > 0; $i-- ) : ?>
				<span class="ekdiloseis__day ekdiloseis__day--out" aria-hidden="true"><span class="ekdiloseis__num"><?php echo esc_html( (string) ( $prev_days - $i + 1 ) ); ?></span></span>
			<?php endfor; ?>
			<?php for ( $day = 1; $day <= $days; $day++ ) : ?>
				<?php
				$date    = sprintf( '%04d-%02d-%02d', $year, $month_num, $day );
				$colors  = isset( $marked[ $date ] ) && is_array( $marked[ $date ] ) ? $marked[ $date ] : array();
				$has     = ! empty( $colors );
				$classes = 'ekdiloseis__day' . ( $has ? ' ekdiloseis__day--has' : '' ) . ( $today === $date ? ' is-today' : '' );
				$label   = sprintf( '%d %s %d', $day, $genitive, $year );
				$label  .= $has ? ', ' . $copy['withEvents'] : ', ' . $copy['withoutEvents'];
				?>
				<button type="button" class="<?php echo esc_attr( $classes ); ?>" data-date="<?php echo esc_attr( $date ); ?>" data-has="<?php echo $has ? '1' : '0'; ?>" aria-pressed="false" aria-controls="<?php echo esc_attr( $list_id ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"<?php echo $today === $date ? ' aria-current="date"' : ''; ?>>
					<span class="ekdiloseis__num"><?php echo esc_html( (string) $day ); ?></span>
					<span class="ekdiloseis__dots" aria-hidden="true">
						<?php foreach ( $colors as $dot_color ) : ?>
							<span class="ekdiloseis__dot" style="background-color: <?php echo esc_attr( $dot_color ); ?>"></span>
						<?php endforeach; ?>
					</span>
				</button>
			<?php endfor; ?>
			<?php for ( $i = 1; $i <= $trail; $i++ ) : ?>
				<span class="ekdiloseis__day ekdiloseis__day--out" aria-hidden="true"><span class="ekdiloseis__num"><?php echo esc_html( (string) $i ); ?></span></span>
			<?php endfor; ?>
		</div>
		<?php if ( $legend ) : ?>
			<ul class="ekdiloseis__legend" aria-label="<?php echo esc_attr( $copy['legend'] ); ?>">
				<?php foreach ( $legend as $item ) : ?>
					<li class="ekdiloseis__legend-item"><span class="ekdiloseis__dot" style="background-color: <?php echo esc_attr( $item['color'] ); ?>" aria-hidden="true"></span><?php echo esc_html( $item['name'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php
	return array(
		'month'   => $month->format( 'Y-m' ),
		'title'   => $title,
		'main'    => (string) ob_get_clean(),
		'payload' => $payload,
		'json'    => $json,
	);
}

/**
 * Default (built-in) calendar: month card with legend, and a day panel with event cards.
 *
 * @return string
 */
function ekdiloseis_render_custom() {
	ekdiloseis_enqueue_assets();

	$month    = ekdiloseis_requested_month();
	$page_url = get_permalink();
	if ( ! is_string( $page_url ) || '' === $page_url ) {
		$page_url = home_url( '/' );
	}
	$page_url = remove_query_arg( 'ekd_month', $page_url );

	static $instance = 0;
	++$instance;
	$list_id = 'ekdiloseis-list-' . $instance;

	$parts = ekdiloseis_custom_month_parts( $month, $page_url, $list_id );
	$copy  = ekdiloseis_calendar_copy();

	ob_start();
	?>
	<div class="ekdiloseis <?php echo esc_attr( ekdiloseis_layout_class() ); ?>" data-month="<?php echo esc_attr( $parts['month'] ); ?>" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-page-url="<?php echo esc_url( $page_url ); ?>"<?php echo ekdiloseis_root_style_attr( 'custom' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
		<div class="ekdiloseis__main">
		<?php echo $parts['main']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while built. ?>
		</div>
		<div class="ekdiloseis__list" id="<?php echo esc_attr( $list_id ); ?>" aria-live="polite">
			<div class="ekdiloseis__dayhead">
				<p class="ekdiloseis__hint"><?php echo esc_html( $copy['hint'] ); ?></p>
			</div>
		</div>
		<script type="application/json" class="ekdiloseis__data"><?php echo $parts['json']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* encoded. ?></script>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Public, read-only AJAX endpoint for the Default calendar: one month card and its event data.
 * action=ekdiloseis_month, month=YYYY-MM (required, strict), list_id=ekdiloseis-list-N, page_url=same-site URL.
 * No nonce: it only reads published events (the same data the page shows) and changes nothing.
 */
function ekdiloseis_ajax_month() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only endpoint.
	$raw   = isset( $_GET['month'] ) && is_string( $_GET['month'] ) ? wp_unslash( $_GET['month'] ) : '';
	$month = ekdiloseis_parse_month( $raw );
	if ( null === $month ) {
		wp_send_json_error( array( 'message' => 'Invalid month. Use YYYY-MM.' ), 400 );
	}

	$list_id = isset( $_GET['list_id'] ) && is_string( $_GET['list_id'] ) ? wp_unslash( $_GET['list_id'] ) : '';
	if ( ! preg_match( '/^ekdiloseis-list-\d{1,4}$/', $list_id ) ) {
		$list_id = 'ekdiloseis-list-1';
	}

	$home     = home_url( '/' );
	$page_url = isset( $_GET['page_url'] ) && is_string( $_GET['page_url'] ) ? esc_url_raw( wp_unslash( $_GET['page_url'] ) ) : '';
	// Only links back to this site; anything else falls back to the home page.
	$page_url = '' !== $page_url ? wp_validate_redirect( $page_url, $home ) : $home;
	$page_url = remove_query_arg( 'ekd_month', $page_url );
	// phpcs:enable

	$parts = ekdiloseis_custom_month_parts( $month, $page_url, $list_id );
	nocache_headers();
	wp_send_json_success(
		array(
			'month' => $parts['month'],
			'title' => $parts['title'],
			'html'  => $parts['main'],
			'data'  => $parts['payload'],
		)
	);
}
add_action( 'wp_ajax_ekdiloseis_month', 'ekdiloseis_ajax_month' );
add_action( 'wp_ajax_nopriv_ekdiloseis_month', 'ekdiloseis_ajax_month' );
add_shortcode( 'myevents', 'ekdiloseis_shortcode' );
