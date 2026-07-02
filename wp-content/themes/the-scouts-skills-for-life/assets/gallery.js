/**
 * Gallery lightbox — progressive enhancement for .gallery-grid.
 * Click a photo to open it full-screen with its caption; navigate with the
 * arrows or keyboard, close with Esc / backdrop / the × button. No deps.
 */
(function () {
	var grid = document.querySelector('.gallery-grid');
	if (!grid) {
		return;
	}

	var cards = [].slice.call(grid.querySelectorAll('.gallery-card'));
	var items = cards
		.map(function (card) {
			var img = card.querySelector('img');
			if (!img) {
				return null;
			}
			var h = card.querySelector('h3');
			var p = card.querySelector('.gallery-card__body p');
			return {
				src: img.getAttribute('src'),
				title: h ? h.textContent.trim() : (img.getAttribute('alt') || ''),
				text: p ? p.textContent.trim() : ''
			};
		})
		.filter(Boolean);

	if (!items.length) {
		return;
	}

	var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var current = 0;

	// Build the lightbox once.
	var box = document.createElement('div');
	box.className = 'db-lightbox';
	box.setAttribute('aria-hidden', 'true');
	box.innerHTML =
		'<div class="db-lightbox__backdrop" data-close></div>' +
		'<button class="db-lightbox__btn db-lightbox__close" data-close aria-label="Close">&times;</button>' +
		'<button class="db-lightbox__btn db-lightbox__prev" data-prev aria-label="Previous">&#8249;</button>' +
		'<figure class="db-lightbox__stage">' +
		'<img class="db-lightbox__img" alt="" />' +
		'<figcaption class="db-lightbox__caption"><strong></strong><span></span></figcaption>' +
		'</figure>' +
		'<button class="db-lightbox__btn db-lightbox__next" data-next aria-label="Next">&#8250;</button>' +
		'<span class="db-lightbox__count"></span>';
	document.body.appendChild(box);

	var imgEl = box.querySelector('.db-lightbox__img');
	var capTitle = box.querySelector('.db-lightbox__caption strong');
	var capText = box.querySelector('.db-lightbox__caption span');
	var countEl = box.querySelector('.db-lightbox__count');

	function render() {
		var it = items[current];
		imgEl.src = it.src;
		imgEl.alt = it.title;
		capTitle.textContent = it.title;
		capText.textContent = it.text;
		countEl.textContent = (current + 1) + ' / ' + items.length;
	}

	function open(i) {
		current = i;
		render();
		box.classList.add('is-open');
		box.setAttribute('aria-hidden', 'false');
		document.documentElement.style.overflow = 'hidden';
	}

	function close() {
		box.classList.remove('is-open');
		box.setAttribute('aria-hidden', 'true');
		document.documentElement.style.overflow = '';
	}

	function step(dir) {
		current = (current + dir + items.length) % items.length;
		render();
	}

	// Wire the cards.
	cards.forEach(function (card, i) {
		var img = card.querySelector('img');
		if (!img) {
			return;
		}
		card.classList.add('gallery-card--zoomable');
		card.setAttribute('role', 'button');
		card.setAttribute('tabindex', '0');
		card.addEventListener('click', function () { open(i); });
		card.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				open(i);
			}
		});
	});

	box.addEventListener('click', function (e) {
		if (e.target.hasAttribute('data-close')) { close(); }
		else if (e.target.hasAttribute('data-prev')) { step(-1); }
		else if (e.target.hasAttribute('data-next')) { step(1); }
	});

	document.addEventListener('keydown', function (e) {
		if (!box.classList.contains('is-open')) {
			return;
		}
		if (e.key === 'Escape') { close(); }
		else if (e.key === 'ArrowLeft') { step(-1); }
		else if (e.key === 'ArrowRight') { step(1); }
	});
}());
