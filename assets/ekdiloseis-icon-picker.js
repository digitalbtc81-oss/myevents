/**
 * Admin icon picker for category and event icon class fields.
 * Stored value stays a Font Awesome class (fa-solid fa-name) or empty.
 */
(function () {
	'use strict';

	var FIELDS = ['ekdiloseis_term_icon', 'ekdiloseis_event_icon'];

	/** Font Awesome 6 free classic solid names only. */
	var ICONS = [
		['calendar', 'Calendar'],
		['music', 'Music'],
		['masks-theater', 'Theater'],
		['landmark', 'Landmark'],
		['users', 'Users'],
		['people-group', 'Group'],
		['child', 'Child'],
		['baby', 'Baby'],
		['utensils', 'Food'],
		['mug-hot', 'Cafe'],
		['cake-candles', 'Cake'],
		['bus', 'Bus'],
		['train', 'Train'],
		['bicycle', 'Bicycle'],
		['futbol', 'Soccer'],
		['basketball', 'Basketball'],
		['person-running', 'Running'],
		['person-hiking', 'Hike'],
		['person-swimming', 'Swim'],
		['graduation-cap', 'Graduation'],
		['school', 'School'],
		['book', 'Book'],
		['book-open', 'Reading'],
		['microphone', 'Mic'],
		['guitar', 'Guitar'],
		['drum', 'Drum'],
		['film', 'Film'],
		['camera', 'Camera'],
		['palette', 'Art'],
		['paintbrush', 'Paint'],
		['ticket', 'Ticket'],
		['clock', 'Clock'],
		['champagne-glasses', 'Cheers'],
		['heart', 'Heart'],
		['star', 'Star'],
		['trophy', 'Trophy'],
		['puzzle-piece', 'Games'],
		['tree', 'Tree'],
		['leaf', 'Leaf'],
		['seedling', 'Garden'],
		['paw', 'Animals'],
		['sun', 'Sun'],
		['moon', 'Moon'],
		['umbrella-beach', 'Beach'],
		['campground', 'Camp'],
		['church', 'Church'],
		['place-of-worship', 'Worship'],
		['monument', 'Monument'],
		['building-columns', 'Museum'],
		['hospital', 'Hospital'],
		['kit-medical', 'Aid'],
		['wheelchair', 'Access'],
		['handshake', 'Handshake'],
		['briefcase', 'Work'],
		['comments', 'Comments'],
		['bell', 'Notice'],
		['flag', 'Flag'],
		['location-dot', 'Location']
	];

	function classFor(name) {
		return 'fa-solid fa-' + name;
	}

	function sync(input, root) {
		var value = (input.value || '').trim();
		var choices = root.querySelectorAll('.ekdiloseis-icon-picker__choice');
		var i;
		for (i = 0; i < choices.length; i++) {
			var choice = choices[i];
			var on = (choice.getAttribute('data-icon') || '') === value;
			choice.classList.toggle('is-selected', on);
			choice.setAttribute('aria-pressed', on ? 'true' : 'false');
		}
	}

	function choiceButton(iconClass, label) {
		var button = document.createElement('button');
		var glyph = document.createElement('span');
		var name = document.createElement('span');
		button.type = 'button';
		button.className = 'ekdiloseis-icon-picker__choice';
		button.setAttribute('data-icon', iconClass);
		button.setAttribute('aria-pressed', 'false');
		glyph.className = 'ekdiloseis-icon-picker__glyph';
		glyph.setAttribute('aria-hidden', 'true');
		if (iconClass) {
			var icon = document.createElement('i');
			icon.className = iconClass;
			glyph.appendChild(icon);
		} else {
			glyph.textContent = '—';
		}
		name.className = 'ekdiloseis-icon-picker__name';
		name.textContent = label;
		button.appendChild(glyph);
		button.appendChild(name);
		return button;
	}

	function attach(input) {
		var wrap;
		var toggle;
		var panel;
		var grid;
		var i;

		if (!input || input.getAttribute('data-ekdiloseis-picker') === '1') {
			return;
		}
		input.setAttribute('data-ekdiloseis-picker', '1');

		wrap = document.createElement('div');
		wrap.className = 'ekdiloseis-icon-picker';

		toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'button ekdiloseis-icon-picker__toggle';
		toggle.textContent = 'Choose icon';
		toggle.setAttribute('aria-expanded', 'false');

		panel = document.createElement('div');
		panel.className = 'ekdiloseis-icon-picker__panel';
		panel.hidden = true;

		grid = document.createElement('div');
		grid.className = 'ekdiloseis-icon-picker__grid';
		grid.setAttribute('role', 'group');
		grid.setAttribute('aria-label', 'Icons');
		grid.appendChild(choiceButton('', 'None'));
		for (i = 0; i < ICONS.length; i++) {
			grid.appendChild(choiceButton(classFor(ICONS[i][0]), ICONS[i][1]));
		}
		panel.appendChild(grid);
		wrap.appendChild(toggle);
		wrap.appendChild(panel);
		input.insertAdjacentElement('afterend', wrap);

		toggle.addEventListener('click', function () {
			var open = panel.hidden;
			panel.hidden = !open;
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				sync(input, wrap);
			}
		});

		grid.addEventListener('click', function (event) {
			var choice = event.target.closest('.ekdiloseis-icon-picker__choice');
			if (!choice || !grid.contains(choice)) {
				return;
			}
			input.value = choice.getAttribute('data-icon') || '';
			sync(input, wrap);
		});

		input.addEventListener('input', function () {
			sync(input, wrap);
		});
		input.addEventListener('change', function () {
			sync(input, wrap);
		});

		sync(input, wrap);
	}

	function init() {
		var i;
		for (i = 0; i < FIELDS.length; i++) {
			attach(document.getElementById(FIELDS[i]));
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
