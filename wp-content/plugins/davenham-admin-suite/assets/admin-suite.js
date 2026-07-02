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

  // Custom-link add/remove and folder/icon/search behaviours all live
  // inside initMenuBuilder() — kept together so we have one place to
  // reason about the menu builder UI.

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

    var flyoutCloseTimer = null;
    function scheduleFlyoutClose() {
      if (window.matchMedia('(max-width: 782px)').matches) {
        return; // touch: rely on click / click-outside, not hover
      }
      clearTimeout(flyoutCloseTimer);
      flyoutCloseTimer = setTimeout(closeFlyouts, 260);
    }
    function cancelFlyoutClose() {
      clearTimeout(flyoutCloseTimer);
    }

    flyoutButtons.forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        openFlyout(button);
      });

      button.addEventListener('mouseenter', function () {
        if (!window.matchMedia('(max-width: 782px)').matches) {
          cancelFlyoutClose();
          openFlyout(button);
        }
      });

      // Auto-close when the pointer leaves the trigger — cancelled if it
      // moves onto the panel (below), so the menu no longer stays stuck open.
      button.addEventListener('mouseleave', scheduleFlyoutClose);
    });

    shell.querySelectorAll('.das-app-flyout').forEach(function (panel) {
      panel.addEventListener('mouseenter', cancelFlyoutClose);
      panel.addEventListener('mouseleave', scheduleFlyoutClose);
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
    var form = document.getElementById('das-menu-builder-form');
    if (!form) {
      return;
    }

    // ---- Helpers ----
    function $$(sel, root) {
      return Array.prototype.slice.call((root || form).querySelectorAll(sel));
    }
    function $1(sel, root) {
      return (root || form).querySelector(sel);
    }
    var initialSnapshot = '';

    // ---- Drag & drop across sections ----
    var draggedCard = null;
    var dropZones = $$('[data-das-drop-zone]');

    function cards() {
      return $$('[data-das-card]', form);
    }

    function updateOrderValues() {
      // Per-section order: write a 10-step sequence so the saved
      // sort is stable and sanitize_menu_items() / sanitize_custom_links()
      // re-sort cleanly. Each section's cards start at 10.
      dropZones.forEach(function (zone) {
        $$('[data-das-card]', zone).forEach(function (card, idx) {
          var order = card.querySelector('.das-mb-order');
          if (order) {
            order.value = String((idx + 1) * 10);
          }
        });
      });
      // Custom-links zone (its own container)
      var customZone = $1('[data-das-custom-rows]');
      if (customZone) {
        $$('[data-das-card]', customZone).forEach(function (card, idx) {
          var order = card.querySelector('.das-mb-order');
          if (order) {
            order.value = String((idx + 1) * 10);
          }
        });
      }
    }

    function syncPlacementFromZone(card, zoneKey) {
      // When a card lands in a new section, sync its hidden inputs.
      var select = card.querySelector('[data-das-placement-select]');
      if (select && select.value !== zoneKey) {
        select.value = zoneKey;
      }
      card.setAttribute('data-das-placement', zoneKey);
      // Show/hide folder dropdown only when placement is "admin".
      var groupSelect = card.querySelector('[data-das-group-select]');
      if (groupSelect) {
        if (zoneKey === 'admin') {
          groupSelect.removeAttribute('hidden');
        } else {
          groupSelect.setAttribute('hidden', '');
        }
      }
    }

    function refreshCounts() {
      var totals = { keep: 0, bottom: 0, admin: 0, hide: 0, custom: 0 };
      dropZones.forEach(function (zone) {
        var key = zone.closest('[data-das-bucket]').getAttribute('data-das-bucket');
        totals[key] = $$('[data-das-card]', zone).length;
        var label = zone.parentNode.querySelector('[data-das-bucket-count]');
        if (label) label.textContent = totals[key];
      });
      var customZone = $1('[data-das-custom-rows]');
      if (customZone) {
        totals.custom = $$('[data-das-card]', customZone).length;
        var customLabel = $1('[data-das-custom-count]');
        if (customLabel) customLabel.textContent = totals.custom;
      }
      $$('[data-das-count]', document.querySelector('.das-mb-counts')).forEach(function (el) {
        var k = el.getAttribute('data-das-count');
        var strong = el.querySelector('strong');
        if (strong && typeof totals[k] !== 'undefined') strong.textContent = totals[k];
      });
    }

    function ensureEmptyHint(zone) {
      var existing = zone.querySelector('.das-mb-empty');
      var hasCards = !!zone.querySelector('[data-das-card]');
      if (hasCards && existing) {
        existing.remove();
      } else if (!hasCards && !existing) {
        var p = document.createElement('p');
        p.className = 'das-mb-empty';
        p.textContent = 'Drop items here.';
        zone.appendChild(p);
      }
    }

    dropZones.forEach(function (zone) {
      zone.addEventListener('dragover', function (event) {
        if (!draggedCard) return;
        event.preventDefault();
        var afterCard = $$('[data-das-card]', zone).filter(function (c) {
          return c !== draggedCard;
        }).find(function (c) {
          var rect = c.getBoundingClientRect();
          return event.clientY < rect.top + rect.height / 2;
        });
        zone.insertBefore(draggedCard, afterCard || null);
      });

      zone.addEventListener('drop', function (event) {
        if (!draggedCard) return;
        event.preventDefault();
        var bucketKey = zone.closest('[data-das-bucket]').getAttribute('data-das-bucket');
        if (!draggedCard.hasAttribute('data-das-custom')) {
          syncPlacementFromZone(draggedCard, bucketKey);
        }
      });
    });

    // Custom-links zone is also a drop target — but only for custom cards.
    var customZone = $1('[data-das-custom-rows]');
    if (customZone) {
      customZone.addEventListener('dragover', function (event) {
        if (!draggedCard || !draggedCard.hasAttribute('data-das-custom')) return;
        event.preventDefault();
        var afterCard = $$('[data-das-card]', customZone).filter(function (c) {
          return c !== draggedCard;
        }).find(function (c) {
          var rect = c.getBoundingClientRect();
          return event.clientY < rect.top + rect.height / 2;
        });
        customZone.insertBefore(draggedCard, afterCard || null);
      });
    }

    function bindCardDrag(card) {
      card.addEventListener('dragstart', function (event) {
        draggedCard = card;
        card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        // Firefox requires data to start the drag.
        try { event.dataTransfer.setData('text/plain', card.getAttribute('data-das-slug') || ''); } catch (e) {}
      });
      card.addEventListener('dragend', function () {
        card.classList.remove('is-dragging');
        draggedCard = null;
        updateOrderValues();
        dropZones.forEach(ensureEmptyHint);
        refreshCounts();
        markDirty();
      });
    }

    cards().forEach(bindCardDrag);

    // ---- Placement <select> change moves the card to the new section ----
    form.addEventListener('change', function (event) {
      var sel = event.target.closest('[data-das-placement-select]');
      if (!sel) return;
      var card = sel.closest('[data-das-card]');
      var bucketKey = sel.value;
      var targetSection = $1('[data-das-bucket="' + bucketKey + '"] [data-das-drop-zone]');
      if (card && targetSection) {
        targetSection.appendChild(card);
        syncPlacementFromZone(card, bucketKey);
        updateOrderValues();
        dropZones.forEach(ensureEmptyHint);
        refreshCounts();
      }
    });

    // ---- Search filter ----
    var searchInput = document.getElementById('das-menu-search');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        var q = searchInput.value.trim().toLowerCase();
        $$('[data-das-card]').forEach(function (card) {
          var hay = card.getAttribute('data-das-search') || '';
          card.classList.toggle('is-filtered-out', q !== '' && hay.indexOf(q) === -1);
        });
      });
    }

    // ---- Icon popover (visual picker) ----
    var popover = document.getElementById('das-icon-popover');
    var popoverTarget = null;
    function openPopover(trigger) {
      if (!popover) return;
      popoverTarget = trigger;
      var rect = trigger.getBoundingClientRect();
      popover.hidden = false;
      // Position below the trigger, clamped to viewport.
      var top = rect.bottom + window.scrollY + 6;
      var left = rect.left + window.scrollX;
      var maxLeft = window.scrollX + document.documentElement.clientWidth - popover.offsetWidth - 12;
      if (left > maxLeft) left = maxLeft;
      popover.style.top = top + 'px';
      popover.style.left = left + 'px';
    }
    function closePopover() {
      if (popover) popover.hidden = true;
      popoverTarget = null;
    }
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-das-icon-trigger]');
      if (trigger) {
        event.preventDefault();
        if (popoverTarget === trigger) {
          closePopover();
        } else {
          openPopover(trigger);
        }
        return;
      }
      var pick = event.target.closest('.das-icon-popover__btn');
      if (pick && popoverTarget) {
        event.preventDefault();
        var iconKey = pick.getAttribute('data-das-icon-key');
        var card = popoverTarget.closest('[data-das-card]');
        if (card) {
          var hidden = card.querySelector('.das-mb-icon-value');
          if (hidden) hidden.value = iconKey;
          var glyph = popoverTarget.querySelector('.dashicons');
          var newClass = pick.querySelector('.dashicons').className;
          if (glyph) glyph.className = newClass;
          markDirty();
        }
        closePopover();
        return;
      }
      if (popover && !popover.hidden && !event.target.closest('#das-icon-popover')) {
        closePopover();
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closePopover();
    });

    // ---- Folder chips editor ----
    var foldersWrap = $1('[data-das-folder-chips]');
    var foldersMirror = document.getElementById('das-folders-mirror');

    function rebuildFoldersMirror() {
      if (!foldersMirror) return;
      var names = $$('.das-folder-chip-input', foldersWrap).map(function (i) {
        return i.value.trim();
      }).filter(Boolean);
      foldersMirror.value = names.join('\n');
      // Sync each card's group <select> options so renames are reflected.
      var optionsHtml = names.map(function (name) {
        var slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        return '<option value="' + slug + '">' + escapeHtml(name) + '</option>';
      }).join('');
      $$('.das-mb-group').forEach(function (sel) {
        var current = sel.value;
        sel.innerHTML = optionsHtml;
        if (Array.prototype.slice.call(sel.options).some(function (o) { return o.value === current; })) {
          sel.value = current;
        }
      });
    }

    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    if (foldersWrap) {
      foldersWrap.addEventListener('input', function (event) {
        if (event.target.classList.contains('das-folder-chip-input')) {
          rebuildFoldersMirror();
          markDirty();
        }
      });
      foldersWrap.addEventListener('click', function (event) {
        if (event.target.classList.contains('das-folder-chip-remove')) {
          event.preventDefault();
          var chip = event.target.closest('[data-das-folder-chip]');
          if (chip && foldersWrap.querySelectorAll('[data-das-folder-chip]').length > 1) {
            chip.remove();
            rebuildFoldersMirror();
            markDirty();
          }
        }
        if (event.target.classList.contains('das-add-folder')) {
          event.preventDefault();
          var chip = document.createElement('span');
          chip.className = 'das-folder-chip';
          chip.setAttribute('data-das-folder-chip', '');
          chip.innerHTML = '<span class="dashicons dashicons-portfolio" aria-hidden="true"></span>' +
            '<input type="text" class="das-folder-chip-input" value="New folder" aria-label="Folder name">' +
            '<button type="button" class="das-folder-chip-remove" aria-label="Remove folder">&times;</button>';
          foldersWrap.insertBefore(chip, event.target);
          var input = chip.querySelector('input');
          if (input) { input.focus(); input.select(); }
          rebuildFoldersMirror();
          markDirty();
        }
      });
    }

    // ---- Custom links: add / remove ----
    document.addEventListener('click', function (event) {
      if (event.target.classList.contains('das-add-custom-link')) {
        event.preventDefault();
        var wrap = $1('[data-das-custom-rows]');
        var tpl = document.getElementById('das-custom-link-template');
        if (!wrap || !tpl) return;
        var nextIndex = parseInt(wrap.getAttribute('data-next-index'), 10) || 0;
        var html = tpl.innerHTML.replace(/__INDEX__/g, String(nextIndex));
        var temp = document.createElement('div');
        temp.innerHTML = html.trim();
        var newCard = temp.firstChild;
        wrap.appendChild(newCard);
        wrap.setAttribute('data-next-index', String(nextIndex + 1));
        bindCardDrag(newCard);
        refreshCounts();
        markDirty();
        var firstInput = newCard.querySelector('input[type="text"]');
        if (firstInput) firstInput.focus();
      }
      if (event.target.classList.contains('das-remove-custom-link')) {
        event.preventDefault();
        var card = event.target.closest('[data-das-card]');
        if (card) {
          card.remove();
          refreshCounts();
          markDirty();
        }
      }
    });

    // ---- Sticky save bar: dirty state + discard ----
    var status = $1('[data-das-dirty-status]');
    function snapshotForm() {
      var data = new FormData(form);
      var pairs = [];
      data.forEach(function (v, k) { pairs.push(k + '=' + v); });
      pairs.sort();
      return pairs.join('|');
    }
    function markDirty() {
      var now = snapshotForm();
      if (now !== initialSnapshot) {
        if (status) status.textContent = 'Unsaved changes';
        status && status.classList.add('is-dirty');
      } else {
        if (status) status.textContent = 'No changes yet';
        status && status.classList.remove('is-dirty');
      }
    }
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    var discardBtn = form.querySelector('.das-mb-discard');
    if (discardBtn) {
      discardBtn.addEventListener('click', function (event) {
        event.preventDefault();
        if (window.confirm('Discard unsaved changes and reload?')) {
          window.location.reload();
        }
      });
    }

    // ---- Reset to defaults confirm ----
    var resetLink = document.querySelector('.das-mb-reset[data-das-confirm]');
    if (resetLink) {
      resetLink.addEventListener('click', function (event) {
        if (!window.confirm(resetLink.getAttribute('data-das-confirm'))) {
          event.preventDefault();
        }
      });
    }

    // ---- Init ----
    updateOrderValues();
    refreshCounts();
    initialSnapshot = snapshotForm();
  }

  initAdminShell();
  initOfflineIndicator();
  initDraftRecovery();
  initMenuBuilder();
});
