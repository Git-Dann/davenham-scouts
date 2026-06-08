(function () {
  function closest(element, selector) {
    while (element && element.nodeType === 1) {
      if (element.matches(selector)) {
        return element;
      }
      element = element.parentElement;
    }
    return null;
  }

  document.addEventListener('click', function (event) {
    var tab = event.target.closest('[data-def-tab]');
    if (tab) {
      var root = closest(tab, '[data-def-tabs]');
      var name = tab.getAttribute('data-def-tab');
      if (!root || !name) {
        return;
      }

      root.querySelectorAll('[data-def-tab]').forEach(function (node) {
        node.classList.toggle('nav-tab-active', node === tab);
      });

      root.querySelectorAll('[data-def-panel]').forEach(function (panel) {
        var active = panel.getAttribute('data-def-panel') === name;
        panel.classList.toggle('is-active', active);
        panel.hidden = !active;
      });
      return;
    }

    var addButton = event.target.closest('[data-def-add-row]');
    if (addButton) {
      var repeatable = closest(addButton, '[data-def-repeatable]');
      if (!repeatable) {
        var card = closest(addButton, '.def-admin-card');
        repeatable = card ? card.querySelector('[data-def-repeatable]') : null;
      }
      var template = document.getElementById(addButton.getAttribute('data-def-add-row'));
      var rows = repeatable ? repeatable.querySelector('.def-repeatable__rows') : null;
      if (!repeatable || !template || !rows) {
        return;
      }

      var index = parseInt(repeatable.getAttribute('data-next-index') || '0', 10);
      repeatable.setAttribute('data-next-index', String(index + 1));
      rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(index)));
      return;
    }

    var removeButton = event.target.closest('[data-def-remove-row]');
    if (removeButton) {
      var row = closest(removeButton, '.def-repeatable-row');
      if (row) {
        row.remove();
      }
    }
  });

  function parseAmount(value) {
    var parsed = parseFloat(String(value || '').replace(/,/g, '').replace(/[^0-9.-]/g, ''));
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function formatMoney(value) {
    return '£' + Math.max(0, value).toFixed(2);
  }

  function updateInventoryRow(row) {
    var quantity = row.querySelector('[name*="[quantity]"]');
    var unitCost = row.querySelector('[name*="[unit_cost]"]');
    var total = row.querySelector('[data-def-row-total]');
    if (!total) {
      return;
    }
    total.textContent = formatMoney(parseAmount(quantity && quantity.value) * parseAmount(unitCost && unitCost.value));
  }

  document.addEventListener('input', function (event) {
    var row = closest(event.target, '.def-repeatable-row--inventory');
    if (row && (event.target.name || '').match(/\[(quantity|unit_cost)\]/)) {
      updateInventoryRow(row);
    }
  });

  document.addEventListener('change', function (event) {
    if (event.target.matches('[data-def-check-done]')) {
      var checklistRow = closest(event.target, '.def-repeatable-row--checklist');
      var status = checklistRow ? checklistRow.querySelector('[data-def-check-status]') : null;
      if (status) {
        status.value = event.target.checked ? 'done' : 'todo';
      }
    }

    if (event.target.matches('[data-def-check-status]')) {
      var row = closest(event.target, '.def-repeatable-row--checklist');
      var checkbox = row ? row.querySelector('[data-def-check-done]') : null;
      if (checkbox) {
        checkbox.checked = event.target.value === 'done';
      }
    }
  });

  function storageAvailable() {
    try {
      var testKey = 'def-storage-test';
      window.localStorage.setItem(testKey, testKey);
      window.localStorage.removeItem(testKey);
      return true;
    } catch (error) {
      return false;
    }
  }

  function debounce(callback, wait) {
    var timeout = null;
    return function () {
      window.clearTimeout(timeout);
      timeout = window.setTimeout(callback, wait);
    };
  }

  function createStatusBar(root) {
    var bar = document.createElement('div');
    bar.className = 'def-offline-status';
    bar.setAttribute('role', 'status');
    root.insertBefore(bar, root.firstChild);
    return bar;
  }

  function updateOnlineStatus(bar) {
    if (!bar) {
      return;
    }
    var offline = !window.navigator.onLine;
    document.body.classList.toggle('def-is-offline', offline);
    bar.textContent = offline ? 'Offline: this event is being saved in this browser until the connection returns.' : '';
    bar.hidden = !offline;
  }

  function setupEventDrafts() {
    var root = document.querySelector('.def-event-admin');
    var form = root ? closest(root, 'form') : null;
    var postId = document.getElementById('post_ID');
    var canStore = storageAvailable();

    if (!root || !form || !postId || !canStore) {
      return;
    }

    var key = 'davenhamEventDraft:' + postId.value;
    var savedAfterSubmit = window.location.search.indexOf('message=') !== -1 || window.location.search.indexOf('saved=') !== -1;
    if (savedAfterSubmit) {
      window.localStorage.removeItem(key);
    }

    var bar = createStatusBar(root);
    updateOnlineStatus(bar);
    window.addEventListener('online', function () {
      updateOnlineStatus(bar);
    });
    window.addEventListener('offline', function () {
      updateOnlineStatus(bar);
    });

    function fields() {
      return Array.prototype.slice.call(form.querySelectorAll('[name^="event_"], [name^="davenham_event_"]'));
    }

    function snapshot() {
      var data = {};
      fields().forEach(function (field) {
        if (!field.name || field.disabled) {
          return;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
          data[field.name] = field.checked;
          return;
        }

        if (field.multiple) {
          data[field.name] = Array.prototype.slice.call(field.options).filter(function (option) {
            return option.selected;
          }).map(function (option) {
            return option.value;
          });
          return;
        }

        data[field.name] = field.value;
      });

      return data;
    }

    function saveDraft() {
      window.localStorage.setItem(key, JSON.stringify({ savedAt: Date.now(), values: snapshot() }));
    }

    function restoreDraft(values) {
      fields().forEach(function (field) {
        if (!Object.prototype.hasOwnProperty.call(values, field.name)) {
          return;
        }

        var value = values[field.name];
        if (field.type === 'checkbox' || field.type === 'radio') {
          field.checked = Boolean(value);
          return;
        }

        if (field.multiple && Array.isArray(value)) {
          Array.prototype.slice.call(field.options).forEach(function (option) {
            option.selected = value.indexOf(option.value) !== -1;
          });
          return;
        }

        field.value = value;
      });
    }

    function showRestorePrompt(draft) {
      var prompt = document.createElement('div');
      var date = draft.savedAt ? new Date(draft.savedAt) : null;
      prompt.className = 'def-draft-restore';
      prompt.innerHTML = '<strong>Local event draft found.</strong><span>' + (date ? ' Saved ' + date.toLocaleString() + '.' : '') + '</span><button type="button" class="button button-primary" data-def-restore-draft>Restore</button><button type="button" class="button button-secondary" data-def-discard-draft>Discard</button>';
      root.insertBefore(prompt, root.firstChild);

      prompt.addEventListener('click', function (event) {
        if (event.target.matches('[data-def-restore-draft]')) {
          restoreDraft(draft.values || {});
          prompt.remove();
          saveDraft();
        }

        if (event.target.matches('[data-def-discard-draft]')) {
          window.localStorage.removeItem(key);
          prompt.remove();
        }
      });
    }

    var existingDraft = null;
    try {
      existingDraft = JSON.parse(window.localStorage.getItem(key) || 'null');
    } catch (error) {
      existingDraft = null;
    }

    if (existingDraft && existingDraft.values && !savedAfterSubmit) {
      showRestorePrompt(existingDraft);
    }

    var debouncedSave = debounce(saveDraft, 350);
    form.addEventListener('input', debouncedSave);
    form.addEventListener('change', debouncedSave);
    form.addEventListener('submit', function () {
      window.localStorage.removeItem(key);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupEventDrafts);
  } else {
    setupEventDrafts();
  }
}());
