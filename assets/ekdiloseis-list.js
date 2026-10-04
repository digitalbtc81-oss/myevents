/**
 * Day-list media shared by every calendar engine.
 * listMedia: image (featured photo), icon (Font Awesome), none.
 */
(function (window) {
	"use strict";

	function safeUrl(url) {
		if (typeof url !== "string" || !url) {
			return "";
		}
		if (/^https?:\/\//i.test(url) || url.charAt(0) === "/") {
			return url;
		}
		return "";
	}

	function safeIcon(value) {
		var text = String(value || "").trim().replace(/\s+/g, " ").toLowerCase();
		if (/^(fa-solid|fa-regular|fa-brands) fa-[a-z0-9-]+$/.test(text)) {
			return text;
		}
		return "";
	}

	function listMedia(data) {
		var mode = data && data.listMedia;
		if (mode === "icon" || mode === "none") {
			return mode;
		}
		return "image";
	}

	/**
	 * Featured image, category/event icon, or nothing. Icon mode never falls through to the photo.
	 *
	 * @param {HTMLElement} article
	 * @param {object} event
	 * @param {object} data Calendar payload.
	 */
	function appendMedia(article, event, data) {
		var mode = listMedia(data);
		if (mode === "none") {
			return;
		}
		if (mode === "icon") {
			var icon = safeIcon(event && event.icon) || "fa-solid fa-calendar";
			var mark = document.createElement("i");
			mark.className = "ekdiloseis__icon " + icon;
			mark.setAttribute("aria-hidden", "true");
			article.appendChild(mark);
			return;
		}
		var thumb = safeUrl(event && event.thumb);
		if (!thumb) {
			return;
		}
		var img = document.createElement("img");
		img.className = "ekdiloseis__thumb";
		img.src = thumb;
		img.alt = (event && event.title) || "";
		article.appendChild(img);
	}

	window.EkdiloseisList = {
		appendMedia: appendMedia
	};
})(window);
