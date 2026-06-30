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
		var wrap = document.querySelector('[data-dpp-children]');
		if (!wrap) {
			return;
		}

		var addBtn = wrap.querySelector('[data-dpp-add-child]');
		var tpl = document.querySelector('[data-dpp-child-template]');

		function addRow() {
			if (!tpl) {
				return;
			}
			var node = tpl.content ? tpl.content.cloneNode(true) : null;
			if (!node) {
				return;
			}
			// Insert the new row before the "add" button.
			if (addBtn && addBtn.parentNode === wrap) {
				wrap.insertBefore(node, addBtn);
			} else {
				wrap.appendChild(node);
			}
		}

		if (addBtn) {
			addBtn.addEventListener('click', function () {
				addRow();
			});
		}

		// Remove (event delegation). Never remove the last remaining row.
		wrap.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('[data-dpp-remove]') : null;
			if (!btn) {
				return;
			}
			var rows = wrap.querySelectorAll('[data-dpp-child-row]');
			if (rows.length <= 1) {
				// Clear the inputs instead of removing the only row.
				var only = rows[0];
				only.querySelectorAll('input, select').forEach(function (el) {
					el.value = '';
				});
				return;
			}
			var row = btn.closest('[data-dpp-child-row]');
			if (row) {
				row.parentNode.removeChild(row);
			}
		});
	});
}());
