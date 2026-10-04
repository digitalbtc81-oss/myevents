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
		var match = String(value || "").match(/^(\d{4}-\d{2}-\d{2})/);
		return match ? match[1] : "";
	}

	function dayCells(calEl) {
		return calEl.querySelectorAll(".ec-day-grid .ec-day");
	}

	function stampDays(calEl, info) {
		var start = dayKey(info && info.startStr);
		var end = dayKey(info && info.endStr);
		if (!start || !end) {
			return;
		}
		var cells = dayCells(calEl);
		var day = start;
		var index = 0;
		while (day < end && index < cells.length) {
			cells[index].setAttribute("data-date", day);
			day = nextDay(day);
			index += 1;
		}
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
			cell.classList.toggle("ekdiloseis-ec__has", has);
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
		var calEl = root.querySelector(".ekdiloseis-ec__calendar");
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

	function boot(root) {
		if (!window.EventCalendar || typeof window.EventCalendar.create !== "function") {
			return;
		}
		var dataEl = root.querySelector(".ekdiloseis-ec__data");
		var calEl = root.querySelector(".ekdiloseis-ec__calendar");
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
		var ecEvents = source.map(function (event) {
			var bg = eventColor(event);
			var fg = eventTextColor(event);
			var url = safeEventUrl(event.url);
			return {
				id: String(event.id),
				title: event.title || "",
				start: String(event.start || "").replace(" ", "T"),
				end: String(event.end || "").replace(" ", "T"),
				color: bg,
				backgroundColor: bg,
				textColor: fg,
				extendedProps: url ? { url: url } : {}
			};
		});

		var initialDate = root.getAttribute("data-initial-date") || undefined;

		function showDay(day) {
			if (!day) {
				return;
			}
			render(root, data, day);
		}

		var openedToday = false;
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
			showDay(today);
		}

		var options = {
			view: "dayGridMonth",
			date: initialDate,
			locale: data.locale === "el" ? "el-GR" : "en",
			firstDay: 1,
			editable: false,
			eventStartEditable: false,
			eventDurationEditable: false,
			dayMaxEvents: false,
			headerToolbar: {
				start: "prev,next today",
				center: "title",
				end: ""
			},
			events: ecEvents,
			datesSet: function (info) {
				stampDays(calEl, info);
				markHas(calEl, source);
				openToday();
			},
			dateClick: function (info) {
				var day = dayKey(info && info.dateStr);
				if (!day && info && info.jsEvent) {
					day = dayUnderPointer(calEl, info.jsEvent.clientX, info.jsEvent.clientY);
				}
				showDay(day);
			},
			eventClick: function (info) {
				var url = "";
				if (info && info.event && info.event.extendedProps) {
					url = safeEventUrl(info.event.extendedProps.url);
				}
				if (url) {
					if (info.jsEvent) {
						info.jsEvent.preventDefault();
						if (info.jsEvent.stopPropagation) {
							info.jsEvent.stopPropagation();
						}
					}
					window.location.assign(url);
					return;
				}
				if (info && info.jsEvent) {
					info.jsEvent.preventDefault();
				}
				var day = "";
				if (info && info.jsEvent) {
					day = dayUnderPointer(calEl, info.jsEvent.clientX, info.jsEvent.clientY);
				}
				if (!day && info && info.event && info.event.start) {
					day = dayKey(info.event.start.toISOString ? info.event.start.toISOString() : info.event.start);
				}
				showDay(day);
			}
		};
		if (data.locale === "el") {
			options.buttonText = function (text) {
				return Object.assign({}, text || {}, {
					today: data.today || "Today",
					next: data.nextMonth || "Next month",
					prev: data.previousMonth || "Previous month"
				});
			};
		}
		var calendar = window.EventCalendar.create(calEl, options);
		openToday();
	}

	function init() {
		var roots = document.querySelectorAll(".ekdiloseis-ec");
		Array.prototype.forEach.call(roots, boot);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
