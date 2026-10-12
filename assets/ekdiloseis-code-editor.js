(function ($) {
	'use strict';
	$(function () {
		var ta = document.getElementById('ekdiloseis_custom_css');
		if (!ta || !window.wp || !wp.codeEditor || !window.ekdiloseisCodeEditor) { return; }
		var settings = $.extend(true, {}, ekdiloseisCodeEditor.settings);
		settings.codemirror = $.extend({}, settings.codemirror, {
			lint: true,
			gutters: ['CodeMirror-lint-markers'],
			lineNumbers: true,
			autoCloseBrackets: true
		});
		var editor = wp.codeEditor.initialize(ta, settings);
		var cm = editor.codemirror;
		cm.getWrapperElement().classList.add('ekd-cm-dark');
		cm.on('change', function () { cm.save(); });
		var form = ta.form || $(ta).closest('form')[0];
		if (form) { form.addEventListener('submit', function () { cm.save(); }, true); }
		var refresh = function () { setTimeout(function () { cm.refresh(); }, 0); };
		$(document).on('click keyup', '[role="tab"], .nav-tab, .ekdiloseis-style-tab', refresh);
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (e) { if (e[0].isIntersecting) { cm.refresh(); } }).observe(cm.getWrapperElement());
		}
		refresh();
	});
})(jQuery);
