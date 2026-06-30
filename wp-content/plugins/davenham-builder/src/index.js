/* global wp */
/**
 * Davenham Blocks — editor registration.
 * Vanilla JS only, no build step. All blocks are server-side rendered (save → null).
 * Controls live in the InspectorControls sidebar panel.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var registerBlockType = blocks.registerBlockType;
	var el               = element.createElement;
	var Fragment         = element.Fragment;
	var __               = i18n.__;

	var InspectorControls  = blockEditor.InspectorControls;
	var MediaUpload        = blockEditor.MediaUpload;
	var MediaUploadCheck   = blockEditor.MediaUploadCheck;

	var PanelBody       = components.PanelBody;
	var TextControl     = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var SelectControl   = components.SelectControl;
	var ToggleControl   = components.ToggleControl;
	var RangeControl    = components.RangeControl;
	var Button          = components.Button;

	// ─── Shared helpers ────────────────────────────────────────────────────────

	/** Render a set of button editor rows + Add/Remove controls. */
	function buttonsEditor( buttons, setAttributes ) {
		buttons = buttons || [];
		var styleOptions = [
			{ label: 'Outline', value: 'outline' },
			{ label: 'White',   value: 'white'   },
			{ label: 'Green',   value: 'green'   },
		];
		var rows = buttons.map( function ( btn, i ) {
			return el( 'div', { key: i, style: { marginBottom: 12, paddingBottom: 12, borderBottom: '1px solid #ddd' } },
				el( TextControl, {
					label: 'Button ' + ( i + 1 ) + ' Text',
					value: btn.text || '',
					onChange: function ( v ) {
						var nb = buttons.slice(); nb[i] = Object.assign( {}, btn, { text: v } );
						setAttributes( { buttons: nb } );
					},
				} ),
				el( TextControl, {
					label: 'Button ' + ( i + 1 ) + ' URL',
					value: btn.url || '',
					onChange: function ( v ) {
						var nb = buttons.slice(); nb[i] = Object.assign( {}, btn, { url: v } );
						setAttributes( { buttons: nb } );
					},
				} ),
				el( SelectControl, {
					label: 'Style',
					value: btn.style || 'outline',
					options: styleOptions,
					onChange: function ( v ) {
						var nb = buttons.slice(); nb[i] = Object.assign( {}, btn, { style: v } );
						setAttributes( { buttons: nb } );
					},
				} ),
				el( Button, {
					variant: 'tertiary', isDestructive: true, isSmall: true,
					onClick: function () {
						setAttributes( { buttons: buttons.filter( function ( _, j ) { return j !== i; } ) } );
					},
				}, 'Remove' )
			);
		} );
		return el( 'div', null,
			rows,
			el( Button, {
				variant: 'primary', isSmall: true,
				onClick: function () {
					setAttributes( { buttons: buttons.concat( [ { text: 'Button', url: '', style: 'outline' } ] ) } );
				},
			}, '+ Add Button' )
		);
	}

	/** MediaUpload helper — shows current image thumbnail + change/select button. */
	function mediaUpload( imageUrl, imageId, onSelect, label ) {
		label = label || 'Select Image';
		return el( MediaUploadCheck, null,
			el( MediaUpload, {
				onSelect: function ( media ) { onSelect( media.url, media.id ); },
				allowedTypes: [ 'image' ],
				value: imageId || 0,
				render: function ( ref ) {
					return el( 'div', null,
						imageUrl && el( 'img', { src: imageUrl, style: { maxWidth: '100%', display: 'block', marginBottom: 8 } } ),
						el( Button, { onClick: ref.open, variant: 'secondary' }, imageUrl ? 'Change Image' : label )
					);
				},
			} )
		);
	}

	/** Grey dashed placeholder shown in the editor canvas. */
	function placeholder( icon, title, detail ) {
		var kids = [
			el( 'div', { key: 'icon', style: { fontSize: 28 } }, icon ),
			el( 'strong', { key: 'title', style: { display: 'block', marginTop: 6, fontSize: 15 } }, title ),
		];
		if ( detail ) {
			kids.push( el( 'p', { key: 'detail', style: { color: '#666', fontSize: 13, marginTop: 4, marginBottom: 0 } }, detail ) );
		}
		// Blocks edited in the Visual Builder get a direct link there instead of a
		// dead-end "manage in Visual Builder" message.
		if ( detail && detail.indexOf( 'Visual Builder' ) !== -1 && window.wp && wp.data && wp.data.select( 'core/editor' ) ) {
			var pid = wp.data.select( 'core/editor' ).getCurrentPostId();
			if ( pid ) {
				kids.push( el( 'a', {
					key: 'cta',
					href: 'admin.php?page=davenham-builder&post_id=' + pid,
					className: 'button button-primary',
					style: { marginTop: 12, textDecoration: 'none' },
				}, 'Edit in Visual Builder →' ) );
			}
		}
		return el( 'div', {
			style: {
				padding: '24px 20px', background: '#f6f7f7',
				border: '2px dashed #bbb', borderRadius: 4, textAlign: 'center',
			},
		}, kids );
	}

	// ─── 1. HERO SECTION ───────────────────────────────────────────────────────
	registerBlockType( 'davenham/hero', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Background Image', initialOpen: true },
						mediaUpload( a.backgroundImageUrl, a.backgroundImageId, function ( url, id ) {
							sa( { backgroundImageUrl: url, backgroundImageId: id } );
						} )
					),
					el( PanelBody, { title: 'Text Content', initialOpen: true },
						el( TextareaControl, { label: 'Heading', value: a.heading, onChange: function ( v ) { sa( { heading: v } ); } } ),
						el( TextareaControl, { label: 'Subtext (HTML)', value: a.subtext, onChange: function ( v ) { sa( { subtext: v } ); }, help: 'Supports basic HTML.' } )
					),
					el( PanelBody, { title: 'CTA Buttons', initialOpen: true },
						buttonsEditor( a.buttons, sa )
					)
				),
				el( 'div', {
					style: {
						background: a.backgroundImageUrl ? 'url(' + a.backgroundImageUrl + ') center/cover' : '#003f87',
						padding: '60px 24px', position: 'relative',
					},
				},
					el( 'div', { style: { background: 'rgba(0,0,0,.45)', padding: '28px 24px', borderRadius: 4, maxWidth: 520, display: 'inline-block' } },
						el( 'h2', { style: { color: '#fff', margin: '0 0 10px' } }, a.heading || 'Hero Heading' ),
						a.subtext && el( 'p', { style: { color: 'rgba(255,255,255,.85)', margin: '0 0 14px', fontSize: 14 } }, '(subtext set — see sidebar)' ),
						el( 'div', null, ( a.buttons || [] ).map( function ( btn, i ) {
							return el( 'span', {
								key: i,
								style: {
									display: 'inline-block', marginRight: 8, padding: '6px 14px',
									background: btn.style === 'green' ? '#37a03c' : 'transparent',
									border: '2px solid #fff', color: '#fff', borderRadius: 3, fontSize: 13,
								},
							}, btn.text || 'Button' );
						} ) )
					)
				)
			);
		},
		save: function () { return null; },
	} );

	// ─── 2. CTA BUTTON ROW ─────────────────────────────────────────────────────
	registerBlockType( 'davenham/cta-button-row', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Buttons', initialOpen: true }, buttonsEditor( a.buttons, sa ) )
				),
				el( 'div', { style: { padding: '14px 16px', background: '#f6f7f7', border: '1px dashed #bbb', borderRadius: 4 } },
					el( 'strong', { style: { display: 'block', marginBottom: 8, fontSize: 12, textTransform: 'uppercase', color: '#888' } }, 'CTA Button Row' ),
					( a.buttons || [] ).length === 0
						? el( 'em', { style: { color: '#aaa' } }, 'No buttons yet — add in sidebar' )
						: el( 'div', null, ( a.buttons || [] ).map( function ( btn, i ) {
							return el( 'span', {
								key: i,
								style: {
									display: 'inline-block', marginRight: 8, padding: '8px 16px',
									background: btn.style === 'green' ? '#37a03c' : 'transparent',
									border: '2px solid ' + ( btn.style === 'green' ? '#37a03c' : '#003f87' ),
									color: btn.style === 'green' ? '#fff' : '#003f87',
									borderRadius: 3, fontSize: 14,
								},
							}, btn.text || 'Button' );
						} ) )
				)
			);
		},
		save: function () { return null; },
	} );

	// ─── 3. WELCOME SECTION ────────────────────────────────────────────────────
	registerBlockType( 'davenham/welcome-section', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Content', initialOpen: true },
						el( TextControl, { label: 'Heading', value: a.heading, onChange: function ( v ) { sa( { heading: v } ); } } ),
						el( TextControl, { label: 'Heading Highlight (inside <span>)', value: a.headingHighlight, onChange: function ( v ) { sa( { headingHighlight: v } ); } } ),
						el( TextareaControl, { label: 'Body Content', value: a.content, onChange: function ( v ) { sa( { content: v } ); }, rows: 5 } ),
						el( TextControl, { label: 'Button Text', value: a.buttonText, onChange: function ( v ) { sa( { buttonText: v } ); } } ),
						el( TextControl, { label: 'Button URL', value: a.buttonUrl, onChange: function ( v ) { sa( { buttonUrl: v } ); } } )
					),
					el( PanelBody, { title: 'Image', initialOpen: true },
						mediaUpload( a.imageUrl, a.imageId, function ( url, id ) { sa( { imageUrl: url, imageId: id } ); } )
					)
				),
				placeholder( '🏕️', 'Welcome Section',
					( a.heading || 'Welcome to' ) + ' ' + ( a.headingHighlight || '1st Davenham' ) )
			);
		},
		save: function () { return null; },
	} );

	// ─── 4. AGE SECTION ────────────────────────────────────────────────────────
	registerBlockType( 'davenham/age-section', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Age Section Settings', initialOpen: true },
						el( TextControl, { label: 'Heading', value: a.heading, onChange: function ( v ) { sa( { heading: v } ); } } ),
						el( ToggleControl, { label: 'Show Squirrels (4–6)',    checked: a.showSquirrels,  onChange: function ( v ) { sa( { showSquirrels: v } ); } } ),
						el( ToggleControl, { label: 'Show Beavers (6–8)',      checked: a.showBeavers,    onChange: function ( v ) { sa( { showBeavers: v } ); } } ),
						el( ToggleControl, { label: 'Show Cubs (8–10½)',       checked: a.showCubs,       onChange: function ( v ) { sa( { showCubs: v } ); } } ),
						el( ToggleControl, { label: 'Show Scouts (10½–14)',    checked: a.showScouts,     onChange: function ( v ) { sa( { showScouts: v } ); } } ),
						el( ToggleControl, { label: 'Show Explorers (14–18)',  checked: a.showExplorers,  onChange: function ( v ) { sa( { showExplorers: v } ); } } ),
						el( ToggleControl, { label: 'Show Network (18–25)',    checked: a.showNetwork,    onChange: function ( v ) { sa( { showNetwork: v } ); } } )
					)
				),
				placeholder( '🦦', 'Age Section', a.heading || 'Aged 6 to 25?' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 5. NEWS FEED ──────────────────────────────────────────────────────────
	registerBlockType( 'davenham/news-feed', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'News Feed Settings', initialOpen: true },
						el( TextControl,  { label: 'Section Heading', value: a.heading,      onChange: function ( v ) { sa( { heading: v } ); } } ),
						el( TextControl,  { label: '"View All" Text', value: a.viewAllText,  onChange: function ( v ) { sa( { viewAllText: v } ); } } ),
						el( TextControl,  { label: '"View All" URL',  value: a.viewAllUrl,   onChange: function ( v ) { sa( { viewAllUrl: v } ); } } ),
						el( RangeControl, {
							label: 'Number of Posts', value: a.numberOfPosts,
							onChange: function ( v ) { sa( { numberOfPosts: v } ); }, min: 1, max: 8,
						} )
					)
				),
				placeholder( '📰', 'News Feed', 'Shows latest ' + a.numberOfPosts + ' posts — rendered server-side' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 6. SITE NOTICE BAR ────────────────────────────────────────────────────
	registerBlockType( 'davenham/site-notice', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			var bg = a.backgroundColor || ( a.style === 'dark' ? '#003f87' : '#fff' );
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Notice Bar Settings', initialOpen: true },
						el( TextControl,  { label: 'Notice Text',             value: a.text,            onChange: function ( v ) { sa( { text: v } ); } } ),
						el( TextControl,  { label: 'Button Text',             value: a.buttonText,      onChange: function ( v ) { sa( { buttonText: v } ); } } ),
						el( TextControl,  { label: 'Button URL',              value: a.buttonUrl,       onChange: function ( v ) { sa( { buttonUrl: v } ); } } ),
						el( SelectControl, {
							label: 'Style', value: a.style,
							options: [ { label: 'White', value: 'white' }, { label: 'Dark (navy)', value: 'dark' } ],
							onChange: function ( v ) { sa( { style: v } ); },
						} ),
						el( TextControl, { label: 'Custom Background Colour (optional, e.g. #f00)', value: a.backgroundColor, onChange: function ( v ) { sa( { backgroundColor: v } ); } } )
					)
				),
				el( 'div', {
					style: {
						padding: '10px 20px', background: bg,
						color: a.style === 'dark' ? '#fff' : '#333',
						display: 'flex', alignItems: 'center', justifyContent: 'space-between',
						border: '1px dashed #bbb',
					},
				},
					el( 'span', null, a.text || 'Notice text — set in sidebar' ),
					a.buttonText && el( 'span', { style: { padding: '6px 12px', background: '#37a03c', color: '#fff', borderRadius: 3, fontSize: 13 } }, a.buttonText )
				)
			);
		},
		save: function () { return null; },
	} );

	// ─── 7. TEXT + IMAGE ───────────────────────────────────────────────────────
	registerBlockType( 'davenham/text-image', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Content', initialOpen: true },
						el( TextControl,     { label: 'Heading',         value: a.heading,  onChange: function ( v ) { sa( { heading: v } ); } } ),
						el( TextareaControl, { label: 'Body (HTML)',      value: a.content,  onChange: function ( v ) { sa( { content: v } ); }, rows: 6 } ),
						el( SelectControl, {
							label: 'Image Position', value: a.imagePosition,
							options: [ { label: 'Right', value: 'right' }, { label: 'Left', value: 'left' } ],
							onChange: function ( v ) { sa( { imagePosition: v } ); },
						} )
					),
					el( PanelBody, { title: 'Image', initialOpen: true },
						mediaUpload( a.imageUrl, a.imageId, function ( url, id ) { sa( { imageUrl: url, imageId: id } ); } )
					)
				),
				placeholder( '🖼️', 'Text + Image', ( a.heading || 'Heading' ) + ' — image ' + a.imagePosition )
			);
		},
		save: function () { return null; },
	} );

	// ─── 8. ICON / FEATURE ROW ─────────────────────────────────────────────────
	registerBlockType( 'davenham/icon-feature-row', {
		edit: function ( props ) {
			var a       = props.attributes;
			var sa      = props.setAttributes;
			var columns = a.columns || [];
			var colRows = columns.map( function ( col, i ) {
				return el( 'div', { key: i, style: { marginBottom: 12, paddingBottom: 12, borderBottom: '1px solid #eee' } },
					el( 'strong', { style: { display: 'block', marginBottom: 6, fontSize: 13 } }, 'Column ' + ( i + 1 ) ),
					el( TextControl, {
						label: 'Icon Image URL',
						value: col.iconUrl || '',
						onChange: function ( v ) {
							var nc = columns.slice(); nc[i] = Object.assign( {}, col, { iconUrl: v } );
							sa( { columns: nc } );
						},
					} ),
					el( TextareaControl, {
						label: 'Text / HTML',
						value: col.text || '',
						rows: 3,
						onChange: function ( v ) {
							var nc = columns.slice(); nc[i] = Object.assign( {}, col, { text: v } );
							sa( { columns: nc } );
						},
					} ),
					el( Button, {
						variant: 'tertiary', isDestructive: true, isSmall: true,
						onClick: function () { sa( { columns: columns.filter( function ( _, j ) { return j !== i; } ) } ); },
					}, 'Remove Column' )
				);
			} );
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Icon / Feature Columns', initialOpen: true },
						colRows,
						el( Button, {
							variant: 'primary', isSmall: true,
							onClick: function () { sa( { columns: columns.concat( [ { iconUrl: '', text: '' } ] ) } ); },
						}, '+ Add Column' )
					)
				),
				placeholder( '⚡', 'Icon / Feature Row', columns.length + ' column(s) — manage in sidebar' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 9. PAGE HERO (inner-page banner) ──────────────────────────────────────
	registerBlockType( 'davenham/page-hero', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Page Hero Settings', initialOpen: true },
						el( TextControl,     { label: 'Heading',  value: a.heading, onChange: function ( v ) { sa( { heading: v } ); } } ),
						el( TextareaControl, { label: 'Subtext',  value: a.subtext, onChange: function ( v ) { sa( { subtext: v } ); }, rows: 3 } )
					)
				),
				el( 'div', { style: { background: '#003f87', padding: '40px 24px', borderRadius: 4 } },
					el( 'h2', { style: { color: '#fff', margin: '0 0 8px', fontSize: 24 } }, a.heading || 'Page Title' ),
					a.subtext && el( 'p', { style: { color: 'rgba(255,255,255,.75)', margin: 0, fontSize: 14 } }, a.subtext )
				)
			);
		},
		save: function () { return null; },
	} );

	// ─── 10. EVENTS LIST ───────────────────────────────────────────────────────
	registerBlockType( 'davenham/events-list', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Events List Settings', initialOpen: true },
						el( TextControl,  { label: 'Section Heading',  value: a.heading,        onChange: function ( v ) { sa( { heading: v } ); } } ),
						el( TextControl,  { label: '"View All" Text',  value: a.viewAllText,    onChange: function ( v ) { sa( { viewAllText: v } ); } } ),
						el( TextControl,  { label: '"View All" URL',   value: a.viewAllUrl,     onChange: function ( v ) { sa( { viewAllUrl: v } ); } } ),
						el( RangeControl, {
							label: 'Number of Events', value: a.numberOfEvents,
							onChange: function ( v ) { sa( { numberOfEvents: v } ); }, min: 1, max: 10,
						} )
					)
				),
				placeholder( '📅', 'Events List', 'Shows next ' + a.numberOfEvents + ' events — rendered server-side' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 11. FAQ ACCORDION ─────────────────────────────────────────────────────
	registerBlockType( 'davenham/faq', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'FAQ Settings', initialOpen: true },
						el( TextControl, { label: 'Heading', value: a.heading, onChange: function ( v ) { sa( { heading: v } ); } } )
					)
				),
				placeholder( '❓', 'FAQ Accordion', ( a.items || [] ).length + ' item(s) — manage in Visual Builder' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 12. LEADERS / TEAM ────────────────────────────────────────────────────
	registerBlockType( 'davenham/leaders', {
		edit: function ( props ) {
			var a = props.attributes;
			return el( Fragment, null,
				placeholder( '👩‍✈️', 'Leaders / Team', ( a.leaders || [] ).length + ' leader(s) — manage in Visual Builder' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 13. SPONSORS / PARTNERS ───────────────────────────────────────────────
	registerBlockType( 'davenham/sponsors', {
		edit: function ( props ) {
			var a = props.attributes;
			return el( Fragment, null,
				placeholder( '🤝', 'Sponsors / Partners', ( a.logos || [] ).length + ' logo(s) — manage in Visual Builder' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 14. CONTACT INFO ──────────────────────────────────────────────────────
	registerBlockType( 'davenham/contact-info', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Contact Info', initialOpen: true },
						el( TextControl, { label: 'Heading', value: a.heading, onChange: function ( v ) { sa( { heading: v } ); } } )
					)
				),
				placeholder( '📍', 'Contact Info', a.heading || 'Get in Touch' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 15. PHOTO GALLERY ─────────────────────────────────────────────────────
	registerBlockType( 'davenham/gallery', {
		edit: function ( props ) {
			var a = props.attributes;
			return el( Fragment, null,
				placeholder( '🎨', 'Photo Gallery', ( a.images || [] ).length + ' image(s) — manage in Visual Builder' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 16. VIDEO EMBED ───────────────────────────────────────────────────────
	registerBlockType( 'davenham/video-embed', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Video Settings', initialOpen: true },
						el( TextControl, { label: 'YouTube / Vimeo URL', value: a.videoUrl, onChange: function ( v ) { sa( { videoUrl: v } ); } } ),
						el( TextControl, { label: 'Heading (optional)',  value: a.heading,  onChange: function ( v ) { sa( { heading: v } ); } } ),
						el( TextControl, { label: 'Caption (optional)',  value: a.caption,  onChange: function ( v ) { sa( { caption: v } ); } } )
					)
				),
				placeholder( '▶️', 'Video Embed', a.videoUrl || 'Set URL in sidebar' )
			);
		},
		save: function () { return null; },
	} );

	// ─── 17. SECTION DIVIDER ───────────────────────────────────────────────────
	registerBlockType( 'davenham/section-divider', {
		edit: function ( props ) {
			var a  = props.attributes;
			var sa = props.setAttributes;
			return el( Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Divider Settings', initialOpen: true },
						el( RangeControl, {
							label: 'Height (px)', value: a.height,
							onChange: function ( v ) { sa( { height: v } ); }, min: 8, max: 200,
						} ),
						el( SelectControl, {
							label: 'Style', value: a.style,
							options: [
								{ label: 'Blank Space',     value: 'blank'  },
								{ label: 'Thin Line',       value: 'line'   },
								{ label: 'Scouts Fleur ⚜', value: 'scouts' },
							],
							onChange: function ( v ) { sa( { style: v } ); },
						} )
					)
				),
				el( 'div', {
					style: {
						height: ( a.height || 48 ) + 'px',
						background: '#f6f7f7',
						border: '1px dashed #bbb',
						borderRadius: 4,
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						color: '#bbb',
						fontSize: 12,
					},
				}, 'Spacer / Divider — ' + ( a.style || 'blank' ) + ' — ' + ( a.height || 48 ) + 'px' )
			);
		},
		save: function () { return null; },
	} );

}(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
) );
