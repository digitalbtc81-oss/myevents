(function () {
	'use strict';

	var NEUTRAL = '#6b7280';

	function el(tag, attrs, text) {
		var node = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (key) {
			node.setAttribute(key, attrs[key]);
		});
		if (text !== undefined) {
			node.textContent = text;
		}
		return node;
	}

	function init(root) {
		var typeSelect = root.querySelector('#ekdiloseis-source-post_type');
		var map = root.querySelector('.ekdiloseis-source__map');
		var taxSelect = root.querySelector('.ekdiloseis-source__taxonomy');
		var colorsBox = root.querySelector('.ekdiloseis-source__colors');
		var colorsRow = root.querySelector('.ekdiloseis-source__colors-row');
		var request = 0;

		function categoryType() {
			var checked = root.querySelector('input[name="ekdiloseis_source[category_type]"]:checked');
			return checked ? checked.value : 'none';
		}

		function pickerValue(picker) {
			var select = picker.querySelector('select');
			if (select.value === '__custom') {
				return picker.querySelector('.ekdiloseis-keypick__custom').value.trim();
			}
			return select.value;
		}

		function categoryKey() {
			var type = categoryType();
			if (type === 'taxonomy') {
				return taxSelect.value;
			}
			if (type === 'meta') {
				return pickerValue(root.querySelector('.ekdiloseis-keypick[data-field="category_meta"]'));
			}
			return '';
		}

		function syncPicker(picker) {
			var select = picker.querySelector('select');
			var custom = picker.querySelector('.ekdiloseis-keypick__custom');
			custom.hidden = select.value !== '__custom';
		}

		function syncCategory() {
			var type = categoryType();
			root.querySelector('.ekdiloseis-source__cat-tax').hidden = type !== 'taxonomy';
			root.querySelector('.ekdiloseis-source__cat-meta').hidden = type !== 'meta';
			colorsRow.hidden = type === 'none';
		}

		function currentColors() {
			var out = {};
			colorsBox.querySelectorAll('.ekdiloseis-source__color').forEach(function (row) {
				out[row.querySelector('input[type="hidden"]').value] = row.querySelector('input[type="color"]').value;
			});
			return out;
		}

		function renderColors(values) {
			var saved = currentColors();
			colorsBox.textContent = '';
			values.forEach(function (value, i) {
				var row = el('div', { 'class': 'ekdiloseis-source__color' });
				row.appendChild(el('input', { type: 'hidden', name: 'ekdiloseis_source[colors][' + i + '][value]', value: value }));
				row.appendChild(el('input', { type: 'color', name: 'ekdiloseis_source[colors][' + i + '][color]', value: saved[value] || NEUTRAL, 'aria-label': 'Color for ' + value }));
				row.appendChild(el('span', {}, value));
				colorsBox.appendChild(row);
			});
			if (!values.length) {
				colorsBox.appendChild(el('p', { 'class': 'description' }, 'No values found yet for this category source.'));
			}
		}

		function renderKeys(keys) {
			root.querySelectorAll('.ekdiloseis-keypick').forEach(function (picker) {
				var select = picker.querySelector('select');
				var custom = picker.querySelector('.ekdiloseis-keypick__custom');
				var current = pickerValue(picker);
				select.textContent = '';
				select.appendChild(el('option', { value: '' }, '\u2014 None \u2014'));
				keys.forEach(function (key) {
					select.appendChild(el('option', { value: key }, key));
				});
				select.appendChild(el('option', { value: '__custom' }, 'Custom key\u2026'));
				if (current && keys.indexOf(current) === -1) {
					select.value = '__custom';
					custom.value = current;
				} else {
					select.value = current;
				}
				syncPicker(picker);
			});
		}

		function renderTaxonomies(taxes) {
			var current = taxSelect.value;
			taxSelect.textContent = '';
			taxSelect.appendChild(el('option', { value: '' }, '\u2014 Select \u2014'));
			Object.keys(taxes).forEach(function (name) {
				taxSelect.appendChild(el('option', { value: name }, taxes[name]));
			});
			taxSelect.value = taxes[current] ? current : '';
		}

		function load(full) {
			var type = typeSelect.value;
			map.hidden = type === 'ekdilosi';
			if (type === 'ekdilosi') {
				return;
			}
			var id = ++request;
			var body = new URLSearchParams();
			body.set('action', 'ekdiloseis_source_fields');
			body.set('nonce', root.getAttribute('data-nonce'));
			body.set('post_type', type);
			body.set('category_type', categoryType());
			body.set('category_key', categoryKey());
			root.classList.add('is-loading');
			fetch(root.getAttribute('data-ajax-url'), { method: 'POST', credentials: 'same-origin', body: body })
				.then(function (response) { return response.json(); })
				.then(function (json) {
					if (id !== request || !json || !json.success) {
						return;
					}
					if (full) {
						renderKeys(json.data.metaKeys || []);
						renderTaxonomies(json.data.taxonomies || {});
					}
					renderColors(json.data.values || []);
				})
				.catch(function () {})
				.then(function () {
					if (id === request) {
						root.classList.remove('is-loading');
					}
				});
		}

		typeSelect.addEventListener('change', function () { load(true); });
		root.addEventListener('change', function (event) {
			var target = event.target;
			var picker = target.closest('.ekdiloseis-keypick');
			if (picker && target.tagName === 'SELECT') {
				syncPicker(picker);
			}
			if (target.name === 'ekdiloseis_source[category_type]') {
				syncCategory();
				load(false);
			} else if (target === taxSelect || (picker && picker.getAttribute('data-field') === 'category_meta')) {
				load(false);
			}
		});
		syncCategory();
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.ekdiloseis-source').forEach(init);
	});
})();
