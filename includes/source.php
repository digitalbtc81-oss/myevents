<?php
/**
 * Data source: read events from the plugin post type (default) or from any public post type
 * with a field mapping. Every calendar engine gets the same normalized event arrays.
 *
 * @package Ekdiloseis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Neutral color for events whose category has no color.
 *
 * @return string
 */
function ekdiloseis_source_neutral_color() {
	return '#6b7280';
}

/**
 * Mapping fields that hold a meta key.
 *
 * @return array<string, string> key => admin label.
 */
function ekdiloseis_source_map_fields() {
	return array(
		'start_date' => 'Start date field',
		'start_time' => 'Start time field',
		'end_date'   => 'End date field (optional)',
		'end_time'   => 'End time field (optional)',
		'location'   => 'Location field',
		'url'        => 'URL / link field (optional)',
	);
}

/**
 * Date formats accepted in the mapping.
 *
 * @return array<string, string>
 */
function ekdiloseis_source_date_formats() {
	return array(
		'auto'      => 'Auto-detect',
		'd/m/Y'     => 'dd/mm/yyyy',
		'Y-m-d'     => 'yyyy-mm-dd (Y-m-d)',
		'timestamp' => 'Unix timestamp',
	);
}

/**
 * Default option value: the plugin's own post type, no mapping.
 *
 * @return array<string, mixed>
 */
function ekdiloseis_source_defaults() {
	return array(
		'post_type'         => 'ekdilosi',
		'start_date'        => '',
		'start_time'        => '',
		'end_date'          => '',
		'end_time'          => '',
		'location'          => '',
		'url'               => '',
		'category_type'     => 'none',
		'category_taxonomy' => '',
		'category_meta'     => '',
		'date_format'       => 'auto',
		'colors'            => array(),
	);
}

/**
 * A meta key as typed or picked, or empty when unusable.
 *
 * @param mixed $value Raw.
 * @return string
 */
function ekdiloseis_source_sanitize_key( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( wp_unslash( $value ) );
	if ( '' === $value || strlen( $value ) > 191 || ! preg_match( '/^[A-Za-z0-9_\-:.]+$/', $value ) ) {
		return '';
	}
	return $value;
}

/**
 * Public post types a calendar can read (attachments excluded).
 *
 * @return array<string, string> name => label.
 */
function ekdiloseis_source_post_types() {
	$out = array();
	foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $name => $object ) {
		if ( 'attachment' === $name ) {
			continue;
		}
		$out[ $name ] = $object->labels->singular_name . ' (' . $name . ')';
	}
	if ( ! isset( $out['ekdilosi'] ) ) {
		$out = array( 'ekdilosi' => 'Event (ekdilosi)' ) + $out;
	}
	return $out;
}

/**
 * Sanitize the stored or submitted option. Safe to run twice on its own output.
 *
 * @param mixed $value Raw.
 * @return array<string, mixed>
 */
function ekdiloseis_sanitize_source( $value ) {
	$clean = ekdiloseis_source_defaults();
	if ( ! is_array( $value ) ) {
		return $clean;
	}

	$type = isset( $value['post_type'] ) && is_string( $value['post_type'] ) ? sanitize_key( $value['post_type'] ) : 'ekdilosi';
	// Post types register on init; the list may be incomplete earlier, so keep any existing type name then.
	if ( did_action( 'init' ) && 'ekdilosi' !== $type && ! isset( ekdiloseis_source_post_types()[ $type ] ) ) {
		$type = 'ekdilosi';
	}
	$clean['post_type'] = '' !== $type ? $type : 'ekdilosi';

	$keys = array_merge( array_keys( ekdiloseis_source_map_fields() ), array( 'category_meta' ) );
	foreach ( $keys as $field ) {
		$raw = isset( $value[ $field ] ) ? $value[ $field ] : '';
		if ( '__custom' === $raw ) {
			$raw = isset( $value[ $field . '__custom' ] ) ? $value[ $field . '__custom' ] : '';
		}
		$clean[ $field ] = ekdiloseis_source_sanitize_key( $raw );
	}

	$cat_type               = isset( $value['category_type'] ) && is_string( $value['category_type'] ) ? $value['category_type'] : 'none';
	$clean['category_type'] = in_array( $cat_type, array( 'none', 'taxonomy', 'meta' ), true ) ? $cat_type : 'none';
	$tax                    = isset( $value['category_taxonomy'] ) && is_string( $value['category_taxonomy'] ) ? sanitize_key( $value['category_taxonomy'] ) : '';
	$clean['category_taxonomy'] = ( '' !== $tax && ( ! did_action( 'init' ) || taxonomy_exists( $tax ) ) ) ? $tax : '';

	$format               = isset( $value['date_format'] ) && is_string( $value['date_format'] ) ? $value['date_format'] : 'auto';
	$clean['date_format'] = isset( ekdiloseis_source_date_formats()[ $format ] ) ? $format : 'auto';

	$colors = array();
	if ( isset( $value['colors'] ) && is_array( $value['colors'] ) ) {
		foreach ( $value['colors'] as $k => $row ) {
			if ( is_array( $row ) ) { // Form rows: [ value, color ].
				$label = isset( $row['value'] ) && is_string( $row['value'] ) ? $row['value'] : '';
				$color = isset( $row['color'] ) && is_string( $row['color'] ) ? $row['color'] : '';
			} else { // Stored map: value => color.
				$label = (string) $k;
				$color = is_string( $row ) ? $row : '';
			}
			$label = trim( sanitize_text_field( $label ) );
			$color = ekdiloseis_normalize_hex_color( $color );
			if ( '' !== $label && '' !== $color && count( $colors ) < 200 ) {
				$colors[ mb_substr( $label, 0, 190 ) ] = $color;
			}
		}
	}
	$clean['colors'] = $colors;

	return $clean;
}

/**
 * Saved data source.
 *
 * @return array<string, mixed>
 */
function ekdiloseis_get_source() {
	$raw = get_option( 'ekdiloseis_source', array() );
	return ekdiloseis_sanitize_source( is_array( $raw ) ? $raw : array() );
}

/**
 * True when events come from the plugin's own post type (the original behavior).
 *
 * @return bool
 */
function ekdiloseis_source_is_default() {
	$source = ekdiloseis_get_source();
	return 'ekdilosi' === $source['post_type'];
}

/**
 * Parse a stored date value to Y-m-d plus an optional H:i:s found in the same value.
 *
 * @param mixed  $raw    Meta value.
 * @param string $format auto|d/m/Y|Y-m-d|timestamp.
 * @return array{date: string, time: string}|null
 */
function ekdiloseis_source_parse_date( $raw, $format = 'auto' ) {
	if ( is_array( $raw ) ) {
		$raw = reset( $raw );
	}
	if ( ! is_scalar( $raw ) ) {
		return null;
	}
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return null;
	}
	$tz = new DateTimeZone( 'Europe/Athens' );

	$build = static function ( $y, $m, $d, $time = '' ) {
		$y = (int) $y;
		$m = (int) $m;
		$d = (int) $d;
		if ( $y < 100 ) {
			$y += $y < 70 ? 2000 : 1900;
		}
		if ( ! checkdate( $m, $d, $y ) ) {
			return null;
		}
		return array(
			'date' => sprintf( '%04d-%02d-%02d', $y, $m, $d ),
			'time' => $time,
		);
	};
	$tail_time = static function ( $rest ) {
		$rest = trim( (string) $rest );
		return '' === $rest ? '' : ekdiloseis_source_parse_time( $rest );
	};

	if ( 'timestamp' === $format || ( 'auto' === $format && preg_match( '/^\d{9,11}$/', $raw ) ) ) {
		if ( ! preg_match( '/^\d{1,11}$/', $raw ) ) {
			return null;
		}
		$dt = ( new DateTimeImmutable( '@' . $raw ) )->setTimezone( $tz );
		return array(
			'date' => $dt->format( 'Y-m-d' ),
			'time' => $dt->format( 'H:i:s' ),
		);
	}
	if ( ( 'Y-m-d' === $format || 'auto' === $format ) && preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[T ](.*))?$/', $raw, $m ) ) {
		return $build( $m[1], $m[2], $m[3], $tail_time( $m[4] ?? '' ) );
	}
	if ( ( 'd/m/Y' === $format || 'auto' === $format ) && preg_match( '#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{2}|\d{4})(?:\s+(.*))?$#', $raw, $m ) ) {
		return $build( $m[3], $m[2], $m[1], $tail_time( $m[4] ?? '' ) );
	}
	// ACF date picker stores Ymd.
	if ( 'auto' === $format && preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $raw, $m ) ) {
		return $build( $m[1], $m[2], $m[3] );
	}
	return null;
}

/**
 * Parse a clock value: 19:00, 7:00 μμ, 7:00 π.μ., 7 pm, 19.30. Returns H:i:s or empty.
 *
 * @param mixed $raw Meta value.
 * @return string
 */
function ekdiloseis_source_parse_time( $raw ) {
	if ( is_array( $raw ) ) {
		$raw = reset( $raw );
	}
	if ( ! is_scalar( $raw ) ) {
		return '';
	}
	$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( (string) $raw ), 'UTF-8' ) : strtolower( trim( (string) $raw ) );
	$text = str_replace( array( '.', ' ', "\xC2\xA0" ), array( '', '', '' ), $text );
	// 19.30 lost its dot above; recover HHMM written with a dot or colon only when no suffix follows.
	if ( ! preg_match( '/^(\d{1,2})(?::(\d{2}))?(?::(\d{2}))?(.*)$/u', $text, $m ) ) {
		return '';
	}
	$hour   = (int) $m[1];
	$minute = isset( $m[2] ) && '' !== $m[2] ? (int) $m[2] : 0;
	$second = isset( $m[3] ) && '' !== $m[3] ? (int) $m[3] : 0;
	$suffix = $m[4];
	if ( '' !== $suffix && preg_match( '/^(\d{2})$/', $suffix, $mm ) && '' === $m[2] && strlen( $m[1] ) <= 2 ) {
		$minute = (int) $mm[1]; // 19.30 → "1930" is not reached; "19" + "30" when written 19.30.
		$suffix = '';
	}
	if ( '' === $m[2] && strlen( $m[1] ) > 2 ) {
		return '';
	}
	$pm = array( 'μμ', 'pm', 'p', 'απόγευμα', 'βράδυ' );
	$am = array( 'πμ', 'am', 'a', 'πρωί' );
	if ( in_array( $suffix, $pm, true ) ) {
		if ( $hour < 1 || $hour > 12 ) {
			return '';
		}
		$hour = 12 === $hour ? 12 : $hour + 12;
	} elseif ( in_array( $suffix, $am, true ) ) {
		if ( $hour < 1 || $hour > 12 ) {
			return '';
		}
		$hour = 12 === $hour ? 0 : $hour;
	} elseif ( '' !== $suffix && ! in_array( $suffix, array( 'h', 'ω', 'ώρα' ), true ) ) {
		return '';
	}
	if ( $hour > 23 || $minute > 59 || $second > 59 ) {
		return '';
	}
	return sprintf( '%02d:%02d:%02d', $hour, $minute, $second );
}

/**
 * First scalar of a meta value as plain text.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return string
 */
function ekdiloseis_source_meta_text( $post_id, $key ) {
	if ( '' === $key ) {
		return '';
	}
	$raw = get_post_meta( (int) $post_id, $key, true );
	if ( is_array( $raw ) ) {
		$raw = reset( $raw );
	}
	return is_scalar( $raw ) ? trim( sanitize_text_field( (string) $raw ) ) : '';
}

/**
 * Category name and color for one post, from a taxonomy or a meta field.
 *
 * @param int                  $post_id Post ID.
 * @param array<string, mixed> $source  Source option.
 * @return array{name: string, color: string}
 */
function ekdiloseis_source_category( $post_id, $source ) {
	$neutral = ekdiloseis_source_neutral_color();
	$colors  = is_array( $source['colors'] ) ? $source['colors'] : array();
	if ( 'taxonomy' === $source['category_type'] && '' !== $source['category_taxonomy'] ) {
		$terms = get_the_terms( (int) $post_id, $source['category_taxonomy'] );
		if ( ! is_array( $terms ) || ! $terms ) {
			return array( 'name' => '', 'color' => $neutral );
		}
		usort(
			$terms,
			static function ( $a, $b ) {
				return (int) $a->term_id <=> (int) $b->term_id;
			}
		);
		$term = $terms[0];
		$name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
		if ( 'ekdilosi_katigoria' === $source['category_taxonomy'] ) {
			$color = ekdiloseis_category_color( (int) $term->term_id );
		} else {
			$color = isset( $colors[ $name ] ) ? $colors[ $name ] : $neutral;
		}
		return array( 'name' => $name, 'color' => $color );
	}
	if ( 'meta' === $source['category_type'] && '' !== $source['category_meta'] ) {
		$name = html_entity_decode( ekdiloseis_source_meta_text( $post_id, $source['category_meta'] ), ENT_QUOTES, 'UTF-8' );
		if ( '' === $name ) {
			return array( 'name' => '', 'color' => $neutral );
		}
		return array(
			'name'  => $name,
			'color' => isset( $colors[ $name ] ) ? $colors[ $name ] : $neutral,
		);
	}
	return array( 'name' => '', 'color' => $neutral );
}

/**
 * "All day" in the public calendar language.
 *
 * @return string
 */
function ekdiloseis_source_all_day_label() {
	return 'el' === ekdiloseis_get_locale() ? 'Ολοήμερο' : 'All day';
}

/**
 * One mapped post as the normalized event payload, or null when it has no usable date.
 *
 * @param WP_Post              $post   Post.
 * @param array<string, mixed> $source Source option.
 * @return array<string, mixed>|null
 */
function ekdiloseis_source_event_from_post( WP_Post $post, $source ) {
	$post_id = (int) $post->ID;
	$format  = (string) $source['date_format'];

	$start = '' !== $source['start_date'] ? ekdiloseis_source_parse_date( get_post_meta( $post_id, $source['start_date'], true ), $format ) : null;
	if ( null === $start ) {
		// No start date: fall back to the post date (day only).
		$start = array(
			'date' => substr( (string) $post->post_date, 0, 10 ),
			'time' => '',
		);
	}
	$start_time = '' !== $source['start_time'] ? ekdiloseis_source_parse_time( get_post_meta( $post_id, $source['start_time'], true ) ) : '';
	if ( '' === $start_time ) {
		$start_time = $start['time'];
	}

	$end = '' !== $source['end_date'] ? ekdiloseis_source_parse_date( get_post_meta( $post_id, $source['end_date'], true ), $format ) : null;
	$end_date = null !== $end ? $end['date'] : $start['date'];
	$end_time = '' !== $source['end_time'] ? ekdiloseis_source_parse_time( get_post_meta( $post_id, $source['end_time'], true ) ) : '';
	if ( '' === $end_time && null !== $end ) {
		$end_time = $end['time'];
	}

	$tz       = new DateTimeZone( 'Europe/Athens' );
	$start_dt = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $start['date'] . ' ' . ( '' !== $start_time ? $start_time : '00:00:00' ), $tz );
	if ( ! $start_dt instanceof DateTimeImmutable ) {
		return null;
	}
	$end_dt = null;
	if ( '' !== $end_time ) {
		$candidate = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $end_date . ' ' . $end_time, $tz );
		if ( $candidate instanceof DateTimeImmutable && $candidate > $start_dt ) {
			$end_dt = $candidate;
		} else {
			$end_time = '';
		}
	}
	if ( null === $end_dt && $end_date > $start['date'] ) {
		$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $end_date, $tz );
		if ( $day instanceof DateTimeImmutable ) {
			$end_dt = $day->modify( '+1 day' ); // Through the end of the last day.
		}
	}
	$next_midnight = $start_dt->setTime( 0, 0, 0 )->modify( '+1 day' );
	if ( null === $end_dt ) {
		if ( '' !== $start_time ) {
			$hour   = $start_dt->modify( '+1 hour' );
			$end_dt = $hour < $next_midnight ? $hour : $next_midnight;
		} else {
			$end_dt = $next_midnight;
		}
	}

	if ( '' === $start_time ) {
		$label = ekdiloseis_source_all_day_label();
	} else {
		$label = substr( $start_time, 0, 5 ) . ( '' !== $end_time ? '–' . substr( $end_time, 0, 5 ) : '' );
	}

	$thumb = get_the_post_thumbnail_url( $post_id, 'medium' );
	if ( ! is_string( $thumb ) || '' === $thumb ) {
		$thumb = get_the_post_thumbnail_url( $post_id, 'full' );
	}
	$thumb = is_string( $thumb ) && '' !== $thumb ? esc_url_raw( $thumb ) : '';

	$url = '';
	if ( '' !== $source['url'] ) {
		$url = esc_url_raw( ekdiloseis_source_meta_text( $post_id, $source['url'] ), array( 'http', 'https' ) );
	}
	if ( '' === $url ) {
		$permalink = get_permalink( $post_id );
		$url       = is_string( $permalink ) ? esc_url_raw( $permalink ) : '';
	}

	$category = ekdiloseis_source_category( $post_id, $source );
	$location = '' !== $source['location'] ? ekdiloseis_sanitize_location( ekdiloseis_source_meta_text( $post_id, $source['location'] ) ) : '';

	$event = array(
		'id'        => $post_id,
		'title'     => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
		'start'     => $start_dt->format( 'Y-m-d H:i:s' ),
		'end'       => $end_dt->format( 'Y-m-d H:i:s' ),
		'time'      => $label,
		'timeLabel' => $label,
		'allDay'    => '' === $start_time,
		'thumb'     => '' !== $thumb ? $thumb : null,
		'url'       => $url,
		'color'     => $category['color'],
		'textColor' => ekdiloseis_contrast_text_color( $category['color'] ),
		'category'  => $category['name'],
		'location'  => $location,
		'excerpt'   => ekdiloseis_event_excerpt( $post_id ),
	);
	if ( 'icon' === ekdiloseis_get_list_media() ) {
		$event['icon'] = 'fa-solid fa-calendar';
	}
	return $event;
}

/**
 * Every published post of the mapped type, normalized. Cached for the request.
 *
 * @return array<int, array<string, mixed>>
 */
function ekdiloseis_source_all_mapped_events() {
	static $cache = array();
	$source = ekdiloseis_get_source();
	$hash   = md5( (string) wp_json_encode( $source ) . ekdiloseis_get_list_media() . ekdiloseis_get_locale() );
	if ( isset( $cache[ $hash ] ) ) {
		return $cache[ $hash ];
	}
	$posts  = get_posts(
		array(
			'post_type'        => $source['post_type'],
			'post_status'      => 'publish',
			'posts_per_page'   => 2000,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);
	$events = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$event = ekdiloseis_source_event_from_post( $post, $source );
		if ( null !== $event ) {
			$events[] = $event;
		}
	}
	usort(
		$events,
		static function ( $a, $b ) {
			return array( $a['start'], $a['id'] ) <=> array( $b['start'], $b['id'] );
		}
	);
	$cache[ $hash ] = $events;
	return $events;
}

/**
 * Normalized events from the selected source. With the default source this is the original ekdilosi query.
 * Range bounds are wall-clock Y-m-d H:i:s; empty strings mean no limit.
 *
 * @param string $range_start Inclusive lower bound for event end (end > start bound).
 * @param string $range_end   Exclusive upper bound for event start.
 * @return array<int, array<string, mixed>>
 */
function ekdiloseis_get_events( $range_start = '', $range_end = '' ) {
	if ( ekdiloseis_source_is_default() ) {
		if ( '' === $range_start && '' === $range_end ) {
			return ekdiloseis_all_events();
		}
		$month = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $range_start, new DateTimeZone( 'Europe/Athens' ) );
		return $month instanceof DateTimeImmutable ? ekdiloseis_events_for_month( $month ) : array();
	}
	$out = array();
	foreach ( ekdiloseis_source_all_mapped_events() as $event ) {
		if ( '' !== $range_end && $event['start'] >= $range_end ) {
			continue;
		}
		if ( '' !== $range_start && $event['end'] <= $range_start ) {
			continue;
		}
		$out[] = $event;
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Admin: settings field, AJAX helpers.
 * ------------------------------------------------------------------------- */

/**
 * Distinct public meta keys used by a post type (no leading underscore).
 *
 * @param string $post_type Post type.
 * @return string[]
 */
function ekdiloseis_source_meta_keys( $post_type ) {
	global $wpdb;
	$keys = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT pm.meta_key FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.post_type = %s AND pm.meta_key NOT LIKE %s
			ORDER BY pm.meta_key ASC LIMIT 300",
			$post_type,
			$wpdb->esc_like( '_' ) . '%'
		)
	);
	return is_array( $keys ) ? array_values( array_filter( array_map( 'strval', $keys ) ) ) : array();
}

/**
 * Taxonomies attached to a post type.
 *
 * @param string $post_type Post type.
 * @return array<string, string> name => label.
 */
function ekdiloseis_source_taxonomies( $post_type ) {
	$out = array();
	foreach ( get_object_taxonomies( $post_type, 'objects' ) as $name => $tax ) {
		if ( 'post_format' === $name ) {
			continue;
		}
		$out[ $name ] = $tax->labels->singular_name . ' (' . $name . ')';
	}
	return $out;
}

/**
 * Distinct category values for the color list: term names or meta values.
 *
 * @param string $post_type Post type.
 * @param string $type      taxonomy|meta.
 * @param string $key       Taxonomy or meta key.
 * @return string[]
 */
function ekdiloseis_source_category_values( $post_type, $type, $key ) {
	global $wpdb;
	if ( '' === $key ) {
		return array();
	}
	if ( 'taxonomy' === $type ) {
		if ( ! taxonomy_exists( $key ) ) {
			return array();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $key,
				'hide_empty' => false,
				'number'     => 100,
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return array_values(
			array_map(
				static function ( $term ) {
					return html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
				},
				$terms
			)
		);
	}
	if ( 'meta' === $type ) {
		$values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s AND pm.meta_key = %s AND pm.meta_value <> ''
				ORDER BY pm.meta_value ASC LIMIT 100",
				$post_type,
				$key
			)
		);
		$out = array();
		foreach ( is_array( $values ) ? $values : array() as $value ) {
			$value = maybe_unserialize( $value );
			if ( is_array( $value ) ) {
				$value = reset( $value );
			}
			if ( is_scalar( $value ) ) {
				$text = trim( sanitize_text_field( (string) $value ) );
				if ( '' !== $text && mb_strlen( $text ) <= 190 ) {
					$out[ $text ] = $text;
				}
			}
		}
		return array_values( $out );
	}
	return array();
}

/**
 * Admin AJAX: meta keys, taxonomies, and category values for a post type.
 */
function ekdiloseis_ajax_source_fields() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'ekdiloseis_source_fields', 'nonce' );
	$post_type = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';
	if ( ! isset( ekdiloseis_source_post_types()[ $post_type ] ) ) {
		wp_send_json_error( array( 'message' => 'Unknown post type.' ), 400 );
	}
	$cat_type = isset( $_POST['category_type'] ) ? sanitize_key( wp_unslash( $_POST['category_type'] ) ) : 'none';
	$cat_key  = isset( $_POST['category_key'] ) ? ekdiloseis_source_sanitize_key( sanitize_text_field( wp_unslash( $_POST['category_key'] ) ) ) : '';
	if ( 'taxonomy' === $cat_type ) {
		$cat_key = sanitize_key( $cat_key );
	}
	wp_send_json_success(
		array(
			'metaKeys'   => ekdiloseis_source_meta_keys( $post_type ),
			'taxonomies' => ekdiloseis_source_taxonomies( $post_type ),
			'values'     => ekdiloseis_source_category_values( $post_type, $cat_type, $cat_key ),
		)
	);
}
add_action( 'wp_ajax_ekdiloseis_source_fields', 'ekdiloseis_ajax_source_fields' );

/**
 * One meta-key picker: a select of known keys plus "Custom key…" with a text box.
 *
 * @param string   $field Option sub-key.
 * @param string   $value Saved meta key.
 * @param string[] $keys  Known keys.
 */
function ekdiloseis_source_key_picker( $field, $value, $keys ) {
	$custom = '' !== $value && ! in_array( $value, $keys, true );
	$id     = 'ekdiloseis-source-' . $field;
	?>
	<span class="ekdiloseis-keypick" data-field="<?php echo esc_attr( $field ); ?>">
		<select id="<?php echo esc_attr( $id ); ?>" name="ekdiloseis_source[<?php echo esc_attr( $field ); ?>]" class="ekdiloseis-keypick__select" data-value="<?php echo esc_attr( $value ); ?>">
			<option value=""><?php echo esc_html( '— None —' ); ?></option>
			<?php foreach ( $keys as $key ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $key ); ?></option>
			<?php endforeach; ?>
			<option value="__custom" <?php selected( $custom ); ?>><?php echo esc_html( 'Custom key…' ); ?></option>
		</select>
		<input type="text" class="regular-text code ekdiloseis-keypick__custom" name="ekdiloseis_source[<?php echo esc_attr( $field ); ?>__custom]" value="<?php echo esc_attr( $custom ? $value : '' ); ?>" placeholder="<?php echo esc_attr( 'meta_key' ); ?>" aria-label="<?php echo esc_attr( 'Custom meta key' ); ?>"<?php echo $custom ? '' : ' hidden'; ?>>
	</span>
	<?php
}

/**
 * Section description.
 */
function ekdiloseis_source_section_cb() {
	echo '<p>' . esc_html( 'Choose where the calendar reads events from. Events (ekdilosi) is the plugin post type and keeps the original behavior. Any other public post type needs a field mapping.' ) . '</p>';
}

/**
 * Data source field: post type, mapping, category colors.
 */
function ekdiloseis_source_field_cb() {
	$source     = ekdiloseis_get_source();
	$types      = ekdiloseis_source_post_types();
	$is_default = 'ekdilosi' === $source['post_type'];
	$keys       = $is_default ? array() : ekdiloseis_source_meta_keys( $source['post_type'] );
	$taxes      = $is_default ? array() : ekdiloseis_source_taxonomies( $source['post_type'] );
	$cat_key    = 'taxonomy' === $source['category_type'] ? $source['category_taxonomy'] : $source['category_meta'];
	$values     = $is_default ? array() : ekdiloseis_source_category_values( $source['post_type'], $source['category_type'], $cat_key );
	foreach ( array_keys( $source['colors'] ) as $saved ) {
		if ( ! in_array( (string) $saved, $values, true ) ) {
			$values[] = (string) $saved;
		}
	}
	?>
	<div class="ekdiloseis-source" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ekdiloseis_source_fields' ) ); ?>">
		<p>
			<label for="ekdiloseis-source-post_type"><strong><?php echo esc_html( 'Source post type' ); ?></strong></label><br>
			<select id="ekdiloseis-source-post_type" name="ekdiloseis_source[post_type]">
				<?php foreach ( $types as $name => $label ) : ?>
					<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $source['post_type'], $name ); ?>><?php echo esc_html( 'ekdilosi' === $name ? $label . ' — default' : $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<div class="ekdiloseis-source__map"<?php echo $is_default ? ' hidden' : ''; ?>>
			<p class="description"><?php echo esc_html( 'Pick the custom fields of that post type. Lists show the meta keys found on its posts; choose Custom key… to type another. A missing start date uses the post date.' ); ?></p>
			<table class="ekdiloseis-source__table">
				<?php foreach ( ekdiloseis_source_map_fields() as $field => $label ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( 'ekdiloseis-source-' . $field ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><?php ekdiloseis_source_key_picker( $field, (string) $source[ $field ], $keys ); ?></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="ekdiloseis-source-date_format"><?php echo esc_html( 'Date format' ); ?></label></th>
					<td>
						<select id="ekdiloseis-source-date_format" name="ekdiloseis_source[date_format]">
							<?php foreach ( ekdiloseis_source_date_formats() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $source['date_format'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html( 'Times such as 19:00, 7:00 pm, 7:00 μμ and 11:00 πμ are understood.' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html( 'Category source' ); ?></th>
					<td>
						<fieldset class="ekdiloseis-source__cat">
							<label><input type="radio" name="ekdiloseis_source[category_type]" value="none" <?php checked( $source['category_type'], 'none' ); ?>> <?php echo esc_html( 'None' ); ?></label>
							<label><input type="radio" name="ekdiloseis_source[category_type]" value="taxonomy" <?php checked( $source['category_type'], 'taxonomy' ); ?>> <?php echo esc_html( 'Taxonomy' ); ?></label>
							<label><input type="radio" name="ekdiloseis_source[category_type]" value="meta" <?php checked( $source['category_type'], 'meta' ); ?>> <?php echo esc_html( 'Meta field' ); ?></label>
						</fieldset>
						<p class="ekdiloseis-source__cat-tax"<?php echo 'taxonomy' === $source['category_type'] ? '' : ' hidden'; ?>>
							<select name="ekdiloseis_source[category_taxonomy]" class="ekdiloseis-source__taxonomy" aria-label="<?php echo esc_attr( 'Category taxonomy' ); ?>">
								<option value=""><?php echo esc_html( '— Select —' ); ?></option>
								<?php foreach ( $taxes as $name => $label ) : ?>
									<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $source['category_taxonomy'], $name ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p class="ekdiloseis-source__cat-meta"<?php echo 'meta' === $source['category_type'] ? '' : ' hidden'; ?>>
							<?php ekdiloseis_source_key_picker( 'category_meta', (string) $source['category_meta'], $keys ); ?>
						</p>
					</td>
				</tr>
				<tr class="ekdiloseis-source__colors-row"<?php echo 'none' === $source['category_type'] ? ' hidden' : ''; ?>>
					<th scope="row"><?php echo esc_html( 'Category colors' ); ?></th>
					<td>
						<p class="description"><?php echo esc_html( 'One color per category value. For a taxonomy every term is listed, and new terms appear here with gray #6b7280 until you pick a color. The same color is also on the term add and edit screens of that taxonomy; both places save the same value. Event Categories (ekdilosi_katigoria) keeps its own term colors.' ); ?></p>
						<div class="ekdiloseis-source__colors">
							<?php foreach ( array_values( $values ) as $i => $value ) : ?>
								<?php $color = isset( $source['colors'][ $value ] ) ? $source['colors'][ $value ] : ''; ?>
								<div class="ekdiloseis-source__color">
									<input type="hidden" name="ekdiloseis_source[colors][<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( $value ); ?>">
									<input type="color" name="ekdiloseis_source[colors][<?php echo (int) $i; ?>][color]" value="<?php echo esc_attr( '' !== $color ? $color : ekdiloseis_source_neutral_color() ); ?>" aria-label="<?php echo esc_attr( 'Color for ' . $value ); ?>">
									<span><?php echo esc_html( $value ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}

/**
 * Settings registration for the data source.
 */
function ekdiloseis_register_source_setting() {
	register_setting(
		'ekdiloseis_settings',
		'ekdiloseis_source',
		array(
			'type'              => 'array',
			'description'       => 'Post type and field mapping the calendar reads events from.',
			'sanitize_callback' => 'ekdiloseis_sanitize_source',
			'default'           => ekdiloseis_source_defaults(),
			'show_in_rest'      => false,
		)
	);
	add_settings_section( 'ekdiloseis_source_section', 'Data source', 'ekdiloseis_source_section_cb', 'ekdiloseis-settings' );
	add_settings_field( 'ekdiloseis_source', 'Source', 'ekdiloseis_source_field_cb', 'ekdiloseis-settings', 'ekdiloseis_source_section' );
}
add_action( 'admin_init', 'ekdiloseis_register_source_setting', 5 );

/**
 * Settings-screen script for the Data source field. Called from ekdiloseis_enqueue_settings_assets().
 */
function ekdiloseis_enqueue_source_assets() {
	$js = EKDILOSEIS_DIR . 'assets/ekdiloseis-source.js';
	if ( is_readable( $js ) ) {
		wp_enqueue_script( 'ekdiloseis-source', EKDILOSEIS_URL . 'assets/ekdiloseis-source.js', array(), (string) filemtime( $js ), true );
	}
}

/* ---------------------------------------------------------------------------
 * Term screens of the mapped taxonomy: a Color field and column that read and write
 * the Category colors list of the Data source (one place stores the colors).
 * ------------------------------------------------------------------------- */

/**
 * The mapped category taxonomy that uses Data source colors, or empty.
 * Event Categories (ekdilosi_katigoria) keeps its own term color field.
 *
 * @return string
 */
function ekdiloseis_source_color_taxonomy() {
	$source = ekdiloseis_get_source();
	if ( 'ekdilosi' === $source['post_type'] || 'taxonomy' !== $source['category_type'] ) {
		return '';
	}
	$tax = (string) $source['category_taxonomy'];
	return ( '' !== $tax && 'ekdilosi_katigoria' !== $tax && taxonomy_exists( $tax ) ) ? $tax : '';
}

/**
 * Plain term name, the key used in the colors list.
 *
 * @param WP_Term|string $term Term or raw name.
 * @return string
 */
function ekdiloseis_source_term_key( $term ) {
	$name = $term instanceof WP_Term ? $term->name : (string) $term;
	return trim( sanitize_text_field( html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ) ) );
}

/**
 * Saved color for a term name, or empty.
 *
 * @param string $key Term key.
 * @return string
 */
function ekdiloseis_source_term_color( $key ) {
	$source = ekdiloseis_get_source();
	return isset( $source['colors'][ $key ] ) ? (string) $source['colors'][ $key ] : '';
}

/**
 * Write one term color into the Data source option (empty removes it).
 *
 * @param string $key   Term key.
 * @param string $color #rrggbb or empty.
 * @param string $old   Previous key to drop (rename).
 */
function ekdiloseis_source_set_term_color( $key, $color, $old = '' ) {
	$source = ekdiloseis_get_source();
	$colors = $source['colors'];
	if ( '' !== $old && $old !== $key ) {
		if ( '' === $color && isset( $colors[ $old ] ) ) {
			$color = $colors[ $old ];
		}
		unset( $colors[ $old ] );
	}
	if ( '' !== $key ) {
		if ( '' !== $color ) {
			$colors[ $key ] = $color;
		} else {
			unset( $colors[ $key ] );
		}
	}
	if ( $colors !== $source['colors'] ) {
		$source['colors'] = $colors;
		update_option( 'ekdiloseis_source', $source );
	}
}

/**
 * Color input markup shared by the add and edit screens.
 *
 * @param string $value Current color.
 */
function ekdiloseis_source_term_color_input( $value ) {
	wp_nonce_field( 'ekdiloseis_source_term_color', 'ekdiloseis_source_term_color_nonce' );
	?>
	<input type="color" id="ekdiloseis-source-term-color" name="ekdiloseis_source_term_color" oninput="var c=document.querySelector('input[name=ekdiloseis_source_term_color_on]');if(c){c.checked=true;}" value="<?php echo esc_attr( '' !== $value ? $value : ekdiloseis_source_neutral_color() ); ?>">
	<label><input type="checkbox" name="ekdiloseis_source_term_color_on" value="1" <?php checked( '' !== $value ); ?>> <?php echo esc_html( 'Use this color' ); ?></label>
	<p class="description"><?php echo esc_html( 'Calendar color for this category. It is the same value as Events > Settings > Data source > Category colors; changing it in either place updates both. Unchecked uses gray #6b7280.' ); ?></p>
	<?php
}

/**
 * Add-term screen field.
 */
function ekdiloseis_source_term_add_field() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="form-field">
		<label for="ekdiloseis-source-term-color"><?php echo esc_html( 'Color' ); ?></label>
		<?php ekdiloseis_source_term_color_input( '' ); ?>
	</div>
	<?php
}

/**
 * Edit-term screen field.
 *
 * @param WP_Term $term Term.
 */
function ekdiloseis_source_term_edit_field( $term ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<tr class="form-field">
		<th scope="row"><label for="ekdiloseis-source-term-color"><?php echo esc_html( 'Color' ); ?></label></th>
		<td><?php ekdiloseis_source_term_color_input( ekdiloseis_source_term_color( ekdiloseis_source_term_key( $term ) ) ); ?></td>
	</tr>
	<?php
}

/**
 * Remember the name before an update so a rename keeps its color.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy.
 */
function ekdiloseis_source_term_before_edit( $term_id, $taxonomy ) {
	if ( $taxonomy !== ekdiloseis_source_color_taxonomy() ) {
		return;
	}
	$term = get_term( (int) $term_id, $taxonomy );
	if ( $term instanceof WP_Term ) {
		$GLOBALS['ekdiloseis_source_old_term_key'][ (int) $term_id ] = ekdiloseis_source_term_key( $term );
	}
}

/**
 * Save the term color (created or edited), and carry the color across renames.
 *
 * @param int $term_id Term ID.
 */
function ekdiloseis_source_term_save( $term_id ) {
	$tax  = ekdiloseis_source_color_taxonomy();
	$term = get_term( (int) $term_id, $tax );
	if ( '' === $tax || ! $term instanceof WP_Term ) {
		return;
	}
	$key = ekdiloseis_source_term_key( $term );
	$old = isset( $GLOBALS['ekdiloseis_source_old_term_key'][ (int) $term_id ] ) ? $GLOBALS['ekdiloseis_source_old_term_key'][ (int) $term_id ] : '';

	$submitted = isset( $_POST['ekdiloseis_source_term_color_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ekdiloseis_source_term_color_nonce'] ) ), 'ekdiloseis_source_term_color' )
		&& current_user_can( 'manage_options' );
	if ( $submitted ) {
		$on    = isset( $_POST['ekdiloseis_source_term_color_on'] );
		$color = $on && isset( $_POST['ekdiloseis_source_term_color'] ) ? ekdiloseis_normalize_hex_color( sanitize_text_field( wp_unslash( $_POST['ekdiloseis_source_term_color'] ) ) ) : '';
		if ( '' !== $old && $old !== $key ) {
			ekdiloseis_source_set_term_color( '', '', $old );
		}
		ekdiloseis_source_set_term_color( $key, $color );
	} elseif ( '' !== $old && $old !== $key ) {
		ekdiloseis_source_set_term_color( $key, '', $old ); // Renamed elsewhere (REST, wp-cli): move the color.
	}
}

/**
 * Drop the color of a deleted term.
 *
 * @param int     $term_id      Term ID.
 * @param int     $tt_id        Term taxonomy ID.
 * @param WP_Term $deleted_term Deleted term copy.
 */
function ekdiloseis_source_term_deleted( $term_id, $tt_id, $deleted_term ) {
	unset( $term_id, $tt_id );
	if ( $deleted_term instanceof WP_Term ) {
		ekdiloseis_source_set_term_color( '', '', ekdiloseis_source_term_key( $deleted_term ) );
	}
}

/**
 * Color column on the term list.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function ekdiloseis_source_term_columns( $columns ) {
	$out = array();
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'name' === $key ) {
			$out['ekdiloseis_color'] = 'Color';
		}
	}
	return $out;
}

/**
 * Color column cell.
 *
 * @param string $content Cell.
 * @param string $column  Column.
 * @param int    $term_id Term ID.
 * @return string
 */
function ekdiloseis_source_term_column( $content, $column, $term_id ) {
	if ( 'ekdiloseis_color' !== $column ) {
		return $content;
	}
	$term  = get_term( (int) $term_id );
	$color = $term instanceof WP_Term ? ekdiloseis_source_term_color( ekdiloseis_source_term_key( $term ) ) : '';
	$shown = '' !== $color ? $color : ekdiloseis_source_neutral_color();
	return '<span style="display:inline-block;width:14px;height:14px;border-radius:50%;vertical-align:middle;margin-right:6px;background:' . esc_attr( $shown ) . '"></span><code>' . esc_html( '' !== $color ? $color : 'default' ) . '</code>';
}

/**
 * Hook the term screens of the mapped taxonomy (after every plugin registered its taxonomies).
 */
function ekdiloseis_source_term_hooks() {
	$tax = ekdiloseis_source_color_taxonomy();
	if ( '' === $tax ) {
		return;
	}
	add_action( $tax . '_add_form_fields', 'ekdiloseis_source_term_add_field' );
	add_action( $tax . '_edit_form_fields', 'ekdiloseis_source_term_edit_field' );
	add_action( 'edit_terms', 'ekdiloseis_source_term_before_edit', 10, 2 );
	add_action( 'created_' . $tax, 'ekdiloseis_source_term_save' );
	add_action( 'edited_' . $tax, 'ekdiloseis_source_term_save' );
	add_action( 'delete_' . $tax, 'ekdiloseis_source_term_deleted', 10, 3 );
	add_filter( 'manage_edit-' . $tax . '_columns', 'ekdiloseis_source_term_columns' );
	add_filter( 'manage_' . $tax . '_custom_column', 'ekdiloseis_source_term_column', 10, 3 );
}
add_action( 'init', 'ekdiloseis_source_term_hooks', 99 );
