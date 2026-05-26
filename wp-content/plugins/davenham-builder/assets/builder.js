/* =============================================================
   Davenham Builder — assets/builder.js  v1.1
   Vanilla React (no JSX, no build step). Uses global wp.*
   ============================================================= */
( function () {
	'use strict';

	const { createElement: el, useState, useEffect, useCallback, useRef, Fragment } = wp.element;
	const SIMPLE_MODE_KEY = 'davenhamBuilderSimpleMode';
	const GUIDE_DISMISSED_KEY = 'davenhamBuilderGuideDismissed';
	const DEFAULT_SECTION_PADDING = 64;
	const DEFAULT_MAX_WIDTH = 1180;
	const DEFAULT_MIN_WIDTH = 0;
	const SCOUTS_MARK_URL = 'https://davenhamscouts.org.uk/wp-content/uploads/2026/04/scouts-1.png';

	// ─── REST helpers ─────────────────────────────────────────────────────────
	// We use absolute URLs so no custom middleware is needed — this eliminates
	// any possibility of double-middleware or root-URL mangling.
	const REST = ( dbConfig.restUrl || ( dbConfig.siteUrl + '/wp-json/' ) ).replace( /\/$/, '' );
	const NONCE = dbConfig.nonce;

	// Translate WordPress REST errors into copy a non-technical user can act on.
	function friendlyRestError( status, body ) {
		if ( status === 401 || status === 403 ) {
			return 'Your sign-in may have expired. Please refresh the page and try again.';
		}
		if ( status === 404 ) {
			return 'That page or item couldn\'t be found. It may have been deleted.';
		}
		if ( status >= 500 ) {
			return 'The server didn\'t respond — please try again in a moment.';
		}
		if ( body && body.message ) {
			return body.message;
		}
		return 'Something went wrong (error ' + status + '). Please try again.';
	}

	function restGet( path ) {
		return window.fetch( REST + path, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': NONCE,
				'Content-Type': 'application/json',
			},
		} ).then( function ( r ) {
			return r.json().then( function ( body ) {
				if ( ! r.ok ) {
					throw new Error( friendlyRestError( r.status, body ) );
				}
				return body;
			} ).catch( function ( err ) {
				if ( err instanceof Error ) throw err;
				throw new Error( 'Couldn\'t reach the site — check your connection and try again.' );
			} );
		} );
	}

	function restPost( path, data ) {
		return window.fetch( REST + path, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': NONCE,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( data ),
		} ).then( function ( r ) {
			return r.json().then( function ( body ) {
				if ( ! r.ok ) {
					throw new Error( friendlyRestError( r.status, body ) );
				}
				return body;
			} ).catch( function ( err ) {
				if ( err instanceof Error ) throw err;
				throw new Error( 'Couldn\'t save — check your connection and try again.' );
			} );
		} );
	}

	// ─── Block schemas ────────────────────────────────────────────────────────
	const BLOCKS = [
		// ── Hero & banners ───────────────────────────────────────────────────
		{
			type: 'davenham/hero',
			label: 'Hero Section',
			icon: '🏔️',
			category: 'hero',
			desc: 'Big full-width hero with background image & CTA buttons',
			defaults: {
				heading: 'We help young people gain the skills they need to shine bright',
				subtext: '<p>Welcome to 1st Davenham Scout Group.</p>',
				backgroundImageUrl: '',
				backgroundImageId: 0,
				buttons: [],
			},
			fields: [
				{ key: 'heading',            label: 'Heading',               type: 'textarea' },
				{ key: 'subtext',            label: 'Subtext (HTML)',         type: 'textarea', help: 'Supports <p> tags.' },
				{ key: 'backgroundImageUrl', label: 'Background Image',       type: 'image', idKey: 'backgroundImageId' },
				{ key: 'buttons',            label: 'CTA Buttons',            type: 'buttons' },
			],
		},
		{
			type: 'davenham/page-hero',
			label: 'Page Hero',
			icon: '📋',
			category: 'hero',
			desc: 'Inner-page banner — title + optional subtext',
			defaults: { heading: '', subtext: '' },
			fields: [
				{ key: 'heading', label: 'Heading (blank = page title)', type: 'text' },
				{ key: 'subtext', label: 'Subtext',                       type: 'textarea' },
			],
		},
		{
			type: 'davenham/site-notice',
			label: 'Notice Bar',
			icon: '📢',
			category: 'hero',
			desc: 'Dismissable top banner — fundraising, announcements',
			defaults: { text: '', buttonText: '', buttonUrl: '#', style: 'white', backgroundColor: '' },
			fields: [
				{ key: 'text',            label: 'Notice Text',              type: 'text' },
				{ key: 'buttonText',      label: 'Button Text',              type: 'text' },
				{ key: 'buttonUrl',       label: 'Button URL',               type: 'text' },
				{ key: 'style',           label: 'Style',                    type: 'select', options: [['white','White'],['dark','Dark Navy']] },
				{ key: 'backgroundColor', label: 'Custom BG Colour',         type: 'text', help: 'e.g. #003982 — leave blank for default' },
			],
		},

		// ── Content ───────────────────────────────────────────────────────────
		{
			type: 'davenham/welcome-section',
			label: 'Welcome Section',
			icon: '🏕️',
			category: 'content',
			desc: 'Text left, image right with scouts shapes overlay',
			defaults: {
				heading: 'Welcome to', headingHighlight: '1st Davenham',
				content: '<p>Every week young people across our sections take part in adventurous activities and learn skills for life.</p>',
				buttonText: 'Find out more', buttonUrl: '/about-us',
				imageUrl: '', imageId: 0,
			},
			fields: [
				{ key: 'heading',          label: 'Heading',                 type: 'text' },
				{ key: 'headingHighlight', label: 'Highlight (coloured span)',type: 'text' },
				{ key: 'content',          label: 'Body Text (HTML)',         type: 'textarea' },
				{ key: 'buttonText',       label: 'Button Text',              type: 'text' },
				{ key: 'buttonUrl',        label: 'Button URL',               type: 'text' },
				{ key: 'imageUrl',         label: 'Image',                    type: 'image', idKey: 'imageId' },
			],
		},
		{
			type: 'davenham/text-image',
			label: 'Text + Image',
			icon: '🖼️',
			category: 'content',
			desc: 'Flexible 50/50 split layout inside the white container',
			defaults: { heading: '', content: '', imageUrl: '', imageId: 0, imagePosition: 'right' },
			fields: [
				{ key: 'heading',       label: 'Heading',        type: 'text' },
				{ key: 'content',       label: 'Body (HTML)',     type: 'textarea' },
				{ key: 'imageUrl',      label: 'Image',           type: 'image', idKey: 'imageId' },
				{ key: 'imagePosition', label: 'Image Position',  type: 'select', options: [['right','Right'],['left','Left']] },
			],
		},
		{
			type: 'davenham/rich-text',
			label: 'Rich Text',
			icon: '📝',
			category: 'content',
			desc: 'Full-width HTML content area — great for long copy',
			defaults: { content: '<p>Enter your content here…</p>', background: 'white' },
			fields: [
				{ key: 'content',    label: 'Content (HTML)',  type: 'textarea' },
				{ key: 'background', label: 'Background',      type: 'select', options: [['white','White'],['grey','Light Grey'],['navy','Scouts Navy'],['purple','Scouts Purple']] },
			],
		},
		{
			type: 'davenham/faq',
			label: 'FAQ Accordion',
			icon: '❓',
			category: 'content',
			desc: 'Collapsible question & answer accordion',
			defaults: {
				heading: 'Frequently Asked Questions',
				items: [
					{ question: 'How do I join Scouts?', answer: 'Get in touch with us via the contact page and we\'ll let you know about upcoming joining evenings.' },
					{ question: 'What age groups do you cater for?', answer: 'We welcome young people from age 4 (Squirrels) right through to 25 (Network).' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section Heading', type: 'text' },
				{
					key: 'items',
					type: 'repeater',
					label: 'FAQ Items',
					addLabel: '＋ Add Question',
					itemLabel: ( item, i ) => item.question ? item.question.substring( 0, 40 ) + ( item.question.length > 40 ? '…' : '' ) : 'Question ' + ( i + 1 ),
					subfields: [
						{ key: 'question', type: 'text',     label: 'Question', default: '' },
						{ key: 'answer',   type: 'textarea', label: 'Answer',   default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/icon-feature-row',
			label: 'Icon Feature Row',
			icon: '⚡',
			category: 'content',
			desc: 'Row of icon + text columns — activities, values, highlights',
			defaults: { columns: [] },
			fields: [
				{ key: 'columns', label: 'Columns', type: 'columns' },
			],
		},
		{
			type: 'davenham/cta-button-row',
			label: 'CTA Button Row',
			icon: '🔘',
			category: 'content',
			desc: 'A centred row of outline / white / green CTA buttons',
			defaults: { heading: '', buttons: [] },
			fields: [
				{ key: 'heading', label: 'Heading (optional)', type: 'text' },
				{ key: 'buttons', label: 'Buttons',            type: 'buttons' },
			],
		},
		{
			type: 'davenham/leaders',
			label: 'Leaders / Team',
			icon: '👩‍✈️',
			category: 'content',
			desc: 'Photo cards for leaders, helpers, and trustees',
			defaults: {
				heading: 'Our Leaders',
				leaders: [
					{ imageUrl: '', imageId: 0, name: 'Akela', role: 'Cub Scout Leader', section: 'Cubs' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section Heading', type: 'text' },
				{
					key: 'leaders',
					type: 'repeater',
					label: 'Leaders',
					addLabel: '＋ Add Leader',
					itemLabel: ( item, i ) => item.name || 'Leader ' + ( i + 1 ),
					subfields: [
						{ key: 'imageUrl', type: 'image',    label: 'Photo',       idKey: 'imageId', default: '' },
						{ key: 'name',     type: 'text',     label: 'Name',        default: '' },
						{ key: 'role',     type: 'text',     label: 'Role / Title',default: '' },
						{ key: 'section',  type: 'text',     label: 'Section',     default: '', help: 'e.g. Cubs, Scouts, Explorers' },
					],
				},
			],
		},
		{
			type: 'davenham/sponsors',
			label: 'Sponsors / Partners',
			icon: '🤝',
			category: 'content',
			desc: 'Logo grid for sponsors, partners, and supporters',
			defaults: {
				heading: 'Our Supporters',
				logos: [],
			},
			fields: [
				{ key: 'heading', label: 'Section Heading', type: 'text' },
				{
					key: 'logos',
					type: 'repeater',
					label: 'Logos',
					addLabel: '＋ Add Sponsor',
					itemLabel: ( item, i ) => item.name || 'Sponsor ' + ( i + 1 ),
					subfields: [
						{ key: 'imageUrl', type: 'image', label: 'Logo', idKey: 'imageId', default: '' },
						{ key: 'name',     type: 'text',  label: 'Organisation Name', default: '' },
						{ key: 'url',      type: 'text',  label: 'Website URL', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/contact-info',
			label: 'Contact Info',
			icon: '📍',
			category: 'content',
			desc: 'Address, phone, email, opening hours + optional map embed',
			defaults: {
				heading: 'Get in Touch',
				address: '1st Davenham Scout HQ\nDavenham\nNorthwich\nCW9 8LF',
				phone: '',
				email: '',
				openingHours: '',
				mapEmbedUrl: '',
				buttonText: 'Send us a message',
				buttonUrl: '/contact',
			},
			fields: [
				{ key: 'heading',      label: 'Heading',           type: 'text' },
				{ key: 'address',      label: 'Address',           type: 'textarea' },
				{ key: 'phone',        label: 'Phone Number',      type: 'text' },
				{ key: 'email',        label: 'Email Address',     type: 'text' },
				{ key: 'openingHours', label: 'Opening Hours',     type: 'text', help: 'e.g. Mondays 7:00pm – 9:00pm' },
				{ key: 'mapEmbedUrl',  label: 'Google Maps Embed URL', type: 'textarea', help: 'Paste the src= URL from an embed iframe' },
				{ key: 'buttonText',   label: 'CTA Button Text',   type: 'text' },
				{ key: 'buttonUrl',    label: 'CTA Button URL',    type: 'text' },
			],
		},

		// ── Dynamic ───────────────────────────────────────────────────────────
		{
			type: 'davenham/age-section',
			label: 'Age Sections',
			icon: '🦦',
			category: 'dynamic',
			desc: 'Six age-group blocks: Squirrels → Network',
			defaults: {
				heading: 'Aged 6 to 25?',
				showSquirrels: true, showBeavers: true, showCubs: true,
				showScouts: true, showExplorers: true, showNetwork: true,
			},
			fields: [
				{ key: 'heading',       label: 'Heading',             type: 'text' },
				{ key: 'showSquirrels', label: 'Show Squirrels (4–6)',   type: 'toggle' },
				{ key: 'showBeavers',   label: 'Show Beavers (6–8)',     type: 'toggle' },
				{ key: 'showCubs',      label: 'Show Cubs (8–10½)',      type: 'toggle' },
				{ key: 'showScouts',    label: 'Show Scouts (10½–14)',   type: 'toggle' },
				{ key: 'showExplorers', label: 'Show Explorers (14–18)', type: 'toggle' },
				{ key: 'showNetwork',   label: 'Show Network (18–25)',   type: 'toggle' },
			],
		},
		{
			type: 'davenham/news-feed',
			label: 'News Feed',
			icon: '📰',
			category: 'dynamic',
			desc: 'Latest posts — auto-updates, no maintenance needed',
			defaults: { heading: 'Latest news', viewAllText: 'View all news', viewAllUrl: '/news', numberOfPosts: 4 },
			fields: [
				{ key: 'heading',       label: 'Section Heading',   type: 'text' },
				{ key: 'viewAllText',   label: '"View All" Label',   type: 'text' },
				{ key: 'viewAllUrl',    label: '"View All" URL',     type: 'text' },
				{ key: 'numberOfPosts', label: 'Number of Posts',   type: 'number', min: 1, max: 8 },
			],
		},
		{
			type: 'davenham/events-list',
			label: 'Events List',
			icon: '📅',
			category: 'dynamic',
			desc: 'Upcoming events — live from the events CPT',
			defaults: { heading: 'Upcoming events', viewAllText: 'View all events', viewAllUrl: '/events', numberOfEvents: 5 },
			fields: [
				{ key: 'heading',        label: 'Section Heading',    type: 'text' },
				{ key: 'viewAllText',    label: '"View All" Label',    type: 'text' },
				{ key: 'viewAllUrl',     label: '"View All" URL',      type: 'text' },
				{ key: 'numberOfEvents', label: 'Number of Events',   type: 'number', min: 1, max: 10 },
			],
		},

		// ── Media ─────────────────────────────────────────────────────────────
		{
			type: 'davenham/gallery',
			label: 'Photo Gallery',
			icon: '🎨',
			category: 'media',
			desc: 'Responsive image grid with optional captions',
			defaults: { heading: 'Photo Gallery', images: [] },
			fields: [
				{ key: 'heading', label: 'Section Heading', type: 'text' },
				{
					key: 'images',
					type: 'repeater',
					label: 'Photos',
					addLabel: '＋ Add Photo',
					itemLabel: ( item, i ) => item.caption || item.alt || 'Photo ' + ( i + 1 ),
					subfields: [
						{ key: 'url',     type: 'image', label: 'Photo',      idKey: 'id', default: '' },
						{ key: 'caption', type: 'text',  label: 'Caption',    default: '' },
						{ key: 'alt',     type: 'text',  label: 'Alt text',   default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/video-embed',
			label: 'Video',
			icon: '▶️',
			category: 'media',
			desc: 'YouTube or Vimeo embed with optional heading',
			defaults: { heading: '', videoUrl: '', caption: '' },
			fields: [
				{ key: 'heading',  label: 'Heading (optional)', type: 'text' },
				{ key: 'videoUrl', label: 'YouTube / Vimeo URL', type: 'text', help: 'Paste the full video URL — e.g. https://www.youtube.com/watch?v=...' },
				{ key: 'caption',  label: 'Caption',            type: 'text' },
			],
		},

		// ── Utilities ─────────────────────────────────────────────────────────
		{
			type: 'davenham/section-divider',
			label: 'Spacer / Divider',
			icon: '➖',
			category: 'utility',
			desc: 'Blank space or a thin horizontal rule between sections',
			defaults: { height: 48, style: 'blank' },
			fields: [
				{ key: 'height', label: 'Height (px)', type: 'number', min: 8, max: 200 },
				{ key: 'style',  label: 'Style',       type: 'select', options: [['blank','Blank space'],['line','Thin line'],['scouts','Scouts fleur divider']] },
			],
		},
	];

	const ADVANCED_FIELDS = [
		{ key: 'backgroundColor', label: 'Background colour', type: 'color', group: 'advanced' },
		{ key: 'textColor',       label: 'Text colour',       type: 'color', group: 'advanced' },
		{ key: 'headingColor',    label: 'Heading colour',    type: 'color', group: 'advanced' },
		{ key: 'linkColor',       label: 'Link / button text colour', type: 'color', group: 'advanced' },
		{ key: 'textAlign',       label: 'Text alignment',    type: 'select', group: 'advanced', options: [['left','Left'],['center','Center'],['right','Right']] },
		{ key: 'paddingTop',      label: 'Top spacing (px)',  type: 'number', group: 'advanced', min: 0, max: 240 },
		{ key: 'paddingBottom',   label: 'Bottom spacing (px)', type: 'number', group: 'advanced', min: 0, max: 240 },
		{ key: 'maxWidth',        label: 'Max content width (px)', type: 'number', group: 'advanced', min: 320, max: 1600 },
		{ key: 'minWidth',        label: 'Min content width (px)', type: 'number', group: 'advanced', min: 0, max: 1200 },
		{ key: 'anchorId',        label: 'Anchor ID',         type: 'text', group: 'advanced', help: 'Optional jump-link ID, e.g. join-us' },
		{ key: 'customClassName', label: 'Extra CSS classes', type: 'text', group: 'advanced', help: 'Space-separated classes for designers or developers.' },
		{ key: 'customCss',       label: 'Custom CSS',        type: 'textarea', group: 'advanced', help: 'Use raw declarations like color:#fff; or full CSS with & as the block selector, e.g. & h2 { letter-spacing:.04em; }' },
	];

	BLOCKS.push(
		{
			type: 'davenham/stats-grid',
			label: 'Stats Grid',
			icon: '📊',
			category: 'story',
			desc: 'Big numbers for impact, fundraising totals, volunteer hours, or reach.',
			defaults: {
				heading: 'Impact in numbers',
				items: [
					{ value: '120+', label: 'Young people supported', detail: 'Across Beavers, Cubs, Scouts and Explorers' },
					{ value: '40+', label: 'Volunteers', detail: 'Helping each week, behind the scenes and at events' },
					{ value: '100%', label: 'Community led', detail: 'Run locally for local families' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'items',
					label: 'Stat cards',
					type: 'repeater',
					addLabel: '＋ Add statistic',
					itemLabel: ( item, i ) => item.label || item.value || 'Statistic ' + ( i + 1 ),
					subfields: [
						{ key: 'value', type: 'text', label: 'Main number', default: '' },
						{ key: 'label', type: 'text', label: 'Label', default: '' },
						{ key: 'detail', type: 'textarea', label: 'Detail', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/testimonial-grid',
			label: 'Testimonials',
			icon: '💬',
			category: 'story',
			desc: 'Parent, young person, volunteer, or supporter quotes in a polished grid.',
			defaults: {
				heading: 'What people say',
				items: [
					{ quote: 'Scouts has helped our child grow in confidence and try things they would never normally choose.', name: 'Parent', meta: 'Beaver parent' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'items',
					label: 'Testimonials',
					type: 'repeater',
					addLabel: '＋ Add testimonial',
					itemLabel: ( item, i ) => item.name || 'Testimonial ' + ( i + 1 ),
					subfields: [
						{ key: 'quote', type: 'textarea', label: 'Quote', default: '' },
						{ key: 'name', type: 'text', label: 'Name', default: '' },
						{ key: 'meta', type: 'text', label: 'Role / context', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/quote-banner',
			label: 'Quote Banner',
			icon: '❝',
			category: 'story',
			desc: 'A strong full-width quote or mission line with source attribution.',
			defaults: { quote: 'Skills for life start with adventure, teamwork, and belonging.', attribution: '1st Davenham Scouts' },
			fields: [
				{ key: 'quote', label: 'Quote', type: 'textarea' },
				{ key: 'attribution', label: 'Attribution', type: 'text' },
			],
		},
		{
			type: 'davenham/card-grid',
			label: 'Card Grid',
			icon: '🗂️',
			category: 'content',
			desc: 'Flexible cards for sections, hall hire, parent guides, or ways to help.',
			defaults: {
				heading: 'Explore more',
				cards: [
					{ title: 'Join us', text: 'Find the right section for your young person and register your interest.', buttonText: 'Start here', buttonUrl: '/join', imageUrl: '', imageId: 0 },
					{ title: 'Volunteer', text: 'Whether you can help every week or once a term, we would love to hear from you.', buttonText: 'See roles', buttonUrl: '/volunteer', imageUrl: '', imageId: 0 },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'cards',
					label: 'Cards',
					type: 'repeater',
					addLabel: '＋ Add card',
					itemLabel: ( item, i ) => item.title || 'Card ' + ( i + 1 ),
					subfields: [
						{ key: 'title', type: 'text', label: 'Title', default: '' },
						{ key: 'text', type: 'textarea', label: 'Text', default: '' },
						{ key: 'buttonText', type: 'text', label: 'Button text', default: '' },
						{ key: 'buttonUrl', type: 'text', label: 'Button URL', default: '' },
						{ key: 'imageUrl', type: 'image', label: 'Image', idKey: 'imageId', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/downloads-list',
			label: 'Downloads List',
			icon: '📥',
			category: 'utility',
			desc: 'Useful files, forms, policies, packs, and printable resources.',
			defaults: {
				heading: 'Downloads',
				items: [
					{ title: 'Trustee information pack', meta: 'PDF', url: '/wp-content/uploads/2026/05/trustee-info-pack.pdf' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'items',
					label: 'Files',
					type: 'repeater',
					addLabel: '＋ Add file',
					itemLabel: ( item, i ) => item.title || 'File ' + ( i + 1 ),
					subfields: [
						{ key: 'title', type: 'text', label: 'Title', default: '' },
						{ key: 'meta', type: 'text', label: 'Meta / file type', default: '' },
						{ key: 'url', type: 'text', label: 'File URL', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/timeline',
			label: 'Timeline',
			icon: '🕰️',
			category: 'story',
			desc: 'Chronological milestones for history, campaigns, or project phases.',
			defaults: {
				heading: 'Our journey',
				items: [
					{ year: '1934', title: 'The group begins', text: 'Young people in Davenham start scouting activities together.' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'items',
					label: 'Timeline points',
					type: 'repeater',
					addLabel: '＋ Add milestone',
					itemLabel: ( item, i ) => item.title || item.year || 'Milestone ' + ( i + 1 ),
					subfields: [
						{ key: 'year', type: 'text', label: 'Year / date', default: '' },
						{ key: 'title', type: 'text', label: 'Title', default: '' },
						{ key: 'text', type: 'textarea', label: 'Text', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/steps',
			label: 'Steps / Process',
			icon: '🧭',
			category: 'content',
			desc: 'A clear step-by-step flow for joining, booking, donating, or applying.',
			defaults: {
				heading: 'How it works',
				items: [
					{ title: 'Tell us about you', text: 'Share the basics so we can match you to the right section or opportunity.' },
					{ title: 'We get in touch', text: 'A volunteer will explain next steps and answer any questions.' },
					{ title: 'Visit a session', text: 'Come along and see whether it feels like the right fit.' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'items',
					label: 'Steps',
					type: 'repeater',
					addLabel: '＋ Add step',
					itemLabel: ( item, i ) => item.title || 'Step ' + ( i + 1 ),
					subfields: [
						{ key: 'title', type: 'text', label: 'Title', default: '' },
						{ key: 'text', type: 'textarea', label: 'Text', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/split-cta',
			label: 'Split CTA',
			icon: '🪧',
			category: 'conversion',
			desc: 'Two strong calls to action side by side for families, volunteers, or sponsors.',
			defaults: {
				heading: 'How can we help?',
				leftTitle: 'Join as a young person',
				leftText: 'Find the right section and register interest.',
				leftButtonText: 'Join today',
				leftButtonUrl: '/join',
				rightTitle: 'Volunteer with us',
				rightText: 'Share an hour, a skill, or a season.',
				rightButtonText: 'See opportunities',
				rightButtonUrl: '/volunteer',
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{ key: 'leftTitle', label: 'Left title', type: 'text' },
				{ key: 'leftText', label: 'Left text', type: 'textarea' },
				{ key: 'leftButtonText', label: 'Left button text', type: 'text' },
				{ key: 'leftButtonUrl', label: 'Left button URL', type: 'text' },
				{ key: 'rightTitle', label: 'Right title', type: 'text' },
				{ key: 'rightText', label: 'Right text', type: 'textarea' },
				{ key: 'rightButtonText', label: 'Right button text', type: 'text' },
				{ key: 'rightButtonUrl', label: 'Right button URL', type: 'text' },
			],
		},
		{
			type: 'davenham/newsletter-signup',
			label: 'Newsletter Signup',
			icon: '✉️',
			category: 'conversion',
			desc: 'A mailing list signup block with embed area or form shortcode.',
			defaults: {
				heading: 'Stay in the loop',
				text: 'Share your email and we will only send the important stuff.',
				embedCode: '',
				buttonText: 'Join the mailing list',
				buttonUrl: '',
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{ key: 'text', label: 'Intro text', type: 'textarea' },
				{ key: 'embedCode', label: 'Embed / shortcode', type: 'textarea', help: 'Paste a form shortcode or newsletter embed HTML.' },
				{ key: 'buttonText', label: 'Fallback button text', type: 'text' },
				{ key: 'buttonUrl', label: 'Fallback button URL', type: 'text' },
			],
		},
		{
			type: 'davenham/tabs',
			label: 'Tabs',
			icon: '📑',
			category: 'content',
			desc: 'Tabbed content for sections, FAQs, programmes, or parent information.',
			defaults: {
				heading: 'Explore by topic',
				items: [
					{ title: 'Overview', content: '<p>Explain the essentials here.</p>' },
					{ title: 'What to bring', content: '<p>List uniform, kit, and any practical notes.</p>' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'items',
					label: 'Tabs',
					type: 'repeater',
					addLabel: '＋ Add tab',
					itemLabel: ( item, i ) => item.title || 'Tab ' + ( i + 1 ),
					subfields: [
						{ key: 'title', type: 'text', label: 'Tab title', default: '' },
						{ key: 'content', type: 'textarea', label: 'Tab content', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/logo-strip',
			label: 'Logo Strip',
			icon: '🏷️',
			category: 'story',
			desc: 'A simple strip of logos or badges with optional links.',
			defaults: {
				heading: 'Trusted by',
				logos: [],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'logos',
					label: 'Logos',
					type: 'repeater',
					addLabel: '＋ Add logo',
					itemLabel: ( item, i ) => item.name || 'Logo ' + ( i + 1 ),
					subfields: [
						{ key: 'imageUrl', type: 'image', label: 'Logo', idKey: 'imageId', default: '' },
						{ key: 'name', type: 'text', label: 'Name', default: '' },
						{ key: 'url', type: 'text', label: 'Website URL', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/product-grid',
			label: 'Product Grid',
			icon: '🛍',
			category: 'callout',
			desc: 'Grid of shop products by category — for fundraising, event tickets or merchandise.',
			defaults: {
				heading: 'Shop',
				subtitle: '',
				category: '',
				count: 6,
				viewAllUrl: '/shop/',
				viewAllText: 'View all products',
			},
			fields: [
				{ key: 'heading',     label: 'Section heading',    type: 'text' },
				{ key: 'subtitle',    label: 'Subtitle',           type: 'text', help: 'Optional one-liner under the heading.' },
				{ key: 'category',    label: 'Category slug',      type: 'text', help: 'Leave blank for all products. Allowed slugs: event-tickets, group-merchandise, fundraising, equipment-kit' },
				{ key: 'count',       label: 'How many to show',   type: 'number' },
				{ key: 'viewAllUrl',  label: 'View-all URL',       type: 'text' },
				{ key: 'viewAllText', label: 'View-all link text', type: 'text' },
			],
		},
		{
			type: 'davenham/featured-product',
			label: 'Featured Product',
			icon: '⭐',
			category: 'callout',
			desc: 'Promote a single shop product with image, description and link to buy.',
			defaults: {
				productId: 0,
				eyebrow: 'Featured',
				ctaText: 'View product',
			},
			fields: [
				{ key: 'productId', label: 'Product ID', type: 'number', help: 'The WooCommerce product ID. Find it under Products → All products (hover over a row).' },
				{ key: 'eyebrow',   label: 'Eyebrow label', type: 'text', help: 'Small text above the title (e.g. "Featured", "New", "Limited stock").' },
				{ key: 'ctaText',   label: 'Button label', type: 'text' },
			],
		},
		{
			type: 'davenham/event-ticket-card',
			label: 'Event Ticket Card',
			icon: '🎟',
			category: 'callout',
			desc: 'Promote a ticketed event (camp, sleepover, fair) with date, time, location, ages and Book Now link.',
			defaults: {
				eyebrow: 'Event',
				title: '',
				productId: 0,
				dateLine: '',
				timeLine: '',
				location: '',
				ageRange: '',
				included: '',
				bring: '',
				ctaText: 'Book your place',
				secondaryUrl: '',
				secondaryText: '',
			},
			fields: [
				{ key: 'eyebrow',       label: 'Eyebrow',        type: 'text', help: 'Small label above the title (e.g. "Event", "Summer Camp", "Limited spaces").' },
				{ key: 'title',         label: 'Event title',    type: 'text', help: 'Leave blank to use the linked product name.' },
				{ key: 'productId',     label: 'Product ID',     type: 'number', help: 'Link to a WooCommerce ticket product. The price + Book Now link will be pulled automatically.' },
				{ key: 'dateLine',      label: 'Date',           type: 'text', help: 'e.g. Fri 12 – Sun 14 July 2026' },
				{ key: 'timeLine',      label: 'Time',           type: 'text', help: 'e.g. 18:00 arrival, 11:00 pickup' },
				{ key: 'location',      label: 'Location',       type: 'text', help: 'e.g. Peckmill Scout Wood' },
				{ key: 'ageRange',      label: 'Age range',      type: 'text', help: 'e.g. 6 – 8 years (Beavers)' },
				{ key: 'included',      label: "What's included (one per line)", type: 'textarea', help: 'Each line becomes a bullet.' },
				{ key: 'bring',         label: 'What to bring (one per line)', type: 'textarea', help: 'Each line becomes a bullet.' },
				{ key: 'ctaText',       label: 'Primary button label', type: 'text' },
				{ key: 'secondaryUrl',  label: 'Secondary link URL (optional)', type: 'text', help: 'Useful for "More info" or "Email leaders" links.' },
				{ key: 'secondaryText', label: 'Secondary link label', type: 'text' },
			],
		},
		{
			type: 'davenham/session-times',
			label: 'Session Times',
			icon: '🕐',
			category: 'story',
			desc: 'Day / time / age / location card for a Beavers, Cubs or Scouts section page.',
			defaults: {
				day: 'Monday',
				time: '17:30 – 18:45',
				ageRange: '5¾ – 8 years',
				location: '',
				leaders: '',
			},
			fields: [
				{ key: 'day',      label: 'Day',      type: 'text', help: 'e.g. Monday' },
				{ key: 'time',     label: 'Time',     type: 'text', help: 'e.g. 17:30 – 18:45' },
				{ key: 'ageRange', label: 'Age range',type: 'text', help: 'e.g. 5¾ – 8 years' },
				{ key: 'location', label: 'Location (optional)', type: 'text', help: 'Where you meet — Peckmill Scout Wood, Bostock Farm, etc.' },
				{ key: 'leaders',  label: 'Leadership team (optional)', type: 'text', help: 'Comma-separated names — e.g. Gwenda, Dan, Thomas' },
			],
		},
		{
			type: 'davenham/key-facts',
			label: 'Key Facts',
			icon: '📌',
			category: 'story',
			desc: 'Short fact list for hall hire, policies, event essentials, or practical info.',
			defaults: {
				heading: 'Key facts',
				items: [
					{ label: 'When', value: 'Mondays, Tuesdays and Fridays' },
					{ label: 'Where', value: 'Centenary Scout Hall, Davenham' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'items',
					label: 'Facts',
					type: 'repeater',
					addLabel: '＋ Add fact',
					itemLabel: ( item, i ) => item.label || 'Fact ' + ( i + 1 ),
					subfields: [
						{ key: 'label', type: 'text', label: 'Label', default: '' },
						{ key: 'value', type: 'textarea', label: 'Value', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/promo-banner',
			label: 'Promo Banner',
			icon: '🎟️',
			category: 'conversion',
			desc: 'A bold campaign banner for tickets, fundraising, recruitment, or launches.',
			defaults: {
				eyebrow: 'Campaign',
				heading: 'Summer fundraising weekend',
				text: 'Tell people what is happening and why it matters right now.',
				buttonText: 'Learn more',
				buttonUrl: '#',
			},
			fields: [
				{ key: 'eyebrow', label: 'Eyebrow', type: 'text' },
				{ key: 'heading', label: 'Heading', type: 'text' },
				{ key: 'text', label: 'Text', type: 'textarea' },
				{ key: 'buttonText', label: 'Button text', type: 'text' },
				{ key: 'buttonUrl', label: 'Button URL', type: 'text' },
			],
		},
		{
			type: 'davenham/donation-cards',
			label: 'Donation Cards',
			icon: '💷',
			category: 'conversion',
			desc: 'Suggested giving tiers for donations, sponsorship, or project support.',
			defaults: {
				heading: 'Support our next project',
				items: [
					{ title: '£10', text: 'Helps cover craft materials and basic activity supplies.', buttonText: 'Donate £10', buttonUrl: '#' },
					{ title: '£50', text: 'Supports a whole evening of programme delivery.', buttonText: 'Donate £50', buttonUrl: '#' },
				],
			},
			fields: [
				{ key: 'heading', label: 'Section heading', type: 'text' },
				{
					key: 'items',
					label: 'Donation cards',
					type: 'repeater',
					addLabel: '＋ Add card',
					itemLabel: ( item, i ) => item.title || 'Card ' + ( i + 1 ),
					subfields: [
						{ key: 'title', type: 'text', label: 'Amount / title', default: '' },
						{ key: 'text', type: 'textarea', label: 'Text', default: '' },
						{ key: 'buttonText', type: 'text', label: 'Button text', default: '' },
						{ key: 'buttonUrl', type: 'text', label: 'Button URL', default: '' },
					],
				},
			],
		},
		{
			type: 'davenham/popup-promo',
			label: 'Popup Promo',
			icon: '🪄',
			category: 'conversion',
			desc: 'Launch a lightweight modal for signups, event promos, or urgent notices.',
			defaults: {
				heading: 'Open popup',
				buttonText: 'Open details',
				body: '<p>Use this modal for newsletter signup embeds, event details, or short campaigns.</p>',
			},
			fields: [
				{ key: 'heading', label: 'Popup title', type: 'text' },
				{ key: 'buttonText', label: 'Trigger button text', type: 'text' },
				{ key: 'body', label: 'Popup content', type: 'textarea' },
			],
		}
	);

	BLOCKS.forEach( function ( block ) {
		block.fields = ( block.fields || [] ).concat( ADVANCED_FIELDS );
		block.defaults = Object.assign( baseBlockDefaults(), block.defaults || {} );
	} );

	const PRESETS = [
		{
			key: 'homepage',
			label: 'Homepage',
			badge: '⭐ Popular',
			bestFor: 'Your main website homepage',
			desc: 'Hero, quick links, live updates, newsletter and contact sections.',
			sections: function () {
				return [
					createSection( 'davenham/site-notice', {
						text: 'A strong homepage starter for Davenham Scouts. Replace this with your latest update or campaign message.',
						buttonText: 'Contact the team',
						buttonUrl: '/contact/',
					} ),
					createSection( 'davenham/hero', {
						heading: 'Adventure, friendship, and skills for life in the heart of Davenham',
						subtext: '<p>Use this as your homepage starting point, then tailor the messaging and imagery for your group.</p>',
						buttons: [
							{ text: 'Join Scouts', url: '/join/', style: 'outline' },
							{ text: 'Volunteer', url: '/volunteer/', style: 'white' },
						],
					} ),
					createSection( 'davenham/welcome-section', {
						heading: 'Welcome to',
						headingHighlight: '1st Davenham Scouts',
					} ),
					createSection( 'davenham/age-section' ),
					createSection( 'davenham/card-grid', {
						heading: 'Quick links for families',
						items: [
							{ title: 'Parents area', text: 'Share practical information, forms, and useful notices.', buttonText: 'Open page', buttonUrl: '/parents-area/' },
							{ title: 'Events', text: 'Highlight trips, camps, and important dates.', buttonText: 'View events', buttonUrl: '/events/' },
							{ title: 'Contact', text: 'Give families one simple route to get in touch.', buttonText: 'Contact us', buttonUrl: '/contact/' },
						],
					} ),
					createSection( 'davenham/news-feed', { heading: 'Latest updates' } ),
					createSection( 'davenham/events-list', { heading: 'Upcoming events' } ),
					createSection( 'davenham/newsletter-signup' ),
					createSection( 'davenham/contact-info' ),
				];
			},
		},
		{
			key: 'section-page',
			label: 'Section page',
			badge: '🦫 Sections',
			bestFor: 'Beavers, Cubs, Scouts & Explorers',
			desc: 'Ideal for Beavers, Cubs, Scouts, volunteers, or any regular section page.',
			sections: function () {
				return [
					createSection( 'davenham/page-hero', {
						heading: 'Section page title',
						subtext: 'Use this to introduce the section, age range, and what families can expect.',
					} ),
					createSection( 'davenham/text-image', {
						heading: 'What we do',
						content: '<p>Add a warm introduction to the section, what happens each week, and what young people enjoy most.</p>',
					} ),
					createSection( 'davenham/icon-feature-row', {
						columns: [
							{ iconUrl: '', text: '<p><strong>Activities</strong><br />Games, crafts, outdoor adventures, and badges.</p>' },
							{ iconUrl: '', text: '<p><strong>Who it is for</strong><br />Explain the age range and who should come along.</p>' },
							{ iconUrl: '', text: '<p><strong>When we meet</strong><br />Add the day, time, and location.</p>' },
						],
					} ),
					createSection( 'davenham/leaders', { heading: 'Meet the team' } ),
					createSection( 'davenham/faq' ),
					createSection( 'davenham/cta-button-row', {
						heading: 'Ready to take the next step?',
						buttons: [
							{ text: 'Join this section', url: '/join/', style: 'outline' },
							{ text: 'Ask a question', url: '/contact/', style: 'green' },
						],
					} ),
				];
			},
		},
		{
			key: 'fundraiser',
			label: 'Fundraiser',
			badge: '💰 Campaigns',
			bestFor: 'Appeals & fundraising drives',
			desc: 'A strong campaign page for appeals, events, and community support.',
			sections: function () {
				return [
					createSection( 'davenham/promo-banner', {
						eyebrow: 'Fundraiser',
						heading: 'Help us make the next adventure possible',
						text: 'Explain what you are raising money for and why it matters to local young people.',
					} ),
					createSection( 'davenham/stats-grid', {
						heading: 'Why this matters',
						items: [
							{ value: '£2,500', label: 'Target', detail: 'Set your campaign goal.' },
							{ value: '120+', label: 'Young people', detail: 'Explain who benefits.' },
							{ value: '1', label: 'Community', detail: 'Show the shared local impact.' },
						],
					} ),
					createSection( 'davenham/donation-cards' ),
					createSection( 'davenham/testimonial-grid', { heading: 'Why supporters help' } ),
					createSection( 'davenham/downloads-list', { heading: 'Sponsor pack or supporting documents' } ),
					createSection( 'davenham/contact-info' ),
				];
			},
		},
		{
			key: 'hall-hire',
			label: 'Hall hire',
			badge: '🏠 Facilities',
			bestFor: 'Venue hire & booking information',
			desc: 'A practical venue page for facilities, pricing, imagery, and booking routes.',
			sections: function () {
				return [
					createSection( 'davenham/page-hero', {
						heading: 'Hall hire',
						subtext: 'Introduce the venue, location, and the kind of groups or events it suits.',
					} ),
					createSection( 'davenham/gallery', { heading: 'Take a look around' } ),
					createSection( 'davenham/key-facts', {
						heading: 'At a glance',
						items: [
							{ label: 'Capacity', value: 'Add number' },
							{ label: 'Facilities', value: 'Kitchen, toilets, parking, access, etc.' },
							{ label: 'Availability', value: 'Weekdays, evenings, or weekends' },
						],
					} ),
					createSection( 'davenham/card-grid', {
						heading: 'Useful information',
						items: [
							{ title: 'Pricing', text: 'Explain your hire rates clearly.', buttonText: 'Request pricing', buttonUrl: '/contact/' },
							{ title: 'What is included', text: 'List furniture, equipment, heating, and access notes.', buttonText: 'Ask a question', buttonUrl: '/contact/' },
							{ title: 'How to book', text: 'Set out the booking process and response time.', buttonText: 'Enquire now', buttonUrl: '/contact/' },
						],
					} ),
					createSection( 'davenham/contact-info', { heading: 'Booking enquiries' } ),
				];
			},
		},
		{
			key: 'parent-info',
			label: 'Parent info',
			badge: '👨‍👩‍👧 Families',
			bestFor: 'Notices, downloads & FAQs for parents',
			desc: 'A family-friendly layout for notices, downloads, forms, and FAQs.',
			sections: function () {
				return [
					createSection( 'davenham/site-notice', {
						text: 'Use this page for the latest practical information for parents and carers.',
						buttonText: 'Open contact page',
						buttonUrl: '/contact/',
					} ),
					createSection( 'davenham/page-hero', {
						heading: 'Parent information',
						subtext: 'Keep this page current with notices, downloads, and the questions families ask most often.',
					} ),
					createSection( 'davenham/card-grid', {
						heading: 'Quick links',
						items: [
							{ title: 'Uniform', text: 'Explain what is needed and where to buy it.', buttonText: 'View shop', buttonUrl: '/shop/' },
							{ title: 'Events', text: 'Point parents to trips, camps, and important dates.', buttonText: 'View events', buttonUrl: '/events/' },
							{ title: 'Contact', text: 'Make sure there is one simple route for questions.', buttonText: 'Contact us', buttonUrl: '/contact/' },
						],
					} ),
					createSection( 'davenham/downloads-list', { heading: 'Forms and downloads' } ),
					createSection( 'davenham/faq', { heading: 'Questions parents ask' } ),
					createSection( 'davenham/contact-info', { heading: 'Still need help?' } ),
				];
			},
		},
	];

	// ─── Category metadata ────────────────────────────────────────────────────
	const CATEGORIES = [
		{ key: 'hero',       label: 'Hero & Banners',      emoji: '🏔️' },
		{ key: 'content',    label: 'Content & Layout',    emoji: '📄' },
		{ key: 'story',      label: 'Storytelling & Trust',emoji: '✨' },
		{ key: 'dynamic',    label: 'Live Content',        emoji: '⚡' },
		{ key: 'media',      label: 'Media & Visuals',     emoji: '🎨' },
		{ key: 'conversion', label: 'Calls to Action',     emoji: '🚀' },
		{ key: 'utility',    label: 'Utilities',           emoji: '🔧' },
	];

	// ─── Utility ──────────────────────────────────────────────────────────────
	function uid() {
		return 'sec-' + Math.random().toString( 36 ).substr( 2, 8 );
	}

	function baseBlockDefaults() {
		return {
			backgroundColor: '',
			textColor: '',
			headingColor: '',
			linkColor: '',
			textAlign: 'left',
			paddingTop: DEFAULT_SECTION_PADDING,
			paddingBottom: DEFAULT_SECTION_PADDING,
			maxWidth: DEFAULT_MAX_WIDTH,
			minWidth: DEFAULT_MIN_WIDTH,
			anchorId: '',
			customClassName: '',
			customCss: '',
		};
	}

	function createSection( type, attrsOverride ) {
		const schema = BLOCKS.find( b => b.type === type );
		return {
			id: uid(),
			type,
			attrs: Object.assign( {}, schema ? schema.defaults : {}, attrsOverride || {} ),
		};
	}

	// ─── Parse WP block markup → sections array ───────────────────────────────
	function parseContent( content ) {
		if ( ! content ) return [];
		const sections = [];
		const re = /<!-- wp:(davenham\/[\w-]+)\s*(.*?)\s*\/-->/gs;
		let m;
		while ( ( m = re.exec( content ) ) !== null ) {
			const type = m[1];
			let attrs = {};
			if ( m[2] ) {
				try { attrs = JSON.parse( m[2] ); } catch ( e ) {}
			}
			const schema = BLOCKS.find( b => b.type === type );
			if ( schema ) attrs = Object.assign( {}, schema.defaults, attrs );
			sections.push( { id: uid(), type, attrs } );
		}
		return sections;
	}

	// ─── Sections array → WP block markup ────────────────────────────────────
	function buildContent( sections ) {
		return sections.map( s => {
			const json = JSON.stringify( s.attrs );
			return `<!-- wp:${ s.type } ${ json } /-->`;
		} ).join( '\n' );
	}

	// ─── Preview text for a section card ─────────────────────────────────────
	function sectionPreview( section ) {
		const a = section.attrs;
		const candidates = [ a.heading, a.text, a.content ];
		for ( const c of candidates ) {
			if ( typeof c === 'string' && c.trim() ) {
				return c.replace( /<[^>]+>/g, '' ).substring( 0, 55 ) + ( c.length > 55 ? '…' : '' );
			}
		}
		const schema = BLOCKS.find( b => b.type === section.type );
		return schema ? schema.desc : '';
	}

	// ─── WP Media Picker ──────────────────────────────────────────────────────
	function openMediaPicker( onSelect ) {
		if ( ! window.wp || ! wp.media ) return;
		const frame = wp.media( {
			title: 'Select Image',
			button: { text: 'Use this image' },
			multiple: false,
		} );
		frame.on( 'select', function () {
			const att = frame.state().get( 'selection' ).first().toJSON();
			onSelect( att.url, att.id );
		} );
		frame.open();
	}

	// ─── Convert a video URL → embed URL ─────────────────────────────────────
	function toEmbedUrl( url ) {
		if ( ! url ) return '';
		// YouTube
		const ytMatch = url.match( /(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/ );
		if ( ytMatch ) return 'https://www.youtube.com/embed/' + ytMatch[1];
		// Vimeo
		const vmMatch = url.match( /vimeo\.com\/(\d+)/ );
		if ( vmMatch ) return 'https://player.vimeo.com/video/' + vmMatch[1];
		// Already an embed URL
		if ( url.includes( '/embed/' ) || url.includes( 'player.vimeo' ) ) return url;
		return url;
	}

	function wrapSelectionWithTag( textarea, currentValue, before, after, fallbackText ) {
		const input = textarea && textarea.current;
		const safeValue = currentValue || '';
		if ( ! input ) {
			return safeValue + before + ( fallbackText || '' ) + after;
		}

		const start = input.selectionStart || 0;
		const end = input.selectionEnd || 0;
		const selected = safeValue.substring( start, end ) || ( fallbackText || '' );
		const nextValue = safeValue.substring( 0, start ) + before + selected + after + safeValue.substring( end );

		window.requestAnimationFrame( function () {
			input.focus();
			const cursor = start + before.length + selected.length + after.length;
			input.setSelectionRange( cursor, cursor );
		} );

		return nextValue;
	}

	function htmlEscape( value ) {
		return String( value || '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function RichTextarea( { value, onChange, rows, placeholder } ) {
		const ref = useRef( null );
		const safeValue = value || '';
		const apply = function ( builder ) {
			onChange( builder( safeValue, ref ) );
		};

		return el( 'div', { className: 'db-richtext' },
			el( 'div', { className: 'db-richtext__toolbar' },
				el( 'button', { type: 'button', className: 'db-richtext__tool', onClick: () => apply( ( current, textarea ) => wrapSelectionWithTag( textarea, current, '<p>', '</p>', 'Paragraph text' ) ) }, 'P' ),
				el( 'button', { type: 'button', className: 'db-richtext__tool', onClick: () => apply( ( current, textarea ) => wrapSelectionWithTag( textarea, current, '<h2>', '</h2>', 'Heading' ) ) }, 'H2' ),
				el( 'button', { type: 'button', className: 'db-richtext__tool', onClick: () => apply( ( current, textarea ) => wrapSelectionWithTag( textarea, current, '<h3>', '</h3>', 'Subheading' ) ) }, 'H3' ),
				el( 'button', { type: 'button', className: 'db-richtext__tool', onClick: () => apply( ( current, textarea ) => wrapSelectionWithTag( textarea, current, '<strong>', '</strong>', 'Bold text' ) ) }, 'Bold' ),
				el( 'button', { type: 'button', className: 'db-richtext__tool', onClick: () => apply( ( current, textarea ) => wrapSelectionWithTag( textarea, current, '<em>', '</em>', 'Italic text' ) ) }, 'Italic' ),
				el( 'button', {
					type: 'button',
					className: 'db-richtext__tool',
					onClick: () => apply( function ( current, textarea ) {
						const href = window.prompt( 'Link URL', 'https://' );
						if ( ! href ) return current;
						return wrapSelectionWithTag( textarea, current, '<a href="' + htmlEscape( href ) + '">', '</a>', 'Link text' );
					} ),
				}, 'Link' ),
				el( 'button', {
					type: 'button',
					className: 'db-richtext__tool',
					onClick: () => apply( ( current, textarea ) => wrapSelectionWithTag( textarea, current, '<ul><li>', '</li></ul>', 'List item' ) ),
				}, 'List' )
			),
			el( 'textarea', {
				ref,
				rows: rows || 5,
				value: safeValue,
				placeholder: placeholder || '',
				onChange: e => onChange( e.target.value ),
			} ),
			el( 'div', { className: 'db-richtext__hint' }, 'Formatting helpers insert HTML. You can still edit the markup directly if you want full control.' )
		);
	}

	// ─── Field Input ──────────────────────────────────────────────────────────
	function FieldInput( { fieldDef, value, onChange, attrs, onAttrsChange, simpleMode } ) {
		const { type, key: fkey, label, help, options, min, max, idKey, subfields, addLabel, itemLabel } = fieldDef;

		if ( type === 'text' || type === 'url' ) {
			return el( 'div', { className: 'db-field' },
				el( 'label', null, label ),
				el( 'input', {
					type: 'text',
					value: value || '',
					onChange: e => onChange( e.target.value ),
				} ),
				help && el( 'span', { className: 'db-field__help' }, help )
			);
		}

		if ( type === 'textarea' ) {
			return el( 'div', { className: 'db-field' },
				el( 'label', null, label ),
				el( RichTextarea, {
					rows: 4,
					value: value || '',
					onChange: onChange,
				} ),
				help && el( 'span', { className: 'db-field__help' }, help )
			);
		}

		if ( type === 'color' ) {
			return el( 'div', { className: 'db-field db-field--color' },
				el( 'label', null, label ),
				el( 'div', { className: 'db-color-input' },
					el( 'input', {
						type: 'color',
						value: value || '#000000',
						onChange: e => onChange( e.target.value ),
					} ),
					el( 'input', {
						type: 'text',
						value: value || '',
						placeholder: '#003982',
						onChange: e => onChange( e.target.value ),
					} )
				),
				help && el( 'span', { className: 'db-field__help' }, help )
			);
		}

		if ( type === 'number' ) {
			return el( 'div', { className: 'db-field' },
				el( 'label', null, label ),
				el( 'input', {
					type: 'number',
					value: value === undefined ? '' : value,
					min, max,
					onChange: e => onChange( parseInt( e.target.value, 10 ) || 0 ),
				} )
			);
		}

		if ( type === 'select' ) {
			return el( 'div', { className: 'db-field' },
				el( 'label', null, label ),
				el( 'select', {
					value: value || '',
					onChange: e => onChange( e.target.value ),
				}, ( options || [] ).map( ( [val, lbl] ) =>
					el( 'option', { key: val, value: val }, lbl )
				) )
			);
		}

		if ( type === 'toggle' ) {
			return el( 'div', { className: 'db-toggle' },
				el( 'span', { className: 'db-toggle__label' }, label ),
				el( 'label', { className: 'db-toggle__switch' },
					el( 'input', {
						type: 'checkbox',
						checked: !! value,
						onChange: e => onChange( e.target.checked ),
					} ),
					el( 'span', { className: 'db-toggle__track' } )
				)
			);
		}

		if ( type === 'image' ) {
			return el( 'div', { className: 'db-field' },
				el( 'label', null, label ),
				value && el( 'img', { className: 'db-field__image-preview', src: value, alt: '' } ),
				el( 'button', {
					className: 'db-field__img-btn',
					type: 'button',
					onClick: () => openMediaPicker( ( url, id ) => {
						onAttrsChange( { [ fkey ]: url, [ idKey || ( fkey + 'Id' ) ]: id } );
					} ),
				}, value ? '🔄 Change Image' : '📁 Select from Media Library' ),
				value && el( 'button', {
					style: { marginTop: 4, width: '100%', padding: '6px', background: 'none', border: '1px solid #eee', borderRadius: 6, color: '#aaa', fontSize: 11, cursor: 'pointer' },
					type: 'button',
					onClick: () => onAttrsChange( { [ fkey ]: '', [ idKey || ( fkey + 'Id' ) ]: 0 } ),
				}, '✕ Remove image' )
			);
		}

		// ── Generic repeater ─────────────────────────────────────────────────
		if ( type === 'repeater' ) {
			const items = Array.isArray( value ) ? value : [];

			const patchItemAt = ( i, patch ) => {
				const next = items.slice();
				next[ i ] = { ...items[ i ], ...patch };
				onChange( next );
			};

			return el( 'div', { className: 'db-field' },
				el( 'label', null, label ),
				items.map( ( item, i ) => {
					const title = itemLabel ? itemLabel( item, i ) : 'Item ' + ( i + 1 );
					return el( 'div', { key: i, className: 'db-repeater__item' },
						el( 'div', { className: 'db-repeater__item-header' },
							el( 'span', { className: 'db-repeater__item-title' }, title ),
							! simpleMode && el( 'button', {
								type: 'button',
								className: 'db-repeater__remove',
								onClick: () => onChange( items.filter( ( _, j ) => j !== i ) ),
							}, '✕ Remove' )
						),
						( subfields || [] ).map( sf =>
							el( FieldInput, {
								key: sf.key,
								fieldDef: sf,
								value: item[ sf.key ],
								onChange: val => patchItemAt( i, { [ sf.key ]: val } ),
								attrs: item,
								onAttrsChange: patch => patchItemAt( i, patch ),
								simpleMode,
							} )
						)
					);
				} ),
				! simpleMode && el( 'button', {
					type: 'button',
					className: 'db-repeater__add',
					onClick: () => {
						const blank = {};
						( subfields || [] ).forEach( sf => { blank[ sf.key ] = sf.default !== undefined ? sf.default : ''; } );
						onChange( [ ...items, blank ] );
					},
				}, addLabel || '＋ Add Item' )
			);
		}

		// ── Buttons repeater ─────────────────────────────────────────────────
		if ( type === 'buttons' ) {
			const btns = Array.isArray( value ) ? value : [];
			const styleOpts = [ [ 'outline', 'Outline (Purple)' ], [ 'white', 'White' ], [ 'green', 'Green' ] ];
			return el( 'div', { className: 'db-field' },
				el( 'label', null, label ),
				btns.map( ( btn, i ) =>
					el( 'div', { key: i, className: 'db-repeater__item' },
						el( 'div', { className: 'db-repeater__item-header' },
							el( 'span', { className: 'db-repeater__item-title' }, 'Button ' + ( i + 1 ) + ( btn.text ? ' — ' + btn.text : '' ) ),
						! simpleMode && el( 'button', {
							type: 'button', className: 'db-repeater__remove',
							onClick: () => onChange( btns.filter( ( _, j ) => j !== i ) ),
						}, '✕ Remove' )
						),
						el( 'div', { className: 'db-field' },
							el( 'label', null, 'Label' ),
							el( 'input', { type: 'text', value: btn.text || '', onChange: e => { const n = btns.slice(); n[i] = { ...btn, text: e.target.value }; onChange( n ); } } )
						),
						el( 'div', { className: 'db-field' },
							el( 'label', null, 'URL' ),
							el( 'input', { type: 'text', value: btn.url || '', onChange: e => { const n = btns.slice(); n[i] = { ...btn, url: e.target.value }; onChange( n ); } } )
						),
						el( 'div', { className: 'db-field' },
							el( 'label', null, 'Style' ),
							el( 'select', { value: btn.style || 'outline', onChange: e => { const n = btns.slice(); n[i] = { ...btn, style: e.target.value }; onChange( n ); } },
								styleOpts.map( ( [v, l] ) => el( 'option', { key: v, value: v }, l ) )
							)
						)
					)
				),
				! simpleMode && el( 'button', {
					type: 'button', className: 'db-repeater__add',
					onClick: () => onChange( [ ...btns, { text: 'Click here', url: '/', style: 'outline' } ] ),
				}, '＋ Add Button' )
			);
		}

		// ── Columns repeater ─────────────────────────────────────────────────
		if ( type === 'columns' ) {
			const cols = Array.isArray( value ) ? value : [];
			return el( 'div', { className: 'db-field' },
				el( 'label', null, label ),
				cols.map( ( col, i ) =>
					el( 'div', { key: i, className: 'db-repeater__item' },
						el( 'div', { className: 'db-repeater__item-header' },
							el( 'span', { className: 'db-repeater__item-title' }, 'Column ' + ( i + 1 ) ),
						! simpleMode && el( 'button', {
							type: 'button', className: 'db-repeater__remove',
							onClick: () => onChange( cols.filter( ( _, j ) => j !== i ) ),
						}, '✕ Remove' )
						),
						el( 'div', { className: 'db-field' },
							el( 'label', null, 'Icon URL' ),
							el( 'input', { type: 'text', value: col.iconUrl || '', onChange: e => { const n = cols.slice(); n[i] = { ...col, iconUrl: e.target.value }; onChange( n ); } } )
						),
						el( 'div', { className: 'db-field' },
							el( 'label', null, 'Text / HTML' ),
							el( 'textarea', { rows: 3, value: col.text || '', onChange: e => { const n = cols.slice(); n[i] = { ...col, text: e.target.value }; onChange( n ); } } )
						)
					)
				),
				! simpleMode && el( 'button', {
					type: 'button', className: 'db-repeater__add',
					onClick: () => onChange( [ ...cols, { iconUrl: '', text: '' } ] ),
				}, '＋ Add Column' )
			);
		}

		return null;
	}

	// ─── Settings Panel ───────────────────────────────────────────────────────
	function SettingsPanel( { section, onUpdate, onBack, simpleMode } ) {
		const schema = BLOCKS.find( b => b.type === section.type );
		if ( ! schema ) return null;

		const setAttr  = ( key, val ) => onUpdate( { ...section.attrs, [ key ]: val } );
		const setAttrs = ( patch )    => onUpdate( { ...section.attrs, ...patch } );
		const standardFields = ( simpleMode ? schema.fields.filter( function ( field ) {
			return field.group !== 'advanced';
		} ) : schema.fields.filter( field => field.group !== 'advanced' ) );
		const advancedFields = schema.fields.filter( field => field.group === 'advanced' );

		return el( 'div', { className: 'db-settings' },
			el( 'div', { className: 'db-settings__topbar' },
				el( 'button', {
					type: 'button',
					className: 'db-settings__back',
					onClick: onBack,
					'aria-label': 'Back to all blocks',
				},
					el( 'span', { className: 'db-settings__back-arrow', 'aria-hidden': 'true' }, '←' ),
					' All blocks'
				)
			),
			el( 'div', { className: 'db-sidebar__header db-sidebar__header--settings' },
				el( 'div', { className: 'db-settings__header-copy' },
					el( 'span', { className: 'db-settings__section-tag' }, schema.icon, ' ', schema.label ),
					el( 'p', { className: 'db-settings__intro' }, schema.desc )
				)
			),
			el( 'div', { className: 'db-sidebar__scroll' },
				standardFields.map( field =>
					el( FieldInput, {
						key: field.key,
						fieldDef: field,
						value: section.attrs[ field.key ],
						onChange: val => setAttr( field.key, val ),
						attrs: section.attrs,
						onAttrsChange: setAttrs,
						simpleMode,
					} )
				),
				! simpleMode && advancedFields.length > 0 && el( 'details', { className: 'db-advanced' },
					el( 'summary', { className: 'db-advanced__summary' }, 'Advanced styles & CSS override' ),
					el( 'div', { className: 'db-advanced__body' },
						el( 'p', { className: 'db-advanced__help' }, 'Use this area for design control without overwhelming day-to-day editors.' ),
						advancedFields.map( field =>
							el( FieldInput, {
								key: field.key,
								fieldDef: field,
								value: section.attrs[ field.key ],
								onChange: val => setAttr( field.key, val ),
								attrs: section.attrs,
								onAttrsChange: setAttrs,
								simpleMode,
							} )
						)
					)
				)
			)
		);
	}

	// ─── Component Library ────────────────────────────────────────────────────
	function ComponentLibrary( { onAdd, insertAfterLabel, insertAtIndex, onClearInsertAt, onApplyPreset, simpleMode, guideDismissed, onDismissGuide, hasSections, hasPage, onCreateNewPage } ) {
		const [ search, setSearch ] = useState( '' );
		// In "insert at index" mode we hide templates (they replace everything).
		const insertingAt = insertAtIndex !== null && insertAtIndex !== undefined;
		const [ libraryMode, setLibraryMode ] = useState( insertingAt ? 'sections' : 'templates' );
		useEffect( function () {
			if ( insertingAt && libraryMode !== 'sections' ) setLibraryMode( 'sections' );
		}, [ insertingAt ] );
		const canAddSections = ! simpleMode || ! hasSections;
		const q = search.toLowerCase();
		const filtered = q
			? BLOCKS.filter( b => b.label.toLowerCase().includes( q ) || b.desc.toLowerCase().includes( q ) )
			: BLOCKS;

		// Guard: if no page is loaded, clicking anything in the library must
		// first prompt the editor to create a page (otherwise the click looks
		// silent because the canvas isn't rendered until a page exists).
		const guardedAdd = function ( type ) {
			if ( ! hasPage ) { if ( onCreateNewPage ) onCreateNewPage(); return; }
			onAdd( type );
		};
		const guardedPreset = function ( preset ) {
			if ( ! hasPage ) { if ( onCreateNewPage ) onCreateNewPage(); return; }
			onApplyPreset( preset );
		};

		const grouped = CATEGORIES.map( cat => ( {
			...cat,
			blocks: filtered.filter( b => b.category === cat.key ),
		} ) ).filter( cat => cat.blocks.length > 0 );

		return el( Fragment, null,
			el( 'div', { className: 'db-sidebar__header' },
				el( 'div', { className: 'db-sidebar__header-copy' },
					el( 'span', { className: 'db-sidebar__title' }, 'Build your page' ),
					el( 'p', { className: 'db-sidebar__subtitle' }, simpleMode && hasSections ? 'Simple mode is on. Pick an existing section on the canvas to swap text and images safely.' : 'Pick a ready-made section and customise it in the settings panel. Use the search to find sections by name.' )
				),
				insertAfterLabel && el( 'span', {
					style: { fontSize: 10, color: '#777', fontWeight: 600, whiteSpace: 'nowrap', marginLeft: 8, background: '#f3eaff', padding: '4px 8px', borderRadius: 999 },
					title: 'New section will be inserted after the selected one',
				}, '↓ after "' + insertAfterLabel + '"' )
			),
			insertAtIndex !== null && insertAtIndex !== undefined && el( 'div', { className: 'db-insert-banner' },
				el( 'span', null, '↳ Inserting a section at position ' + ( insertAtIndex + 1 ) + '. Pick a block below.' ),
				el( 'button', {
					type: 'button',
					className: 'db-insert-banner__cancel',
					onClick: onClearInsertAt,
				}, 'Cancel' )
			),
			! hasPage && el( 'div', { className: 'db-nopage-banner' },
				el( 'div', { className: 'db-nopage-banner__copy' },
					el( 'strong', null, 'Pick a page to start editing' ),
					el( 'span', null, 'Browse blocks below, or create a new page. Clicking a block will prompt you to create one.' )
				),
				el( 'button', {
					type: 'button',
					className: 'db-nopage-banner__btn',
					onClick: onCreateNewPage,
				}, '＋ New page' )
			),
			! guideDismissed && el( 'div', { className: 'db-guide' },
				el( 'div', { className: 'db-guide__header' },
					el( 'strong', null, 'How to use this builder' ),
					el( 'button', { type: 'button', className: 'db-guide__dismiss', onClick: onDismissGuide }, 'Dismiss' )
				),
				el( 'ol', { className: 'db-guide__steps' },
					el( 'li', null, 'Choose a page from the top bar or create a new one.' ),
					el( 'li', null, 'Apply a template below for a quick head start, then tweak each section.' ),
					el( 'li', null, 'Turn on Simple mode to safely edit text and images without changing the layout.' ),
					el( 'li', null, 'Visit Site Settings to update your logo, footer, cookie notice, and newsletter.' )
				)
			),
			canAddSections && ! insertingAt && el( 'div', { className: 'db-library-switcher' },
				el( 'button', {
					type: 'button',
					className: 'db-library-switcher__tab' + ( libraryMode === 'templates' ? ' is-active' : '' ),
					onClick: function () { setLibraryMode( 'templates' ); }
				}, 'Templates' ),
				el( 'button', {
					type: 'button',
					className: 'db-library-switcher__tab' + ( libraryMode === 'sections' ? ' is-active' : '' ),
					onClick: function () { setLibraryMode( 'sections' ); }
				}, 'Sections' )
			),
			simpleMode && hasSections && el( 'div', { className: 'db-guide db-guide--simple' },
				el( 'div', { className: 'db-guide__header' },
					el( 'strong', null, 'Simple mode' )
				),
				el( 'p', { className: 'db-sidebar__subtitle', style: { marginTop: 0 } }, 'Select a section on the canvas to edit text, links, and images without changing the page structure.' )
			),
			canAddSections && libraryMode === 'templates' && el( 'div', { className: 'db-preset-list' },
				el( 'div', { className: 'db-preset-list__header' }, 'Start with a template' ),
				el( 'p', { className: 'db-sidebar__subtitle', style: { marginTop: 0 } }, 'Apply a full starter layout in one click.' ),
				PRESETS.map( function ( preset ) {
					return el( 'button', {
						key: preset.key,
						type: 'button',
						className: 'db-preset-card',
						onClick: function () { guardedPreset( preset ); },
					},
						el( 'div', { className: 'db-preset-card__header' },
							el( 'strong', null, preset.label ),
							preset.badge ? el( 'span', { className: 'db-preset-card__badge' }, preset.badge ) : null
						),
						preset.bestFor ? el( 'em', { className: 'db-preset-card__best-for' }, 'Best for: ' + preset.bestFor ) : null,
						el( 'span', null, preset.desc )
					);
				} )
			),
			canAddSections && libraryMode === 'sections' && el( Fragment, null,
				el( 'div', { className: 'db-lib__search-wrap' },
					el( 'input', {
						type: 'text',
						className: 'db-lib__search',
						placeholder: '🔍  Search sections…',
						value: search,
						onChange: e => setSearch( e.target.value ),
					} )
				),
				el( 'div', { className: 'db-sidebar__scroll' },
					grouped.map( cat =>
						el( 'div', { key: cat.key, className: 'db-lib__category' },
							el( 'div', { className: 'db-lib__category-header' },
								el( 'div', { className: 'db-lib__category-label' }, cat.emoji, ' ', cat.label ),
								el( 'span', { className: 'db-lib__category-count' }, cat.blocks.length )
							),
							el( 'div', { className: 'db-lib__grid' },
								cat.blocks.map( block =>
									el( 'div', {
										key: block.type,
										className: 'db-lib__card',
										onClick: () => guardedAdd( block.type ),
										title: block.desc,
									},
										el( 'span', { className: 'db-lib__icon' }, block.icon ),
										el( 'div', { className: 'db-lib__body' },
											el( 'span', { className: 'db-lib__label' }, block.label ),
											el( 'span', { className: 'db-lib__desc' }, block.desc )
										)
									)
								)
							)
						)
					),
					grouped.length === 0 && el( 'p', { style: { color: '#aaa', fontSize: 12, textAlign: 'center', padding: '20px 0' } }, 'No sections match "' + search + '"' )
				)
			)
		);
	}

	// ─── Section Card ─────────────────────────────────────────────────────────
	function SectionCard( { section, index, isSelected, onSelect, onMoveUp, onMoveDown, onDelete, onDuplicate, canMoveUp, canMoveDown, simpleMode } ) {
		const schema = BLOCKS.find( b => b.type === section.type );
		const preview = sectionPreview( section );

		return el( 'div', {
			className: 'db-section' + ( isSelected ? ' db-section--selected' : '' ),
			'data-type': section.type,
			onClick: e => { if ( ! e.target.closest( '.db-section__btn' ) ) onSelect(); },
		},
			el( 'div', { className: 'db-section__stripe' } ),
			el( 'div', { className: 'db-section__header' },
				el( 'span', { className: 'db-section__index', 'aria-hidden': 'true' }, String( index + 1 ) ),
				el( 'div', { className: 'db-section__icon' }, schema ? schema.icon : '📦' ),
				el( 'div', { className: 'db-section__info' },
					el( 'span', { className: 'db-section__name' }, schema ? schema.label : section.type ),
					el( 'span', { className: 'db-section__preview' }, preview )
				),
				! simpleMode && el( 'div', { className: 'db-section__actions', onClick: e => e.stopPropagation() },
					el( 'button', { type: 'button', className: 'db-section__btn db-section__btn--up',        title: 'Move up',        'aria-label': 'Move section up',        disabled: ! canMoveUp,   onClick: onMoveUp        }, '↑' ),
					el( 'button', { type: 'button', className: 'db-section__btn db-section__btn--down',      title: 'Move down',      'aria-label': 'Move section down',      disabled: ! canMoveDown, onClick: onMoveDown      }, '↓' ),
					el( 'button', { type: 'button', className: 'db-section__btn db-section__btn--duplicate', title: 'Duplicate',      'aria-label': 'Duplicate this section', onClick: onDuplicate                    }, '⎘' ),
					el( 'button', { type: 'button', className: 'db-section__btn db-section__btn--delete',    title: 'Delete',         'aria-label': 'Delete this section',    onClick: () => { if ( window.confirm( 'Delete this section? This can\'t be undone unless you discard your unsaved changes.' ) ) onDelete(); } }, '🗑' )
				)
			)
		);
	}

	// Inline "+ Add section here" rail between (and around) sections.
	function InsertRail( { index, onClick, label } ) {
		return el( 'button', {
			type: 'button',
			className: 'db-insert-rail',
			onClick: function () { onClick( index ); },
			'aria-label': 'Insert a section at position ' + ( index + 1 ),
			title: 'Click to add a section here',
		},
			el( 'span', { className: 'db-insert-rail__line' } ),
			el( 'span', { className: 'db-insert-rail__btn' }, '＋ ', label || 'Add section' ),
			el( 'span', { className: 'db-insert-rail__line' } )
		);
	}

	// ─── Canvas ───────────────────────────────────────────────────────────────
	function Canvas( { sections, selectedId, onSelect, onMove, onDelete, onDuplicate, onInsertAt, simpleMode, viewportMode } ) {
		const viewportClass = viewportMode ? ' db-canvas--' + viewportMode : '';

		if ( sections.length === 0 ) {
			return el( 'div', { className: 'db-canvas' + viewportClass },
				el( 'div', { className: 'db-canvas__inner' },
					el( 'div', { className: 'db-canvas__empty' },
						el( 'div', { className: 'db-canvas__empty-icon' },
							el( 'img', { src: SCOUTS_MARK_URL, alt: '', 'aria-hidden': 'true', className: 'db-canvas__empty-logo' } )
						),
						el( 'h3', null, 'Start building your page' ),
						el( 'p', null, 'Pick a starter template for a head start, or add an individual section. You can rearrange, duplicate or remove any section later.' ),
						el( 'div', { className: 'db-canvas__empty-actions' },
							el( 'button', {
								type: 'button',
								className: 'db-btn db-btn--save',
								onClick: function () { onInsertAt( 0 ); },
							}, '＋ Add a section' )
						),
						el( 'p', { className: 'db-canvas__empty-hint' }, 'Tip: press Cmd/Ctrl + S any time to save.' )
					)
				)
			);
		}

		return el( 'div', { className: 'db-canvas' + viewportClass },
			el( 'div', { className: 'db-canvas__inner' },
				simpleMode ? null : el( InsertRail, { index: 0, onClick: onInsertAt, label: 'Add section at top' } ),
				sections.map( ( section, i ) =>
					el( Fragment, { key: section.id },
						el( SectionCard, {
							section,
							index: i,
							isSelected: section.id === selectedId,
							onSelect:    () => onSelect( section.id ),
							onMoveUp:    () => onMove( i, i - 1 ),
							onMoveDown:  () => onMove( i, i + 1 ),
							onDelete:    () => onDelete( section.id ),
							onDuplicate: () => onDuplicate( section.id ),
							canMoveUp:   i > 0,
							canMoveDown: i < sections.length - 1,
							simpleMode,
						} ),
						simpleMode ? null : el( InsertRail, { index: i + 1, onClick: onInsertAt, label: i === sections.length - 1 ? 'Add section at end' : 'Add section here' } )
					)
				)
			)
		);
	}

	// ─── New Page Modal ───────────────────────────────────────────────────────
	function NewPageModal( { onClose, onCreated } ) {
		const [ title,   setTitle   ] = useState( '' );
		const [ busy,    setBusy    ] = useState( false );
		const [ errMsg,  setErrMsg  ] = useState( '' );
		const inputRef = useRef( null );

		useEffect( () => {
			if ( inputRef.current ) inputRef.current.focus();
		}, [] );

		function create() {
			if ( ! title.trim() ) { setErrMsg( 'Please enter a page title' ); return; }
			setBusy( true );
			setErrMsg( '' );
			restPost( '/wp/v2/pages', { title: title.trim(), status: 'draft' } )
				.then( function ( page ) { onCreated( page ); } )
				.catch( function ( err ) {
					setBusy( false );
					setErrMsg( err && err.message ? err.message : 'Could not create page — try again' );
				} );
		}

		// Close on Escape key (standard modal behaviour)
		useEffect( function () {
			function onKey( e ) { if ( e.key === 'Escape' ) onClose(); }
			document.addEventListener( 'keydown', onKey );
			return function () { document.removeEventListener( 'keydown', onKey ); };
		}, [ onClose ] );

		return el( 'div', {
			className: 'db-modal-overlay',
			onClick: e => { if ( e.target === e.currentTarget ) onClose(); },
		},
			el( 'div', {
				className: 'db-modal',
				role: 'dialog',
				'aria-modal': 'true',
				'aria-labelledby': 'db-new-page-title',
			},
				el( 'div', { className: 'db-modal__header' },
					el( 'h3', { className: 'db-modal__title', id: 'db-new-page-title' }, '＋ Create New Page' ),
					el( 'button', {
						className: 'db-modal__close',
						onClick: onClose,
						'aria-label': 'Close',
						type: 'button',
					}, '✕' )
				),
				el( 'div', { className: 'db-modal__body' },
					el( 'label', { className: 'db-modal__label', htmlFor: 'db-new-page-title-input' }, 'Page Title' ),
					el( 'input', {
						ref: inputRef,
						id: 'db-new-page-title-input',
						type: 'text',
						className: 'db-modal__input',
						placeholder: 'e.g. About Us',
						value: title,
						'aria-describedby': errMsg ? 'db-new-page-error' : 'db-new-page-help',
						onChange: e => { setTitle( e.target.value ); setErrMsg( '' ); },
						onKeyDown: e => { if ( e.key === 'Enter' ) create(); },
					} ),
					errMsg && el( 'p', { className: 'db-modal__error', id: 'db-new-page-error', role: 'alert' }, errMsg ),
					el( 'p', { id: 'db-new-page-help', className: 'db-modal__help' }, 'The page will be created as a draft — you can publish it from WordPress after building.' )
				),
				el( 'div', { className: 'db-modal__footer' },
					el( 'button', {
						className: 'db-btn db-btn--ghost',
						onClick: onClose,
						type: 'button',
					}, 'Cancel' ),
					el( 'button', {
						className: 'db-btn db-btn--save',
						disabled: busy || ! title.trim(),
						onClick: create,
						type: 'button',
					}, busy ? '⏳ Creating…' : 'Create Page →' )
				)
			)
		);
	}

	// ─── Top Bar ──────────────────────────────────────────────────────────────
	function TopBar( { pages, pageId, onPageChange, status, isDirty, onSave, pageLink, onNewPage, simpleMode, onToggleSimpleMode, viewportMode, onViewportChange } ) {
		const statusText  = { saving: 'Saving…', saved: '✓ Saved', error: '✕ Error' }[ status ]
			|| ( isDirty ? '● Unsaved changes' : '' );
		const statusClass = { saving: 'db-topbar__status--saving', saved: 'db-topbar__status--saved', error: 'db-topbar__status--error' }[ status ]
			|| ( isDirty ? 'db-topbar__status--dirty' : '' );

		return el( 'div', { className: 'db-topbar' },
			el( 'div', { className: 'db-topbar__brand' },
				el( 'img', {
					src: SCOUTS_MARK_URL,
					alt: 'Scouts',
					className: 'db-topbar__brand-mark',
					width: '22',
					height: '22',
					style: { width: '22px', height: '22px', maxWidth: '22px', maxHeight: '22px', minWidth: '22px', minHeight: '22px', display: 'block', objectFit: 'contain', flexShrink: 0 }
				} ),
				'Davenham Builder'
			),

			el( 'div', { className: 'db-topbar__divider' } ),

			el( 'div', { className: 'db-topbar__page-select' },
				el( 'select', {
					value: pageId || '',
					onChange: e => onPageChange( e.target.value ),
				},
					el( 'option', { value: '' }, pages.length === 0 ? '⏳ Loading pages…' : '— Select a page —' ),
					pages.map( p =>
						el( 'option', { key: p.id, value: p.id },
							( p.status === 'draft' ? '✏️ ' : '' ) + ( p.title && p.title.rendered ? p.title.rendered : p.title || 'Page #' + p.id )
						)
					)
				)
			),

			el( 'button', {
				className: 'db-btn db-btn--new',
				onClick: onNewPage,
				title: 'Create a new page',
			}, '＋ New Page' ),

			el( 'a', {
				href: dbConfig.adminUrl + 'admin.php?page=davenham-builder-site-settings',
				className: 'db-btn db-btn--ghost',
				title: 'Edit the global header, footer, cookie banner, popup, and newsletter settings',
			}, 'Site settings' ),

			el( 'button', {
				className: 'db-btn db-btn--ghost',
				type: 'button',
				onClick: onToggleSimpleMode,
				title: simpleMode ? 'Switch back to full editing mode' : 'Limit editing to safer text and image updates',
			}, simpleMode ? 'Simple mode: On' : 'Simple mode: Off' ),

			el( 'div', { className: 'db-topbar__spacer' } ),

			// Viewport preview toggle — desktop / tablet / mobile
			el( 'div', { className: 'db-viewport-toggle', role: 'group', 'aria-label': 'Preview viewport' },
				[
					{ key: 'desktop', icon: '🖥', label: 'Desktop' },
					{ key: 'tablet',  icon: '📱', label: 'Tablet (768px)' },
					{ key: 'mobile',  icon: '📱', label: 'Mobile (375px)' },
				].map( function ( opt ) {
					return el( 'button', {
						key: opt.key,
						type: 'button',
						className: 'db-viewport-toggle__btn' + ( viewportMode === opt.key ? ' is-active' : '' ),
						onClick: function () { onViewportChange( opt.key ); },
						title: opt.label,
						'aria-label': 'Preview at ' + opt.label,
						'aria-pressed': viewportMode === opt.key ? 'true' : 'false',
					}, opt.icon );
				} )
			),

			statusText && el( 'span', { className: 'db-topbar__status ' + statusClass }, statusText ),

			pageLink && el( 'a', {
				href: pageLink, target: '_blank', rel: 'noreferrer',
				className: 'db-btn db-btn--preview',
			}, '👁 Preview' ),

			el( 'a', {
				href: dbConfig.adminUrl + 'edit.php?post_type=page',
				className: 'db-btn db-btn--ghost',
			}, '← Pages' ),

			el( 'button', {
				className: 'db-btn db-btn--save',
				onClick: onSave,
				disabled: status === 'saving' || ! pageId,
			}, status === 'saving' ? '⏳ Saving…' : '💾 Save Page' )
		);
	}

	// ─── Toast ────────────────────────────────────────────────────────────────
	function Toast( { message, type } ) {
		if ( ! message ) return null;
		return el( 'div', { className: 'db-toast db-toast--' + ( type || 'success' ) }, message );
	}

	// ─── Main App ─────────────────────────────────────────────────────────────
	function BuilderApp() {
		const [ pages,       setPages       ] = useState( [] );
		const [ pageId,      setPageId      ] = useState( null );
		const [ pageLink,    setPageLink    ] = useState( '' );
		const [ sections,    setSections    ] = useState( [] );
		const [ selectedId,  setSelectedId  ] = useState( null );
		const [ loading,     setLoading     ] = useState( false );
		const [ status,      setStatus      ] = useState( 'idle' );
		const [ toast,       setToast       ] = useState( null );
		const [ showNewPage, setShowNewPage ] = useState( false );
		const [ isDirty,     setIsDirty     ] = useState( false );
		const [ simpleMode,  setSimpleMode  ] = useState( window.localStorage.getItem( SIMPLE_MODE_KEY ) === '1' );
		const [ guideDismissed, setGuideDismissed ] = useState( window.localStorage.getItem( GUIDE_DISMISSED_KEY ) === '1' );
		const savePageRef = useRef( null ); // always points to latest savePage()

		// ── Warn before leaving with unsaved changes ─────────────────────────────
		useEffect( () => {
			function onBeforeUnload( e ) {
				if ( ! isDirty ) return;
				e.preventDefault();
				e.returnValue = '';
			}
			window.addEventListener( 'beforeunload', onBeforeUnload );
			return () => window.removeEventListener( 'beforeunload', onBeforeUnload );
		}, [ isDirty ] );

		// ── Ctrl+S / Cmd+S keyboard shortcut ─────────────────────────────────────
		useEffect( () => {
			function onKeyDown( e ) {
				if ( ( e.ctrlKey || e.metaKey ) && e.key === 's' ) {
					e.preventDefault();
					if ( savePageRef.current ) savePageRef.current();
				}
			}
			document.addEventListener( 'keydown', onKeyDown );
			return () => document.removeEventListener( 'keydown', onKeyDown );
		}, [] );

		// ── Load pages list — uses standard WP REST, no custom routes ───────────
		useEffect( () => {
			restGet( '/wp/v2/pages?per_page=100&orderby=menu_order&order=asc&_fields=id,title,status&status=publish,draft,private' )
				.then( function ( data ) { setPages( data || [] ); } )
				.catch( function () {
					restGet( '/wp/v2/pages?per_page=100&_fields=id,title,status' )
						.then( function ( data ) { setPages( data || [] ); } )
						.catch( function () { showToast( 'Could not load pages list', 'error' ); } );
				} );

			const pid = new URLSearchParams( window.location.search ).get( 'post_id' );
			if ( pid ) loadPage( pid );
		}, [] );

		function showToast( msg, type ) {
			setToast( { msg: msg, type: type || 'success' } );
			setTimeout( function () { setToast( null ); }, 3500 );
		}

		function toggleSimpleMode() {
			const next = ! simpleMode;
			setSimpleMode( next );
			window.localStorage.setItem( SIMPLE_MODE_KEY, next ? '1' : '0' );
			showToast( next ? 'Simple mode is on — layout controls are hidden.' : 'Simple mode is off — full layout editing is back.', 'success' );
		}

		function dismissGuide() {
			setGuideDismissed( true );
			window.localStorage.setItem( GUIDE_DISMISSED_KEY, '1' );
		}

		// ── Load a page — uses /wp/v2/pages/{id}?context=edit ────────────────
		function loadPage( id ) {
			setLoading( true );
			setSelectedId( null );
			// context=edit returns content.raw (raw block markup)
			restGet( '/wp/v2/pages/' + id + '?context=edit&_fields=id,title,link,status,content' )
				.then( function ( data ) {
					setPageId( String( id ) );
					setPageLink( data.link || '' );
					// content.raw contains the raw block markup
					var raw = ( data.content && data.content.raw ) ? data.content.raw : ( data.content || '' );
					setSections( parseContent( raw ) );
					setIsDirty( false );
					setLoading( false );
					var url = new URL( window.location.href );
					url.searchParams.set( 'post_id', id );
					window.history.replaceState( {}, '', url );
				} )
				.catch( function ( err ) {
					setLoading( false );
					showToast( 'Could not load page — ' + ( err && err.message ? err.message : 'check permissions' ), 'error' );
				} );
		}

		// ── Save — uses standard POST /wp/v2/pages/{id} ───────────────────────
		function savePage() {
			if ( ! pageId ) return;
			setStatus( 'saving' );
			restPost( '/wp/v2/pages/' + pageId, { content: buildContent( sections ) } )
				.then( function () {
					setStatus( 'saved' );
					setIsDirty( false );
					showToast( '✓ Page saved successfully', 'success' );
					setTimeout( function () { setStatus( 'idle' ); }, 3000 );
				} )
				.catch( function ( err ) {
					setStatus( 'error' );
					showToast( 'Save failed — ' + ( err && err.message ? err.message : 'try again' ), 'error' );
					setTimeout( function () { setStatus( 'idle' ); }, 4000 );
				} );
		}

		// ── Create new page ───────────────────────────────────────────────────
		function handleNewPageCreated( page ) {
			setShowNewPage( false );
			setPages( function ( prev ) { return [ ...prev, page ]; } );
			loadPage( page.id );
			var name = ( page.title && page.title.rendered ) ? page.title.rendered : ( page.title || 'Page' );
			showToast( '✓ Page "' + name + '" created', 'success' );
		}

		// ── Section operations ────────────────────────────────────────────────
		const addSection = useCallback( type => {
			const schema = BLOCKS.find( b => b.type === type );
			const newSec = { id: uid(), type, attrs: { ...( schema ? schema.defaults : {} ) } };
			setSections( prev => {
				// Insert immediately after the selected section; append if nothing selected.
				if ( selectedId ) {
					const idx = prev.findIndex( s => s.id === selectedId );
					if ( idx !== -1 ) {
						const next = prev.slice();
						next.splice( idx + 1, 0, newSec );
						return next;
					}
				}
				return [ ...prev, newSec ];
			} );
			setSelectedId( newSec.id );
			setIsDirty( true );
		}, [ selectedId ] );

		const updateSection = useCallback( ( id, attrs ) => {
			setSections( prev => prev.map( s => s.id === id ? { ...s, attrs } : s ) );
			setIsDirty( true );
		}, [] );

		const deleteSection = useCallback( id => {
			setSections( prev => prev.filter( s => s.id !== id ) );
			setSelectedId( prev => prev === id ? null : prev );
			setIsDirty( true );
		}, [] );

		const duplicateSection = useCallback( id => {
			setSections( prev => {
				const idx = prev.findIndex( s => s.id === id );
				if ( idx === -1 ) return prev;
				const copy = { id: uid(), type: prev[ idx ].type, attrs: { ...prev[ idx ].attrs } };
				const next = prev.slice();
				next.splice( idx + 1, 0, copy );
				return next;
			} );
			setIsDirty( true );
			showToast( '✓ Section duplicated', 'success' );
		}, [] );

		// Inline "+ Add" between sections — opens picker at a specific position.
		const [ insertAtIndex, setInsertAtIndex ] = useState( null );
		const insertSectionAt = useCallback( ( type, index ) => {
			const schema = BLOCKS.find( b => b.type === type );
			const newSec = { id: uid(), type, attrs: { ...( schema ? schema.defaults : {} ) } };
			setSections( prev => {
				const next = prev.slice();
				const i = Math.max( 0, Math.min( index, next.length ) );
				next.splice( i, 0, newSec );
				return next;
			} );
			setSelectedId( newSec.id );
			setIsDirty( true );
			setInsertAtIndex( null );
		}, [] );

		const moveSection = useCallback( ( from, to ) => {
			setSections( prev => {
				if ( to < 0 || to >= prev.length ) return prev;
				const next = [ ...prev ];
				const [ item ] = next.splice( from, 1 );
				next.splice( to, 0, item );
				return next;
			} );
			setIsDirty( true );
		}, [] );

		// Viewport preview mode — adjusts canvas width to mimic device sizes.
		const [ viewportMode, setViewportMode ] = useState( 'desktop' );

		const applyPreset = useCallback( function ( preset ) {
			if ( ! preset || typeof preset.sections !== 'function' ) {
				return;
			}
			if ( sections.length > 0 && ! window.confirm( 'Replace the current page layout with the "' + preset.label + '" starter preset?' ) ) {
				return;
			}
			const nextSections = preset.sections();
			setSections( nextSections );
			setSelectedId( nextSections[0] ? nextSections[0].id : null );
			setIsDirty( true );
			showToast( 'Loaded the "' + preset.label + '" starter preset.', 'success' );
		}, [ sections ] );

		// ── Render ────────────────────────────────────────────────────────────
		// Keep ref fresh every render so the Ctrl+S handler always calls the latest savePage.
		savePageRef.current = savePage;

		const selectedSection = sections.find( s => s.id === selectedId );
		const insertAfterLabel = selectedSection
			? ( BLOCKS.find( b => b.type === selectedSection.type ) || {} ).label || null
			: null;
		const hasSections = sections.length > 0;

		// When user clicked "+ Add section here" between sections, the library's
		// onAdd inserts at that index instead of appending after the selected one.
		const libraryAddHandler = insertAtIndex !== null
			? function ( type ) { insertSectionAt( type, insertAtIndex ); }
			: addSection;

		const sidebarContent = selectedSection
			? el( SettingsPanel, {
				section: selectedSection,
				onUpdate: attrs => updateSection( selectedId, attrs ),
				onBack: () => setSelectedId( null ),
				simpleMode,
			} )
			: el( ComponentLibrary, {
				onAdd: libraryAddHandler,
				insertAfterLabel: insertAfterLabel,
				insertAtIndex,
				onClearInsertAt: () => setInsertAtIndex( null ),
				onApplyPreset: applyPreset,
				simpleMode,
				guideDismissed,
				onDismissGuide: dismissGuide,
				hasSections,
				hasPage: !! pageId,
				onCreateNewPage: () => setShowNewPage( true ),
			} );

		const mainContent = loading
			? el( 'div', { className: 'db-loading' },
				el( 'div', { className: 'db-spinner' } ),
				'Loading page…'
			  )
			: ! pageId
			? el( 'div', { className: 'db-nopage' },
				el( 'img', { src: 'https://davenhamscouts.org.uk/wp-content/uploads/2026/04/scouts.png', style: { width: 90, height: 'auto', display: 'block', margin: '0 auto 20px' } } ),
				el( 'h2', null, 'Select a page to edit' ),
				el( 'p', null, 'Choose a page from the dropdown above, or click "+ New Page" to create one.' )
			  )
			: el( Canvas, {
				sections, selectedId,
				onSelect: function ( id ) { setInsertAtIndex( null ); setSelectedId( id ); },
				onMove:        moveSection,
				onDelete:      deleteSection,
				onDuplicate:   duplicateSection,
				onInsertAt:    function ( idx ) { setSelectedId( null ); setInsertAtIndex( idx ); },
				simpleMode,
				viewportMode,
			} );

		return el( 'div', { className: 'db-app' },
			el( TopBar, {
				pages, pageId, status, isDirty, pageLink,
				onPageChange: id => { if ( id ) loadPage( id ); else { setPageId( null ); setSections( [] ); setIsDirty( false ); } },
				onSave: savePage,
				onNewPage: () => setShowNewPage( true ),
				simpleMode,
				onToggleSimpleMode: toggleSimpleMode,
				viewportMode,
				onViewportChange: setViewportMode,
			} ),
			el( 'div', { className: 'db-body' },
				el( 'div', { className: 'db-sidebar' }, sidebarContent ),
				mainContent
			),
			toast && el( Toast, { message: toast.msg, type: toast.type } ),
			showNewPage && el( NewPageModal, {
				onClose: () => setShowNewPage( false ),
				onCreated: handleNewPageCreated,
			} )
		);
	}

	// ─── Mount (supports both WP 6.0 render and WP 6.2+ createRoot) ─────────
	const root = document.getElementById( 'davenham-builder-root' );
	if ( root ) {
		if ( wp.element.createRoot ) {
			wp.element.createRoot( root ).render( el( BuilderApp ) );
		} else {
			wp.element.render( el( BuilderApp ), root );
		}
	}
} )();
