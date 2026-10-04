(function () {
	'use strict';

	function activate(root, engine) {
		var tabs = root.querySelectorAll('.ekdiloseis-style-tab');
		var panels = root.querySelectorAll('.ekdiloseis-style-panel');
		var i;

		for (i = 0; i < tabs.length; i++) {
			var tab = tabs[i];
			var on = tab.getAttribute('data-engine') === engine;
			tab.classList.toggle('is-active', on);
			tab.setAttribute('aria-selected', on ? 'true' : 'false');
			tab.tabIndex = on ? 0 : -1;
		}

		for (i = 0; i < panels.length; i++) {
			var panel = panels[i];
			panel.classList.toggle('is-active', panel.getAttribute('data-engine') === engine);
		}
	}

	function init(root) {
		var tabs = root.querySelectorAll('.ekdiloseis-style-tab');
		if (!tabs.length) {
			return;
		}

		root.addEventListener('click', function (event) {
			var tab = event.target.closest('.ekdiloseis-style-tab');
			if (!tab || !root.contains(tab)) {
				return;
			}
			event.preventDefault();
			activate(root, tab.getAttribute('data-engine'));
		});

		root.addEventListener('keydown', function (event) {
			var tab = event.target.closest('.ekdiloseis-style-tab');
			if (!tab || !root.contains(tab)) {
				return;
			}
			var list = Array.prototype.slice.call(tabs);
			var index = list.indexOf(tab);
			if (index < 0) {
				return;
			}
			var next = -1;
			if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
				next = (index + 1) % list.length;
			} else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
				next = (index - 1 + list.length) % list.length;
			} else if (event.key === 'Home') {
				next = 0;
			} else if (event.key === 'End') {
				next = list.length - 1;
			} else {
				return;
			}
			event.preventDefault();
			list[next].focus();
			activate(root, list[next].getAttribute('data-engine'));
		});

		var active = root.querySelector('.ekdiloseis-style-tab.is-active');
		activate(root, active ? active.getAttribute('data-engine') : tabs[0].getAttribute('data-engine'));
	}

	document.addEventListener('DOMContentLoaded', function () {
		var roots = document.querySelectorAll('.ekdiloseis-style-tabs');
		for (var i = 0; i < roots.length; i++) {
			init(roots[i]);
		}
	});
})();
