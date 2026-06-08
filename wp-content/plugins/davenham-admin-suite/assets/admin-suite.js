jQuery(function ($) {
  function updatePreview($input) {
    var $wrap = $input.closest('td');
    var value = $input.val();
    var $preview = $wrap.find('.das-logo-preview');

    if (!value) {
      $preview.remove();
      return;
    }

    if (!$preview.length) {
      $preview = $('<p class="das-logo-preview"><img alt=""></p>');
      $wrap.append($preview);
    }

    $preview.find('img').attr('src', value);
  }

  $('.das-media-open').on('click', function (event) {
    event.preventDefault();

    var $button = $(this);
    var target = $button.data('target');
    var idTarget = $button.data('id-target');
    var $input = $(target);
    var $idInput = $(idTarget);

    var frame = wp.media({
      title: 'Choose admin logo',
      button: {
        text: 'Use this image'
      },
      library: {
        type: ['image']
      },
      multiple: false
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      $input.val(attachment.url).trigger('change');
      if ($idInput.length) {
        $idInput.val(attachment.id);
      }
      updatePreview($input);
    });

    frame.open();
  });

  $('.das-media-clear').on('click', function (event) {
    event.preventDefault();

    var target = $(this).data('target');
    var idTarget = $(this).data('id-target');
    var $input = $(target);
    var $idInput = $(idTarget);
    $input.val('').trigger('change');
    if ($idInput.length) {
      $idInput.val('');
    }
    updatePreview($input);
  });

  $('#das_admin_logo_url').on('change', function () {
    updatePreview($(this));
  });

  $('.das-add-custom-link').on('click', function (event) {
    event.preventDefault();

    var $wrap = $(this).closest('.das-custom-links');
    var $rows = $wrap.find('.das-custom-link-rows');
    var template = $('#das-custom-link-template').html();
    var nextIndex = parseInt($wrap.attr('data-next-index'), 10) || 0;

    template = template.replace(/__INDEX__/g, nextIndex);
    $rows.append(template);
    $wrap.attr('data-next-index', nextIndex + 1);
  });

  $(document).on('click', '.das-remove-custom-link', function (event) {
    event.preventDefault();
    $(this).closest('.das-custom-link-row').remove();
  });

  function storageAvailable() {
    try {
      var key = 'das-storage-test';
      window.localStorage.setItem(key, key);
      window.localStorage.removeItem(key);
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

  function initAdminShell() {
    var body = document.body;
    if (!body) return;

    // The shell is now rendered server-side by render_admin_shell() (PHP).
    // This function ONLY wires up behaviour on the existing DOM — it never
    // creates the shell. If the shell isn't on the page, bail silently
    // (means it's been disabled in settings).
    var shell = document.querySelector('.das-app-shell');
    if (!shell) return;

    // Ensure body has the marker class so CSS layout rules apply, even on
    // pages whose body_class() filter didn't fire (Media grid, etc.).
    if (!body.classList.contains('davenham-admin-shell')) {
      body.classList.add('davenham-admin-shell');
    }

    // Mark <html> so global CSS can target the shell-active state.
    document.documentElement.classList.add('das-app-shell-active');

    // MutationObserver safety net — re-apply the body class if anything
    // strips it later (Backbone, Customizer, plugins). Cheap to run.
    if (window.MutationObserver) {
      var bodyObserver = new MutationObserver(function () {
        if (!body.classList.contains('davenham-admin-shell')) {
          body.classList.add('davenham-admin-shell');
        }
      });
      bodyObserver.observe(body, { attributes: true, attributeFilter: ['class'] });
    }

    var canStore = storageAvailable();

    if (canStore && window.localStorage.getItem('davenhamAdminNavCollapsed') === '1') {
      body.classList.add('das-app-nav-collapsed');
    }

    function escapeHtml(value) {
      return String(value || '').replace(/[&<>"']/g, function (character) {
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        }[character];
      });
    }

    function iconClass(icon) {
      var map = {
        dashboard: 'dashicons-dashboard',
        tickets: 'dashicons-tickets-alt',
        calendar: 'dashicons-calendar-alt',
        pages: 'dashicons-admin-page',
        media: 'dashicons-format-image',
        cart: 'dashicons-cart',
        orders: 'dashicons-cart',
        products: 'dashicons-products',
        users: 'dashicons-admin-users',
        posts: 'dashicons-admin-post',
        forms: 'dashicons-feedback',
        links: 'dashicons-admin-links',
        payments: 'dashicons-money-alt',
        marketing: 'dashicons-megaphone',
        builder: 'dashicons-layout',
        appearance: 'dashicons-admin-appearance',
        analytics: 'dashicons-chart-bar',
        folder: 'dashicons-portfolio',
        admin: 'dashicons-admin-tools',
        plugins: 'dashicons-admin-plugins',
        tools: 'dashicons-admin-tools',
        updates: 'dashicons-update',
        security: 'dashicons-shield',
        backup: 'dashicons-database',
        health: 'dashicons-heart',
        speed: 'dashicons-performance',
        pin: 'dashicons-marker'
      };
      return map[icon] || 'dashicons-marker';
    }

    function currentUrlWithoutHash() {
      return window.location.href.replace(window.location.hash, '');
    }

    function isActive(url) {
      var current = currentUrlWithoutHash();
      return url && (current === url || current.indexOf(url) === 0 || current.indexOf(url.replace(window.location.origin, '')) !== -1);
    }

    function hasFlyout(item) {
      return item.kind === 'admin-tools' || (Array.isArray(item.children) && item.children.length > 0);
    }

    function groupLinksActive(groups) {
      return groups.some(function (group) {
        var links = Array.isArray(group.links) ? group.links : [];
        return links.some(function (link) {
          return isActive(link.url);
        });
      });
    }

    function isItemActive(item) {
      if (isActive(item.url)) {
        return true;
      }

      if (item.kind === 'admin-tools' && groupLinksActive(adminGroups)) {
        return true;
      }

      var childLinks = Array.isArray(item.children) ? item.children : [];
      return childLinks.some(function (link) {
        return isActive(link.url);
      });
    }

    function renderFlyoutContent(item) {
      if (item.kind === 'admin-tools') {
        return '<div class="das-app-flyout-heading"><strong>' + escapeHtml(item.label || 'Admin') + '</strong><a href="' + escapeHtml(item.url || '#') + '">Open overview</a></div>' +
          adminGroups.map(function (group) {
            var links = Array.isArray(group.links) ? group.links : [];
            return '<section class="das-flyout-folder"><h3><span class="dashicons dashicons-portfolio" aria-hidden="true"></span>' + escapeHtml(group.label) + '</h3>' +
              (links.length ? links.map(function (link) {
                return '<a href="' + escapeHtml(link.url) + '">' + escapeHtml(link.label) + '</a>';
              }).join('') : '<p>No links assigned</p>') +
              '</section>';
          }).join('');
      }

      var childLinks = Array.isArray(item.children) ? item.children : [];
      return '<div class="das-app-flyout-heading"><strong>' + escapeHtml(item.label) + '</strong><a href="' + escapeHtml(item.url || '#') + '">Open main page</a></div>' +
        '<div class="das-app-flyout-list">' +
        childLinks.map(function (link) {
          return '<a href="' + escapeHtml(link.url) + '">' + escapeHtml(link.label) + '</a>';
        }).join('') +
        '</div>';
    }

    function renderNavItem(item) {
      var flyout = hasFlyout(item);
      var divider = item.dividerBefore ? '<div class="das-app-divider" aria-hidden="true"></div>' : '';
      var classes = 'das-app-nav-item' + (isItemActive(item) ? ' is-active' : '') + (flyout ? ' has-flyout' : '');
      var icon = '<span class="dashicons ' + escapeHtml(iconClass(item.icon)) + '" aria-hidden="true"></span>';
      var label = '<span class="das-app-nav-label">' + escapeHtml(item.label) + '</span>';
      var chevron = flyout ? '<span class="dashicons dashicons-arrow-right-alt2 das-app-nav-chevron" aria-hidden="true"></span>' : '';

      if (flyout) {
        return divider + '<button type="button" class="' + classes + '" data-das-flyout-target="das-flyout-' + item.shellIndex + '" aria-expanded="false">' + icon + label + chevron + '</button>';
      }

      return divider + '<a href="' + escapeHtml(item.url) + '" class="' + classes + '">' + icon + label + '</a>';
    }

    // The shell is rendered server-side by admin-shell.php — we only wire
    // up behaviour here. Look up existing DOM nodes.
    var mobileMenu = shell.querySelector('.das-app-mobile-menu');
    var overlay = shell.querySelector('.das-app-overlay');
    var collapse = shell.querySelector('.das-app-collapse');
    var flyoutButtons = Array.prototype.slice.call(shell.querySelectorAll('[data-das-flyout-target]'));

    if (mobileMenu) {
      mobileMenu.addEventListener('click', function () {
        body.classList.add('das-app-nav-open');
      });
    }

    if (overlay) {
      overlay.addEventListener('click', function () {
        body.classList.remove('das-app-nav-open');
      });
    }

    function closeFlyouts() {
      flyoutButtons.forEach(function (button) {
        button.classList.remove('is-open');
        button.setAttribute('aria-expanded', 'false');
      });

      shell.querySelectorAll('.das-app-flyout.is-open').forEach(function (panel) {
        panel.classList.remove('is-open');
      });
    }

    function openFlyout(button) {
      var panel = document.getElementById(button.getAttribute('data-das-flyout-target'));
      if (!panel) {
        return;
      }

      var alreadyOpen = panel.classList.contains('is-open');
      closeFlyouts();
      if (alreadyOpen && !window.matchMedia('(max-width: 782px)').matches) {
        return;
      }

      button.classList.add('is-open');
      button.setAttribute('aria-expanded', 'true');
      panel.classList.add('is-open');

      if (!window.matchMedia('(max-width: 782px)').matches) {
        var rect = button.getBoundingClientRect();
        var estimatedHeight = Math.min(panel.scrollHeight || 420, window.innerHeight - 24);
        var top = Math.max(12, Math.min(rect.top, window.innerHeight - estimatedHeight - 12));
        panel.style.top = top + 'px';
      } else {
        panel.style.top = '';
      }
    }

    flyoutButtons.forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        openFlyout(button);
      });

      button.addEventListener('mouseenter', function () {
        if (!window.matchMedia('(max-width: 782px)').matches) {
          openFlyout(button);
        }
      });
    });

    if (collapse) {
      collapse.addEventListener('click', function () {
        body.classList.toggle('das-app-nav-collapsed');
        closeFlyouts();
        if (canStore) {
          window.localStorage.setItem('davenhamAdminNavCollapsed', body.classList.contains('das-app-nav-collapsed') ? '1' : '0');
        }
      });
    }

    document.addEventListener('click', function (event) {
      if (event.target.closest('.das-app-rail') || event.target.closest('.das-app-flyout')) {
        return;
      }

      closeFlyouts();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') {
        return;
      }

      body.classList.remove('das-app-nav-open');
      closeFlyouts();
    });

    window.addEventListener('resize', debounce(function () {
      if (!window.matchMedia('(max-width: 782px)').matches) {
        body.classList.remove('das-app-nav-open');
      }
      closeFlyouts();
    }, 150));
  }

  function initOfflineIndicator() {
    var body = document.body;
    if (!body || !body.classList.contains('davenham-admin-shell') || document.querySelector('.das-offline-indicator')) {
      return;
    }

    var indicator = document.createElement('div');
    indicator.className = 'das-offline-indicator';
    indicator.setAttribute('role', 'status');
    indicator.textContent = 'Offline mode: changes are held in this browser until the connection returns.';
    document.body.appendChild(indicator);

    function update() {
      var offline = !window.navigator.onLine;
      body.classList.toggle('das-is-offline', offline);
      indicator.hidden = !offline;
    }

    window.addEventListener('online', update);
    window.addEventListener('offline', update);
    update();
  }

  function initDraftRecovery() {
    if (!storageAvailable() || !document.body.classList.contains('davenham-admin-shell')) {
      return;
    }

    var forms = Array.prototype.slice.call(document.querySelectorAll('#wpbody-content form'));
    var savedAfterSubmit = window.location.search.indexOf('message=') !== -1 || window.location.search.indexOf('updated=') !== -1 || window.location.search.indexOf('settings-updated=') !== -1;

    forms.forEach(function (form, index) {
      if (form.classList.contains('search-form') || form.id === 'posts-filter' || form.method.toLowerCase() === 'get') {
        return;
      }

      var key = 'davenhamAdminDraft:' + window.location.pathname + window.location.search.replace(/[?&](message|updated|settings-updated)=[^&]*/g, '') + ':' + index;
      if (savedAfterSubmit) {
        window.localStorage.removeItem(key);
      }

      function editableFields() {
        return Array.prototype.slice.call(form.querySelectorAll('input[name], select[name], textarea[name]')).filter(function (field) {
          return !field.disabled && field.type !== 'hidden' && field.type !== 'password' && field.type !== 'file' && field.name.indexOf('_wp') !== 0 && field.name !== 'action';
        });
      }

      function snapshot() {
        var values = {};
        editableFields().forEach(function (field) {
          if (field.type === 'checkbox' || field.type === 'radio') {
            values[field.name] = field.checked;
            return;
          }

          if (field.multiple) {
            values[field.name] = Array.prototype.slice.call(field.options).filter(function (option) {
              return option.selected;
            }).map(function (option) {
              return option.value;
            });
            return;
          }

          values[field.name] = field.value;
        });
        return values;
      }

      function saveDraft() {
        window.localStorage.setItem(key, JSON.stringify({ savedAt: Date.now(), values: snapshot() }));
      }

      function restoreDraft(values) {
        editableFields().forEach(function (field) {
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

      function showPrompt(draft) {
        var prompt = document.createElement('div');
        var date = draft.savedAt ? new Date(draft.savedAt) : null;
        prompt.className = 'das-draft-restore notice notice-info';
        prompt.innerHTML = '<p><strong>Local draft found.</strong> ' + (date ? 'Saved ' + date.toLocaleString() + '. ' : '') + '<button type="button" class="button button-primary" data-das-restore-draft>Restore</button> <button type="button" class="button button-secondary" data-das-discard-draft>Discard</button></p>';
        form.parentNode.insertBefore(prompt, form);

        prompt.addEventListener('click', function (event) {
          if (event.target.matches('[data-das-restore-draft]')) {
            restoreDraft(draft.values || {});
            prompt.remove();
            saveDraft();
          }

          if (event.target.matches('[data-das-discard-draft]')) {
            window.localStorage.removeItem(key);
            prompt.remove();
          }
        });
      }

      try {
        var draft = JSON.parse(window.localStorage.getItem(key) || 'null');
        if (draft && draft.values && !savedAfterSubmit) {
          showPrompt(draft);
        }
      } catch (error) {
        window.localStorage.removeItem(key);
      }

      var debouncedSave = debounce(saveDraft, 450);
      form.addEventListener('input', debouncedSave);
      form.addEventListener('change', debouncedSave);
      form.addEventListener('submit', function () {
        window.localStorage.removeItem(key);
      });
    });
  }

  function initMenuBuilder() {
    var tbody = document.querySelector('.das-menu-builder-rows');
    if (!tbody) {
      return;
    }

    var draggedRow = null;

    function rows() {
      return Array.prototype.slice.call(tbody.querySelectorAll('[data-das-menu-row]'));
    }

    function updateOrderValues() {
      rows().forEach(function (row, index) {
        var order = row.querySelector('.das-menu-order');
        if (order) {
          order.value = String((index + 1) * 10);
        }
      });
    }

    rows().forEach(function (row) {
      row.addEventListener('dragstart', function (event) {
        draggedRow = row;
        row.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', '');
      });

      row.addEventListener('dragend', function () {
        row.classList.remove('is-dragging');
        draggedRow = null;
        updateOrderValues();
      });
    });

    tbody.addEventListener('dragover', function (event) {
      if (!draggedRow) {
        return;
      }

      event.preventDefault();
      var candidates = rows().filter(function (row) {
        return row !== draggedRow;
      });
      var next = candidates.find(function (row) {
        return event.clientY < row.getBoundingClientRect().top + row.offsetHeight / 2;
      });

      tbody.insertBefore(draggedRow, next || null);
    });

    tbody.addEventListener('click', function (event) {
      var button = event.target.closest('[data-das-row-move]');
      if (!button) {
        return;
      }

      event.preventDefault();
      var row = button.closest('[data-das-menu-row]');
      if (!row) {
        return;
      }

      if (button.getAttribute('data-das-row-move') === 'up' && row.previousElementSibling) {
        tbody.insertBefore(row, row.previousElementSibling);
      }

      if (button.getAttribute('data-das-row-move') === 'down' && row.nextElementSibling) {
        tbody.insertBefore(row.nextElementSibling, row);
      }

      updateOrderValues();
    });

    updateOrderValues();
  }

  initAdminShell();
  initOfflineIndicator();
  initDraftRecovery();
  initMenuBuilder();
});
