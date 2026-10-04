<?php
/**
 * Event post type.
 *
 * @package Ekdiloseis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the ekdilosi post type.
 */
function ekdiloseis_register_post_type() {
	$labels = array(
		'name'               => 'Events',
		'singular_name'      => 'Event',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Event',
		'edit_item'          => 'Edit Event',
		'new_item'           => 'New Event',
		'view_item'          => 'View Event',
		'search_items'       => 'Search Events',
		'not_found'          => 'No events found',
		'not_found_in_trash' => 'No events found in Trash',
		'menu_name'          => 'Events',
		'all_items'          => 'All Events',
	);

	register_post_type(
		'ekdilosi',
		array(
			'labels'              => $labels,
			'public'              => true,
			'has_archive'         => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-calendar-alt',
			'menu_position'       => 6,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'rewrite'             => array( 'slug' => 'ekdilosi' ),
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'exclude_from_search' => false,
		)
	);
}
add_action( 'init', 'ekdiloseis_register_post_type' );

/**
 * Featured images for events even if a theme forgets theme support.
 */
function ekdiloseis_theme_supports() {
	add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'ekdiloseis_theme_supports' );

/**
 * Start-date column on the events list.
 *
 * @param array $columns Columns.
 * @return array
 */
function ekdiloseis_event_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['event_start'] = 'Start';
		}
	}
	if ( ! isset( $new['event_start'] ) ) {
		$new['event_start'] = 'Start';
	}
	return $new;
}
add_filter( 'manage_ekdilosi_posts_columns', 'ekdiloseis_event_columns' );

/**
 * Render the start-date column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function ekdiloseis_event_column_content( $column, $post_id ) {
	if ( 'event_start' !== $column ) {
		return;
	}
	$start = get_post_meta( $post_id, 'event_start', true );
	if ( ! is_string( $start ) || ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $start ) ) {
		echo '—';
		return;
	}
	$dt = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $start );
	if ( ! $dt instanceof DateTimeImmutable ) {
		echo '—';
		return;
	}
	echo esc_html( $dt->format( 'd/m/Y H:i' ) );
}
add_action( 'manage_ekdilosi_posts_custom_column', 'ekdiloseis_event_column_content', 10, 2 );

/**
 * Make the start column sortable.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function ekdiloseis_sortable_columns( $columns ) {
	$columns['event_start'] = 'event_start';
	return $columns;
}
add_filter( 'manage_edit-ekdilosi_sortable_columns', 'ekdiloseis_sortable_columns' );

/**
 * Sort the admin list by event_start.
 *
 * @param WP_Query $query Query.
 */
function ekdiloseis_sort_by_start( $query ) {
	if ( ! is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
		return;
	}
	if ( 'ekdilosi' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( 'event_start' !== $query->get( 'orderby' ) ) {
		return;
	}
	$query->set( 'meta_key', 'event_start' );
	$query->set( 'orderby', 'meta_value' );
}
add_action( 'pre_get_posts', 'ekdiloseis_sort_by_start' );
