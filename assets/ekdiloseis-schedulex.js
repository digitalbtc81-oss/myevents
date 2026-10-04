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

	function paintListItem(article, event) {
		article.style.setProperty("--ekd-mark", eventColor(event));
	}

	function dayKey(value) {
		if (!value) {
			return "";
		}
		if (typeof value === "string") {
			var match = value.match(/^(\d{4}-\d{2}-\d{2})/);
			return match ? match[1] : "";
		}
		if (typeof value.toString === "function") {
			var text = String(value.toString());
			var fromText = text.match(/^(\d{4}-\d{2}-\d{2})/);
			if (fromText) {
				return fromText[1];
			}
		}
		return "";
	}

	function toZoned(value) {
		var match = String(value || "").match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
		if (!match || typeof Temporal === "undefined" || !Temporal.ZonedDateTime) {
			return null;
		}
		return Temporal.ZonedDateTime.from({
			year: Number(match[1]),
			month: Number(match[2]),
			day: Number(match[3]),
			hour: Number(match[4]),
			minute: Number(match[5]),
			second: Number(match[6] || 0),
			timeZone: "Europe/Athens"
		});
	}

	function dayCells(calEl) {
		return calEl.querySelectorAll(".sx__month-grid-day[data-date]");
	}

	function markHas(calEl, events) {
		var cells = dayCells(calEl);
		Array.prototype.forEach.call(cells, function (cell) {
			var day = cell.getAttribute("data-date") || "";
			var has = false;
			if (day) {
				has = (events || []).some(function (event) {
					return overlaps(event, day);
				});
			}
			cell.classList.toggle("ekdiloseis-sx__has", has);
		});
	}

	function markSelected(calEl, day) {
		var cells = dayCells(calEl);
		Array.prototype.forEach.call(cells, function (cell) {
			if (cell.getAttribute("data-date") === day) {
				cell.classList.add("is-selected");
			} else {
				cell.classList.remove("is-selected");
			}
		});
	}

	function dayUnderPointer(calEl, clientX, clientY) {
		var cells = dayCells(calEl);
		for (var i = 0; i < cells.length; i++) {
			var rect = cells[i].getBoundingClientRect();
			if (clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom) {
				return cells[i].getAttribute("data-date") || "";
			}
		}
		return "";
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

	function appendEventTitle(body, event) {
		var title = document.createElement("h3");
		title.className = "ekdiloseis__event-title";
		var label = (event && event.title) || "";
		var url = safeEventUrl(event && event.url);
		if (url) {
			var link = document.createElement("a");
			link.className = "ekdiloseis__event-link";
			link.href = url;
			link.textContent = label;
			title.appendChild(link);
		} else {
			title.textContent = label;
		}
		body.appendChild(title);
	}

	function render(root, data, day) {
		var list = root.querySelector(".ekdiloseis__list");
		var calEl = root.querySelector(".ekdiloseis-sx__calendar");
		if (!list || !day) {
			return;
		}
		if (calEl) {
			markSelected(calEl, day);
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

		if (!events.length) {
			var empty = document.createElement("p");
			empty.className = "ekdiloseis__empty";
			empty.textContent = data.emptyMessage || "No events on this day";
			list.appendChild(empty);
			return;
		}

		events.forEach(function (event) {
			var article = document.createElement("article");
			article.className = "ekdiloseis__event";
			paintListItem(article, event);

			if (window.EkdiloseisList) {
				window.EkdiloseisList.appendMedia(article, event, data);
			}

			var body = document.createElement("div");
			appendEventTitle(body, event);
			var time = document.createElement("p");
			time.className = "ekdiloseis__time";
			time.textContent = timeLabel(event);
			body.appendChild(time);
			article.appendChild(body);
			list.appendChild(article);
		});
	}

	function showHint(root, data) {
		var list = root.querySelector(".ekdiloseis__list");
		if (!list) {
			return;
		}
		while (list.firstChild) {
			list.removeChild(list.firstChild);
		}
		var hint = document.createElement("p");
		hint.className = "ekdiloseis__hint";
		hint.textContent = (data && data.hint) || "Select a day to view events.";
		list.appendChild(hint);
	}

	function boot(root) {
		var api = window.SXCalendar;
		if (!api || typeof api.createCalendar !== "function" || typeof api.createViewMonthGrid !== "function") {
			return;
		}
		if (typeof Temporal === "undefined" || !Temporal.PlainDate || !Temporal.ZonedDateTime) {
			return;
		}
		var dataEl = root.querySelector(".ekdiloseis-sx__data");
		var calEl = root.querySelector(".ekdiloseis-sx__calendar");
		if (!dataEl || !calEl) {
			return;
		}
		var data;
		try {
			data = JSON.parse(dataEl.textContent || "");
		} catch (error) {
			return;
		}

		var source = data.events || [];
		var sxEvents = [];
		var calendars = {};
		source.forEach(function (event) {
			var start = toZoned(event.start);
			var end = toZoned(event.end);
			if (!start || !end) {
				return;
			}
			var bg = eventColor(event);
			var fg = eventTextColor(event);
			var calendarId = "c" + bg.slice(1);
			calendars[calendarId] = {
				colorName: calendarId,
				lightColors: {
					main: bg,
					container: bg,
					onContainer: fg
				}
			};
			var sxEvent = {
				id: event.id,
				title: event.title || "",
				start: start,
				end: end,
				calendarId: calendarId
			};
			var url = safeEventUrl(event.url);
			if (url) {
				sxEvent.url = url;
			}
			sxEvents.push(sxEvent);
		});

		var initialDate = root.getAttribute("data-initial-date") || "";
		var selectedDate;
		try {
			selectedDate = initialDate ? Temporal.PlainDate.from(initialDate) : Temporal.Now.plainDateISO("Europe/Athens");
		} catch (error) {
			selectedDate = undefined;
		}

		var sawRange = false;
		var openedToday = false;

		function refreshMarks() {
			markHas(calEl, source);
		}

		function openToday() {
			if (openedToday) {
				return;
			}
			var today = todayIso(data);
			if (!dayHasEvents(data, today)) {
				openedToday = true;
				return;
			}
			if (!dayCells(calEl).length) {
				return;
			}
			openedToday = true;
			render(root, data, today);
		}

		function showDay(day) {
			if (!day) {
				return;
			}
			render(root, data, dayKey(day));
		}

		var config = {
			views: [api.createViewMonthGrid()],
			calendars: calendars,
			events: sxEvents,
			locale: data.locale === "el" ? "el-GR" : "en-US",
			firstDayOfWeek: 1,
			timezone: "Europe/Athens",
			isResponsive: false,
			selectedDate: selectedDate,
			monthGridOptions: {
				nEventsPerDay: 20
			},
			callbacks: {
				onRender: function () {
					refreshMarks();
					openToday();
				},
				onRangeUpdate: function () {
					refreshMarks();
					if (!sawRange) {
						sawRange = true;
						return;
					}
					showHint(root, data);
				},
				onClickDate: function (date, nativeEvent) {
					var day = "";
					if (nativeEvent) {
						day = dayUnderPointer(calEl, nativeEvent.clientX, nativeEvent.clientY);
					}
					if (!day) {
						day = dayKey(date);
					}
					showDay(day);
				},
				onEventClick: function (calendarEvent, nativeEvent) {
					var url = safeEventUrl(calendarEvent && calendarEvent.url);
					if (url) {
						if (nativeEvent && nativeEvent.preventDefault) {
							nativeEvent.preventDefault();
						}
						window.location.assign(url);
						return;
					}
					if (nativeEvent && nativeEvent.preventDefault) {
						nativeEvent.preventDefault();
					}
					var day = "";
					if (nativeEvent) {
						day = dayUnderPointer(calEl, nativeEvent.clientX, nativeEvent.clientY);
					}
					if (!day && calendarEvent) {
						day = dayKey(calendarEvent.start);
					}
					showDay(day);
				},
				onClickPlusEvents: function (date) {
					showDay(dayKey(date));
				}
			}
		};
		if (data.locale === "el" && data.sxTranslations) {
			config.translations = { elGR: data.sxTranslations };
		}
		var calendar;
		try {
			calendar = api.createCalendar(config);
			calendar.render(calEl);
		} catch (error) {
			return;
		}

		window.setTimeout(function () {
			refreshMarks();
			openToday();
		}, 0);

		calEl.addEventListener("click", function (event) {
			var day = dayUnderPointer(calEl, event.clientX, event.clientY);
			if (!day && event.target && event.target.closest) {
				var dated = event.target.closest("[data-date]");
				if (dated && calEl.contains(dated)) {
					day = dated.getAttribute("data-date") || "";
				}
			}
			showDay(day);
		}, true);
	}

	function init() {
		var roots = document.querySelectorAll(".ekdiloseis-sx");
		Array.prototype.forEach.call(roots, boot);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
