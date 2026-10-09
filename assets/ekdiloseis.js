(function () {
	"use strict";

	function nextDay(iso) {
		var parts = iso.split("-");
		var dt = new Date(Date.UTC(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2])));
		dt.setUTCDate(dt.getUTCDate() + 1);
		var month = String(dt.getUTCMonth() + 1);
		var day = String(dt.getUTCDate());
		if (month.length < 2) {
			month = "0" + month;
		}
		if (day.length < 2) {
			day = "0" + day;
		}
		return dt.getUTCFullYear() + "-" + month + "-" + day;
	}

	function overlaps(event, day) {
		var dayStart = day + " 00:00:00";
		var next = nextDay(day) + " 00:00:00";
		return event.start < next && event.end > dayStart;
	}


	function todayIso(data) {
		var day = data && typeof data.todayDate === "string" ? data.todayDate : "";
		if (/^\d{4}-\d{2}-\d{2}$/.test(day)) {
			return day;
		}
		try {
			var parts = new Intl.DateTimeFormat("en-US", {
				timeZone: "Europe/Athens",
				year: "numeric",
				month: "2-digit",
				day: "2-digit"
			}).formatToParts(new Date());
			var year = "";
			var month = "";
			var date = "";
			for (var i = 0; i < parts.length; i++) {
				if (parts[i].type === "year") {
					year = parts[i].value;
				} else if (parts[i].type === "month") {
					month = parts[i].value;
				} else if (parts[i].type === "day") {
					date = parts[i].value;
				}
			}
			if (/^\d{4}$/.test(year) && /^\d{2}$/.test(month) && /^\d{2}$/.test(date)) {
				return year + "-" + month + "-" + date;
			}
		} catch (error) {
			return "";
		}
		return "";
	}

	function dayHasEvents(data, day) {
		if (!day) {
			return false;
		}
		return (data.events || []).some(function (event) {
			return overlaps(event, day);
		});
	}


	function clock(value) {
		return String(value).slice(11, 16);
	}

	function timeLabel(event) {
		if (event.time) {
			return event.time;
		}
		return clock(event.start) + "\u2013" + clock(event.end);
	}

	function eventColor(event) {
		var color = String((event && event.color) || "").toLowerCase();
		return /^#[0-9a-f]{6}$/.test(color) ? color : "#6b7280";
	}

	function eventTextColor(event) {
		var color = String((event && event.textColor) || "").toLowerCase();
		return /^#[0-9a-f]{6}$/.test(color) ? color : "#ffffff";
	}

	var SVG_NS = "http://www.w3.org/2000/svg";
	var ICONS = {
		clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		pin: '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		calendar: '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		chevron: '<path d="M9 18l6-6-6-6"/>'
	};

	/* Static, trusted path data only. Event text never reaches innerHTML. */
	function svgIcon(name) {
		var svg = document.createElementNS(SVG_NS, "svg");
		svg.setAttribute("class", "ekdiloseis__svg ekdiloseis__svg--" + name);
		svg.setAttribute("viewBox", "0 0 24 24");
		svg.setAttribute("width", "24");
		svg.setAttribute("height", "24");
		svg.setAttribute("fill", "none");
		svg.setAttribute("stroke", "currentColor");
		svg.setAttribute("stroke-width", "2");
		svg.setAttribute("stroke-linecap", "round");
		svg.setAttribute("stroke-linejoin", "round");
		svg.setAttribute("aria-hidden", "true");
		svg.setAttribute("focusable", "false");
		svg.innerHTML = ICONS[name] || "";
		return svg;
	}

	function tint(hex, alpha) {
		var r = parseInt(hex.slice(1, 3), 16);
		var g = parseInt(hex.slice(3, 5), 16);
		var b = parseInt(hex.slice(5, 7), 16);
		return "rgba(" + r + ", " + g + ", " + b + ", " + alpha + ")";
	}

	function safeIcon(value) {
		var text = String(value || "").trim().replace(/\s+/g, " ").toLowerCase();
		return /^(fa-solid|fa-regular|fa-brands) fa-[a-z0-9-]+$/.test(text) ? text : "";
	}

	function listMedia(data) {
		var mode = data && data.listMedia;
		return mode === "icon" || mode === "none" ? mode : "image";
	}

	function safeEventUrl(url) {
		if (typeof url !== "string" || !url) {
			return "";
		}
		if (/^https?:\/\//i.test(url) || url.charAt(0) === "/") {
			return url;
		}
		return "";
	}

	function fullDate(data, day) {
		var parts = day.split("-");
		var year = Number(parts[0]);
		var month = Number(parts[1]);
		var date = Number(parts[2]);
		var weekday = new Date(Date.UTC(year, month - 1, date)).getUTCDay();
		var names = data.weekdaysFull || [];
		var months = data.monthsGenitive || [];
		var dayName = names[(weekday + 6) % 7] || "";
		var monthName = months[month - 1] || String(month);
		return (dayName ? dayName + ", " : "") + date + " " + monthName + " " + year;
	}

	function countLabel(data, count) {
		if (count === 1) {
			return data.countOne || "1 scheduled event";
		}
		return String(data.countMany || "%d scheduled events").replace("%d", String(count));
	}

	/* Featured image, or a tinted box with the Font Awesome icon (icon mode) or a calendar icon. */
	function appendMedia(article, event, data, url) {
		var mode = listMedia(data);
		if (mode === "none") {
			return;
		}
		var color = eventColor(event);
		var media = document.createElement(url ? "a" : "div");
		media.className = "ekdiloseis__media";
		if (url) {
			media.href = url;
			media.tabIndex = -1;
			media.setAttribute("aria-hidden", "true");
		}
		var thumb = mode === "image" ? safeEventUrl(event && event.thumb) : "";
		if (thumb) {
			var img = document.createElement("img");
			img.src = thumb;
			img.alt = "";
			img.loading = "lazy";
			media.appendChild(img);
		} else {
			media.classList.add("ekdiloseis__media--icon");
			media.style.backgroundColor = tint(color, 0.14);
			media.style.color = color;
			var fa = mode === "icon" ? safeIcon(event && event.icon) : "";
			if (fa) {
				var mark = document.createElement("i");
				mark.className = "ekdiloseis__fa " + fa;
				mark.setAttribute("aria-hidden", "true");
				media.appendChild(mark);
			} else {
				media.appendChild(svgIcon("calendar"));
			}
		}
		article.appendChild(media);
	}

	function metaLine(className, icon, text) {
		var line = document.createElement("p");
		line.className = "ekdiloseis__meta " + className;
		line.appendChild(svgIcon(icon));
		var span = document.createElement("span");
		span.textContent = text;
		line.appendChild(span);
		return line;
	}

	function buildCard(event, data) {
		var color = eventColor(event);
		var url = safeEventUrl(event && event.url);
		var article = document.createElement("article");
		article.className = "ekdiloseis__card";
		article.style.setProperty("--ekd-mark", color);

		appendMedia(article, event, data, url);

		var body = document.createElement("div");
		body.className = "ekdiloseis__card-body";

		if (event.category) {
			var badge = document.createElement("span");
			badge.className = "ekdiloseis__badge";
			badge.textContent = event.category;
			badge.style.backgroundColor = tint(color, 0.14);
			/* Dark category colors read well as text; light ones fall back to dark text. */
			badge.style.color = eventTextColor(event) === "#ffffff" ? color : "#111827";
			body.appendChild(badge);
		}

		var title = document.createElement("h3");
		title.className = "ekdiloseis__event-title";
		if (url) {
			var link = document.createElement("a");
			link.className = "ekdiloseis__event-link";
			link.href = url;
			link.textContent = event.title || "";
			title.appendChild(link);
		} else {
			title.textContent = event.title || "";
		}
		body.appendChild(title);

		body.appendChild(metaLine("ekdiloseis__time", "clock", clock(event.start) + " \u2013 " + clock(event.end)));
		if (event.location) {
			body.appendChild(metaLine("ekdiloseis__location", "pin", String(event.location)));
		}
		if (event.excerpt) {
			var excerpt = document.createElement("p");
			excerpt.className = "ekdiloseis__excerpt";
			excerpt.textContent = String(event.excerpt);
			body.appendChild(excerpt);
		}
		article.appendChild(body);

		if (url) {
			var chev = document.createElement("a");
			chev.className = "ekdiloseis__chev";
			chev.href = url;
			chev.setAttribute("aria-label", (data.openEvent || "Open event") + ": " + (event.title || ""));
			chev.appendChild(svgIcon("chevron"));
			article.appendChild(chev);
		}
		return article;
	}

	function render(root, data, day, button) {
		var buttons = root.querySelectorAll(".ekdiloseis__day");
		var list = root.querySelector(".ekdiloseis__list");
		if (!list) {
			return;
		}
		Array.prototype.forEach.call(buttons, function (item) {
			if (item.tagName === "BUTTON") {
				item.setAttribute("aria-pressed", "false");
			}
			item.classList.remove("is-selected");
		});
		if (button) {
			button.setAttribute("aria-pressed", "true");
			button.classList.add("is-selected");
		}

		var events = (data.events || []).filter(function (event) {
			return overlaps(event, day);
		});
		events.sort(function (a, b) {
			if (a.start === b.start) {
				return a.id - b.id;
			}
			return a.start < b.start ? -1 : 1;
		});

		while (list.firstChild) {
			list.removeChild(list.firstChild);
		}

		var head = document.createElement("div");
		head.className = "ekdiloseis__dayhead";
		var heading = document.createElement("h3");
		heading.className = "ekdiloseis__daytitle";
		heading.textContent = fullDate(data, day);
		head.appendChild(heading);
		var count = document.createElement("p");
		count.className = "ekdiloseis__count";
		count.textContent = events.length ? countLabel(data, events.length) : (data.emptyMessage || "No events on this day");
		head.appendChild(count);
		list.appendChild(head);

		if (!events.length) {
			return;
		}

		var cards = document.createElement("div");
		cards.className = "ekdiloseis__cards";
		events.forEach(function (event) {
			cards.appendChild(buildCard(event, data));
		});
		list.appendChild(cards);
	}

	var MONTH_RE = /^\d{4}-(0[1-9]|1[0-2])$/;

	function parseData(root) {
		var dataEl = root.querySelector(".ekdiloseis__data");
		if (!dataEl) {
			return null;
		}
		try {
			return JSON.parse(dataEl.textContent || "");
		} catch (error) {
			return null;
		}
	}

	function showHint(root, data) {
		var list = root.querySelector(".ekdiloseis__list");
		if (!list) {
			return;
		}
		while (list.firstChild) {
			list.removeChild(list.firstChild);
		}
		var head = document.createElement("div");
		head.className = "ekdiloseis__dayhead";
		var hint = document.createElement("p");
		hint.className = "ekdiloseis__hint";
		hint.textContent = data.hint || "Select a day to view events.";
		head.appendChild(hint);
		list.appendChild(head);
	}

	function todayButtonOf(root, data) {
		var today = todayIso(data);
		return today ? root.querySelector('button.ekdiloseis__day[data-date="' + today + '"]') : null;
	}

	/* Day panel for a freshly shown month: today in the current month, otherwise the hint. */
	function resetPanel(root, state) {
		var todayButton = todayButtonOf(root, state.data);
		if (todayButton) {
			render(root, state.data, todayIso(state.data), todayButton);
		} else {
			showHint(root, state.data);
		}
	}

	function monthFromUrl(href) {
		try {
			var value = new URL(href, window.location.href).searchParams.get("ekd_month");
			return value && MONTH_RE.test(value) ? value : "";
		} catch (error) {
			return "";
		}
	}

	function currentMonth(state) {
		return todayIso(state.data).slice(0, 7);
	}

	/* Fetch one month from the public endpoint and swap only the month card. */
	function loadMonth(root, state, month, options) {
		options = options || {};
		var ajaxUrl = root.getAttribute("data-ajax-url");
		var main = root.querySelector(".ekdiloseis__main");
		var list = root.querySelector(".ekdiloseis__list");
		if (!ajaxUrl || !main || !MONTH_RE.test(month) || !window.fetch) {
			return Promise.reject(new Error("unavailable"));
		}
		var url = new URL(ajaxUrl, window.location.href);
		url.searchParams.set("action", "ekdiloseis_month");
		url.searchParams.set("month", month);
		url.searchParams.set("list_id", list && list.id ? list.id : "");
		url.searchParams.set("page_url", root.getAttribute("data-page-url") || window.location.href);

		var token = ++state.token;
		root.classList.add("is-loading");
		main.setAttribute("aria-busy", "true");

		return fetch(url.toString(), { credentials: "same-origin", headers: { Accept: "application/json" } })
			.then(function (response) {
				if (!response.ok) {
					throw new Error("HTTP " + response.status);
				}
				return response.json();
			})
			.then(function (json) {
				if (token !== state.token) {
					return;
				}
				if (!json || !json.success || !json.data || typeof json.data.html !== "string" || !json.data.data) {
					throw new Error("bad response");
				}
				main.innerHTML = json.data.html; /* Server-rendered, escaped markup from our own endpoint. */
				state.data = json.data.data;
				root.setAttribute("data-month", json.data.month || month);
				var dataEl = root.querySelector(".ekdiloseis__data");
				if (dataEl) {
					dataEl.textContent = JSON.stringify(state.data);
				}
				resetPanel(root, state);
				if (options.focus) {
					var target = root.querySelector(options.focus);
					if (target) {
						target.focus({ preventScroll: true });
					}
				}
			})
			.finally(function () {
				if (token === state.token) {
					root.classList.remove("is-loading");
					main.removeAttribute("aria-busy");
				}
			});
	}

	function pushMonth(month) {
		if (!window.history || !window.history.pushState) {
			return;
		}
		var url = new URL(window.location.href);
		if (url.searchParams.get("ekd_month") === month) {
			return;
		}
		url.searchParams.set("ekd_month", month);
		window.history.pushState({ ekdMonth: month }, "", url.toString());
	}

	var instances = [];

	function boot(root) {
		var data = parseData(root);
		if (!data) {
			return;
		}
		var state = { data: data, token: 0 };
		instances.push({ root: root, state: state });

		/* Delegated: survives the month card being replaced. Scoped to this root only. */
		root.addEventListener("click", function (event) {
			var target = event.target instanceof Element ? event.target : null;
			if (!target) {
				return;
			}
			var dayButton = target.closest("button.ekdiloseis__day");
			if (dayButton && root.contains(dayButton)) {
				var day = dayButton.getAttribute("data-date");
				if (day) {
					render(root, state.data, day, dayButton);
				}
				return;
			}

			var link = target.closest("a.ekdiloseis__navlink, a.ekdiloseis__today");
			if (!link || !root.contains(link)) {
				return;
			}
			if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
				return; /* New tab / window keeps the plain link. */
			}
			var isToday = link.classList.contains("ekdiloseis__today");
			var shown = root.getAttribute("data-month") || state.data.month || "";
			var month = link.getAttribute("data-month") || monthFromUrl(link.href);
			if (isToday) {
				month = currentMonth(state) || month;
				if (month === shown) {
					var todayButton = todayButtonOf(root, state.data);
					if (todayButton) {
						event.preventDefault();
						render(root, state.data, todayIso(state.data), todayButton);
						todayButton.focus();
					}
					return;
				}
			}
			if (!MONTH_RE.test(month)) {
				return;
			}
			event.preventDefault();
			var focus = isToday ? "button.ekdiloseis__day.is-selected" : 'a.ekdiloseis__navlink[rel="' + link.getAttribute("rel") + '"]';
			var href = link.href;
			loadMonth(root, state, month, { focus: focus }).then(function () {
				pushMonth(month);
			}, function () {
				window.location.href = href; /* Fall back to the normal page load. */
			});
		});

		resetPanel(root, state);
	}

	function onPopState() {
		var month = monthFromUrl(window.location.href);
		instances.forEach(function (item) {
			var target = month || currentMonth(item.state);
			if (!target || target === item.root.getAttribute("data-month")) {
				return;
			}
			loadMonth(item.root, item.state, target).catch(function () {
				window.location.reload();
			});
		});
	}

	function init() {
		var roots = document.querySelectorAll(".ekdiloseis");
		Array.prototype.forEach.call(roots, boot);
		if (instances.length) {
			window.addEventListener("popstate", onPopState);
		}
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
