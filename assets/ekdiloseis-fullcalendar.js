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

	function isoDay(date) {
		return date.getFullYear() + "-" + pad(date.getMonth() + 1) + "-" + pad(date.getDate());
	}

	function markSelected(root, day) {
		var cells = root.querySelectorAll(".fc-daygrid-day");
		Array.prototype.forEach.call(cells, function (cell) {
			if (cell.getAttribute("data-date") === day) {
				cell.classList.add("is-selected");
			} else {
				cell.classList.remove("is-selected");
			}
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
		if (!list || !day) {
			return;
		}
		markSelected(root, day);

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

	function dayUnderPointer(root, clientX, clientY) {
		var cells = root.querySelectorAll(".fc-daygrid-day[data-date]");
		for (var i = 0; i < cells.length; i++) {
			var rect = cells[i].getBoundingClientRect();
			if (clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom) {
				return cells[i].getAttribute("data-date") || "";
			}
		}
		return "";
	}

	function boot(root) {
		if (typeof FullCalendar === "undefined" || !FullCalendar.Calendar) {
			return;
		}
		var dataEl = root.querySelector(".ekdiloseis-fc__data");
		var calEl = root.querySelector(".ekdiloseis-fc__calendar");
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
		var fcEvents = source.map(function (event) {
			var bg = eventColor(event);
			var fg = eventTextColor(event);
			var item = {
				id: String(event.id),
				title: event.title || "",
				start: String(event.start || "").replace(" ", "T"),
				end: String(event.end || "").replace(" ", "T"),
				color: bg,
				backgroundColor: bg,
				borderColor: bg,
				textColor: fg,
				extendedProps: {
					thumb: event.thumb || null,
					time: event.time || ""
				}
			};
			var url = safeEventUrl(event.url);
			if (url) {
				item.url = url;
			}
			return item;
		});

		var initialDate = root.getAttribute("data-initial-date") || undefined;
		var calendar = new FullCalendar.Calendar(calEl, {
			initialView: "dayGridMonth",
			initialDate: initialDate,
			locale: data.locale === "el" ? "el" : "en",
			firstDay: 1,
			height: "auto",
			fixedWeekCount: false,
			headerToolbar: {
				left: "prev,next today",
				center: "title",
				right: ""
			},
			events: fcEvents,
			dayCellClassNames: function (arg) {
				var day = isoDay(arg.date);
				var has = source.some(function (event) {
					return overlaps(event, day);
				});
				return has ? ["ekdiloseis-fc__has"] : [];
			},
			dateClick: function (info) {
				render(root, data, info.dateStr);
			},
			eventClick: function (info) {
				var url = info.event ? safeEventUrl(info.event.url) : "";
				if (url) {
					if (info.jsEvent) {
						info.jsEvent.preventDefault();
					}
					window.location.assign(url);
					return;
				}
				if (info.jsEvent) {
					info.jsEvent.preventDefault();
				}
				var day = "";
				if (info.jsEvent) {
					day = dayUnderPointer(root, info.jsEvent.clientX, info.jsEvent.clientY);
				}
				if (!day && info.el && info.el.closest) {
					var cell = info.el.closest("[data-date]");
					day = cell ? cell.getAttribute("data-date") || "" : "";
				}
				if (!day && info.event && info.event.startStr) {
					day = String(info.event.startStr).slice(0, 10);
				}
				render(root, data, day);
			}
		});
		calendar.render();
		var today = todayIso(data);
		if (dayHasEvents(data, today)) {
			render(root, data, today);
		}
	}

	function init() {
		var roots = document.querySelectorAll(".ekdiloseis-fc");
		Array.prototype.forEach.call(roots, boot);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
