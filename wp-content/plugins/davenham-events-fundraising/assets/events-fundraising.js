(function () {
  var banners = document.querySelectorAll('[data-def-countup]');

  if (!banners.length) {
    return;
  }

  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function formatMoney(value, symbol) {
    var number = Number(value || 0);
    try {
      return symbol + new Intl.NumberFormat(document.documentElement.lang || 'en-GB', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(number);
    } catch (error) {
      return symbol + number.toFixed(2);
    }
  }

  banners.forEach(function (banner) {
    var raised = parseFloat(banner.getAttribute('data-raised') || '0');
    var target = parseFloat(banner.getAttribute('data-target') || '0');
    var symbol = banner.getAttribute('data-symbol') || '';
    var label = banner.querySelector('[data-def-raised-label]');
    var duration = prefersReducedMotion ? 0 : 1250;
    var startTime = null;

    function render(value) {
      var progress = target > 0 ? Math.min(100, Math.max(0, (value / target) * 100)) : 0;
      banner.style.setProperty('--def-progress', progress.toFixed(2) + '%');
      if (label) {
        label.textContent = formatMoney(value, symbol);
      }
      if (target > 0 && value >= target) {
        banner.classList.add('def-fundraising-banner--complete');
      }
    }

    if (!duration) {
      render(raised);
      return;
    }

    function tick(timestamp) {
      if (startTime === null) {
        startTime = timestamp;
      }

      var elapsed = timestamp - startTime;
      var ratio = Math.min(1, elapsed / duration);
      var eased = 1 - Math.pow(1 - ratio, 3);
      render(raised * eased);

      if (ratio < 1) {
        window.requestAnimationFrame(tick);
      } else {
        render(raised);
      }
    }

    render(0);
    window.requestAnimationFrame(tick);
  });
}());
