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

	function pad(value) {
		var text = String(value);
		return text.length < 2 ? "0" + text : text;
	}

	function isoFromParts(year, monthIndex, day) {
		return year + "-" + pad(monthIndex + 1) + "-" + pad(day);
	}

	function isoFromAny(value) {
		if (!value) {
			return "";
		}
		if (typeof value === "string") {
			var match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
			return match ? match[1] + "-" + match[2] + "-" + match[3] : "";
		}
		if (typeof value.getFullYear === "function" && typeof value.getMonth === "function" && typeof value.getDate === "function") {
			return isoFromParts(value.getFullYear(), value.getMonth(), value.getDate());
		}
		if (typeof value.toDate === "function") {
			return isoFromAny(value.toDate());
		}
		return "";
	}

	function dayUnderPointer(calEl, clientX, clientY) {
		var cells = calEl.querySelectorAll(".toastui-calendar-daygrid-cell");
		for (var i = 0; i < cells.length; i++) {
			var rect = cells[i].getBoundingClientRect();
			if (clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom) {
				var dated = cells[i].querySelector("[data-date]");
				return dated ? dated.getAttribute("data-date") || "" : "";
			}
		}
		return "";
	}

	function markSelected(calEl, day) {
		var cells = calEl.querySelectorAll(".toastui-calendar-daygrid-cell");
		Array.prototype.forEach.call(cells, function (cell) {
			var dated = cell.querySelector("[data-date]");
			var cellDay = dated ? dated.getAttribute("data-date") : "";
			if (cellDay && cellDay === day) {
				cell.classList.add("is-selected");
			} else {
				cell.classList.remove("is-selected");
			}
		});
	}

	function markHas(calEl, events) {
		var cells = calEl.querySelectorAll(".toastui-calendar-daygrid-cell");
		Array.prototype.forEach.call(cells, function (cell) {
			var dated = cell.querySelector("[data-date]");
			var day = dated ? dated.getAttribute("data-date") : "";
			var has = false;
			if (day) {
				has = (events || []).some(function (event) {
					return overlaps(event, day);
				});
			}
			cell.classList.toggle("ekdiloseis-toast__has", has);
		});
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
		var calEl = root.querySelector(".ekdiloseis-toast__calendar");
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

	function monthTitle(data, date) {
		var names = data.monthNames || [];
		var name = names[date.getMonth()] || "";
		return (name ? name + " " : "") + date.getFullYear();
	}

	function syncTitle(root, data, calendar) {
		var heading = root.querySelector(".ekdiloseis__title");
		if (!heading || !calendar || typeof calendar.getDate !== "function") {
			return;
		}
		var current = calendar.getDate();
		var iso = isoFromAny(current);
		if (!iso) {
			return;
		}
		var parts = iso.split("-");
		var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
		heading.textContent = monthTitle(data, date);
	}

	function boot(root) {
		var CalendarCtor = window.tui && window.tui.Calendar;
		if (typeof CalendarCtor !== "function") {
			return;
		}
		var dataEl = root.querySelector(".ekdiloseis-toast__data");
		var calEl = root.querySelector(".ekdiloseis-toast__calendar");
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
		var eventUrls = {};
		source.forEach(function (event) {
			var url = safeEventUrl(event.url);
			if (url && event.id != null) {
				eventUrls[String(event.id)] = url;
			}
		});
		var initialDate = root.getAttribute("data-initial-date") || undefined;
		var calendarsById = {};
		var toastEvents = source.map(function (event) {
			var bg = eventColor(event);
			var fg = eventTextColor(event);
			var calendarId = "c" + bg.slice(1);
			calendarsById[calendarId] = {
				id: calendarId,
				name: calendarId,
				backgroundColor: bg,
				borderColor: bg,
				dragBackgroundColor: bg,
				color: fg
			};
			return {
				id: String(event.id),
				calendarId: calendarId,
				title: event.title || "",
				start: String(event.start || "").replace(" ", "T"),
				end: String(event.end || "").replace(" ", "T"),
				category: "time",
				isReadOnly: true,
				backgroundColor: bg,
				borderColor: bg,
				dragBackgroundColor: bg,
				color: fg
			};
		});
		var calendars = Object.keys(calendarsById).map(function (key) {
			return calendarsById[key];
		});
		if (!calendars.length) {
			calendars = [{
				id: "c6b7280",
				name: "Events",
				backgroundColor: "#6b7280",
				borderColor: "#6b7280",
				dragBackgroundColor: "#6b7280",
				color: "#ffffff"
			}];
		}
		var calendar = new CalendarCtor(calEl, {
			defaultView: "month",
			usageStatistics: false,
			isReadOnly: false,
			useFormPopup: false,
			useDetailPopup: false,
			gridSelection: {
				enableClick: true,
				enableDblClick: false
			},
			month: {
				startDayOfWeek: 1,
				dayNames: (data.dayNames && data.dayNames.length === 7) ? data.dayNames : ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
				visibleEventCount: 20,
				isAlways6Weeks: false
			},
			calendars: calendars,
			template: {
				monthGridHeader: function (model) {
					model = model || {};
					var ymd = model.ymd != null ? String(model.ymd) : "";
					if (!/^\d{4}-\d{2}-\d{2}$/.test(ymd)) {
						var rawDate = model.date != null ? String(model.date) : "";
						ymd = /^\d{4}-\d{2}-\d{2}/.test(rawDate) ? rawDate.slice(0, 10) : "";
					}
					var raw = model.date != null ? String(model.date) : "";
					var day = raw.indexOf("-") === -1 ? raw : String(parseInt(raw.split("-").pop(), 10));
					return '<span class="ekdiloseis-toast__date" data-date="' + ymd + '">' + day + "</span>";
				}
			}
		});

		if (initialDate) {
			calendar.setDate(initialDate);
		}

		calendar.createEvents(toastEvents);

		function refreshMarks() {
			markHas(calEl, source);
		}

		refreshMarks();
		syncTitle(root, data, calendar);
		var today = todayIso(data);
		if (dayHasEvents(data, today)) {
			render(root, data, today);
		}

		function showDay(day) {
			if (!day) {
				return;
			}
			render(root, data, day);
		}

		calEl.addEventListener("click", function (event) {
			if (event.target && event.target.closest && event.target.closest(".toastui-calendar-weekday-event-block, .toastui-calendar-weekday-event, .toastui-calendar-event-time")) {
				return;
			}
			var day = dayUnderPointer(calEl, event.clientX, event.clientY);
			if (!day && event.target && event.target.closest) {
				var dated = event.target.closest("[data-date]");
				if (dated && calEl.contains(dated)) {
					day = dated.getAttribute("data-date") || "";
				}
			}
			showDay(day);
		}, true);

		calendar.on("selectDateTime", function (info) {
			var day = "";
			if (info && info.nativeEvent) {
				day = dayUnderPointer(calEl, info.nativeEvent.clientX, info.nativeEvent.clientY);
			}
			if (!day && info) {
				day = isoFromAny(info.start);
			}
			showDay(day);
			if (typeof calendar.clearGridSelections === "function") {
				calendar.clearGridSelections();
			}
		});

		calendar.on("clickEvent", function (info) {
			var id = info && info.event && info.event.id != null ? String(info.event.id) : "";
			var url = id ? eventUrls[id] || "" : "";
			if (url) {
				if (info.nativeEvent && info.nativeEvent.preventDefault) {
					info.nativeEvent.preventDefault();
				}
				window.location.assign(url);
				return;
			}
			var day = "";
			if (info && info.nativeEvent) {
				info.nativeEvent.preventDefault();
				day = dayUnderPointer(calEl, info.nativeEvent.clientX, info.nativeEvent.clientY);
			}
			if (!day && info && info.event) {
				day = isoFromAny(info.event.start);
			}
			showDay(day);
		});

		calendar.on("clickMoreEventsBtn", function (info) {
			showDay(info ? isoFromAny(info.date) : "");
		});

		var prev = root.querySelector("[data-nav='prev']");
		var next = root.querySelector("[data-nav='next']");
		if (prev) {
			prev.addEventListener("click", function () {
				calendar.prev();
				refreshMarks();
				syncTitle(root, data, calendar);
				showHint(root, data);
			});
		}
		if (next) {
			next.addEventListener("click", function () {
				calendar.next();
				refreshMarks();
				syncTitle(root, data, calendar);
				showHint(root, data);
			});
		}
	}

	function init() {
		var roots = document.querySelectorAll(".ekdiloseis-toast");
		Array.prototype.forEach.call(roots, boot);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
