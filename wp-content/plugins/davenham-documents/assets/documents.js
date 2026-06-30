(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	ready(function () {
		var root = document.querySelector('[data-davenham-docs]');
		if (!root) {
			return;
		}

		var search = root.querySelector('[data-davenham-search]');
		if (!search) {
			return;
		}

		var items = Array.prototype.slice.call(root.querySelectorAll('[data-davenham-item]'));
		var groups = Array.prototype.slice.call(root.querySelectorAll('[data-davenham-group]'));
		var noResults = root.querySelector('[data-davenham-noresults]');

		function apply() {
			var q = search.value.trim().toLowerCase();
			var anyVisible = false;

			items.forEach(function (item) {
				var haystack = item.getAttribute('data-search') || '';
				var show = q === '' || haystack.indexOf(q) !== -1;
				item.hidden = !show;
				if (show) {
					anyVisible = true;
				}
			});

			// Hide a category heading if all of its items are filtered out.
			groups.forEach(function (group) {
				var visible = group.querySelectorAll('[data-davenham-item]:not([hidden])');
				group.hidden = visible.length === 0;
			});

			if (noResults) {
				noResults.hidden = anyVisible;
			}
		}

		var timer = null;
		search.addEventListener('input', function () {
			window.clearTimeout(timer);
			timer = window.setTimeout(apply, 120);
		});

		// Pressing Escape clears the filter.
		search.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				search.value = '';
				apply();
			}
		});
	});
}());
