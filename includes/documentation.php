<?php
/**
 * English documentation screen. Read-only; it does not register options.
 *
 * @package Ekdiloseis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submenu under Events, after Settings (that page is registered at priority 20).
 */
function ekdiloseis_add_docs_page() {
	add_submenu_page(
		'edit.php?post_type=ekdilosi',
		'Documentation',
		'Documentation',
		'manage_options',
		'ekdiloseis-documentation',
		'ekdiloseis_render_docs_page'
	);
}
add_action( 'admin_menu', 'ekdiloseis_add_docs_page', 30 );

/**
 * One heading and the paragraphs under it.
 *
 * @param string   $heading    Section title.
 * @param string[] $paragraphs Plain sentences. No HTML.
 */
function ekdiloseis_docs_section( $heading, $paragraphs ) {
	echo '<h2>' . esc_html( $heading ) . '</h2>';
	foreach ( $paragraphs as $paragraph ) {
		echo '<p>' . esc_html( $paragraph ) . '</p>';
	}
}

/**
 * Documentation screen.
 */
function ekdiloseis_render_docs_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'You do not have permission to access this page.' ), 403 );
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( 'Documentation' ); ?></h1>
		<?php
		ekdiloseis_docs_section(
			'What the plugin does',
			array(
				'Events adds a public post type, Event, and a month calendar with a day list. Put the shortcode [myevents] on a page or post. That is the only shortcode, and it takes no attributes.',
				'Clicking a day lists published events whose start and end overlap that calendar day. An event with no valid range is omitted. The built-in calendar loads events for the month on screen. The other engines load every published event so the month can change in the browser.',
			)
		);
		ekdiloseis_docs_section(
			'How to add an event',
			array(
				'Open Events, then Add New. Set the title, the content, and the featured image.',
				'In Schedule, enter Start and End as a date plus a time. The date is mm/dd/yyyy (mm/dd/yy is also accepted). The time is 24-hour HH:MM. Seconds are optional; if you omit them they are stored as 00.',
				'The values are stored as Y-m-d H:i:s, using the site timezone (Europe/Athens) as wall-clock time. End must be after start. If the date or time is invalid, or end is not after start, the range is not saved and the previous start and end stay as they were. Color and icon on the same screen are saved on their own.',
				'The events list has a Start column (day/month/year and HH:MM) that can be sorted.',
			)
		);
		ekdiloseis_docs_section(
			'Categories and colors',
			array(
				'Event Categories is a non-hierarchical taxonomy. Each category has a color. A category with no stored color uses #1b7f4a.',
				'An event can override that color. Check Custom color and pick a color. If Custom color is off, the category color is used and any saved override is removed.',
				'An event with no category uses gray #6b7280. If the event is in several categories and has no custom color, one color is used: the category with the lowest term id.',
				'There is no separate event-day color. Calendar appearance does not set the event color.',
			)
		);
		ekdiloseis_docs_section(
			'Icon',
			array(
				'List media, under Event settings, chooses what the day list shows beside each event: Featured image, Icon, or None. Featured image is the default. Icon does not fall back to the photo. None shows neither.',
				'On an event category, Icon is a Font Awesome class: one of fa-solid, fa-regular, or fa-brands, then one fa- name, for example fa-solid fa-music. The same field on the event is optional and, when List media is Icon, replaces the category icon. Leave it empty to use the category icon.',
				'With several categories, the icon comes from the category with the lowest term id, unless the event sets its own. If that category has no icon, and the event has none, Icon mode uses fa-solid fa-calendar.',
			)
		);
		ekdiloseis_docs_section(
			'Settings',
			array(
				'Event settings is under Events and requires an administrator. It does not change existing pages until you save.',
				'Calendar engine is Default, FullCalendar, Toast UI Calendar, Event Calendar, or Schedule-X. Default is the built-in calendar. Anything else is treated as Default.',
				'Language is English or Greek. It changes month and weekday names on the public calendar, and the short public labels (previous, next, today, and the empty-day text). Admin screens stay in English. A missing or unknown value stays English.',
				'Layout is Below the calendar or Two columns. Below the calendar keeps the day list under the month. Two columns places the calendar on the left and the day list on the right. Narrower than 800px, Two columns stacks the list under the calendar.',
				'Each engine has its own appearance: Font (System, Arial, Georgia, Courier, or Times), Google Font, Size (10 to 32 px; the form default is 14), Text color (form default #1c1c1c), and Background (form default #ffffff). Saving one engine does not copy those values onto the others. Until an engine has a saved style, the public calendar keeps its built-in look.',
				'Custom CSS is the last field in the Calendar section, under Appearance. It is printed on the public page in a style tag after the plugin stylesheet, and only when that page contains [myevents]. It is not applied in admin. A closing style tag, javascript, expression(, @import, and the behavior property are removed. Other CSS, including scroll-behavior, is kept.',
				'Import and export sits under the Save button. Export downloads events-settings.json with the plugin settings only: calendar engine, language, layout, list media, appearance, and custom CSS. It does not include events, categories, or secrets. Replace settings uploads that file and overwrites those options only after the file is valid. Unknown settings are rejected and nothing is changed. Events are not imported.',
			)
		);
		ekdiloseis_docs_section(
			'Today and opening an event',
			array(
				'Today follows the site timezone. If that date has events, the day list opens for today when the calendar loads. No click is required. If today has no events, the list stays on its hint until a day is clicked.',
				'In the day list, the event title is a link to the event. On FullCalendar, Toast UI Calendar, Event Calendar, and Schedule-X, the month shows an event chip; clicking that chip opens the event. Clicking the day itself still filters the list to events that overlap that day. The built-in calendar marks days with color dots and has no event chip; a day click filters the list. If an event has no public URL, a chip click filters the list instead of leaving the page.',
			)
		);
		ekdiloseis_docs_section(
			'Google Font',
			array(
				'The Google Font field accepts letters, numbers, spaces, and hyphens only, up to 80 characters. A family name such as Roboto or Open Sans is valid. Any other character clears the field. Leave it empty to use Font.',
				'When it is set, that family replaces Font for that engine only. The stylesheet is requested only for the engine selected in Calendar engine, and only when [myevents] is shown.',
			)
		);
		?>
	</div>
	<?php
}
