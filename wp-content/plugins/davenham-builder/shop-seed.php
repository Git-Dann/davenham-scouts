<?php
/**
 * Sample shop products seeder.
 *
 * Adds a "Seed sample products" admin screen under Builder. Creates four
 * canonical product categories and ~14 sample products so the shop has
 * realistic content to demo the full purchase flow without waiting on the
 * editor to manually create everything.
 *
 * Non-destructive:
 *   - Every seeded product is tagged with post meta `_db_sample_product = 1`
 *   - "Remove sample products" deletes only those tagged items
 *   - Existing products are never touched
 *   - Categories are created if missing, never deleted
 *
 * Requires WooCommerce active. If it's not, the screen explains how to
 * activate it before continuing.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The product catalogue. Edit here to tune defaults.
 *
 * Each item: name, price (string for accuracy), category slug, short desc,
 * long desc (HTML), optional sale_price, virtual flag (true for tickets).
 */
function db_shop_seed_catalogue(): array {
	return array(
		// ── Group Merchandise ───────────────────────────────────────────────────
		array(
			'name'        => '1st Davenham Necker',
			'price'       => '8.00',
			'category'    => 'group-merchandise',
			'short'       => 'Davenham group necker — group-coloured, properly sized for the section.',
			'long'        => '<p>The official 1st Davenham Scouts necker, worn by Beavers, Cubs and Scouts at section meetings, parades and group events.</p><p>Available to members on joining. If you need a replacement, order here and we\'ll pass it to your section leader at the next meeting.</p>',
			'sku'         => 'DAV-NECKER-001',
			'stock'       => 60,
		),
		array(
			'name'        => 'Davenham Scouts Hoodie (Navy)',
			'price'       => '26.00',
			'category'    => 'group-merchandise',
			'short'       => 'Soft, durable navy hoodie with the Davenham group crest on the chest.',
			'long'        => '<p>Heavyweight cotton/poly blend, navy with a printed Davenham group crest. Great for camp evenings, hikes, and rainy walks back from Peckmill.</p><p><strong>Sizing:</strong> Children\'s 7–8 through Adult XL.</p>',
			'sku'         => 'DAV-HOODIE-NV',
			'stock'       => 35,
		),
		array(
			'name'        => 'Davenham Scouts T-Shirt',
			'price'       => '12.00',
			'category'    => 'group-merchandise',
			'short'       => 'Lightweight purple t-shirt with the group crest — perfect for camp.',
			'long'        => '<p>100% cotton, Scouts purple. The everyday option for camp activities, water sports and warmer summer meetings.</p>',
			'sku'         => 'DAV-TEE-PR',
			'stock'       => 48,
		),
		array(
			'name'        => 'Davenham Scouts Polo Shirt',
			'price'       => '16.00',
			'category'    => 'group-merchandise',
			'short'       => 'Smarter polo shirt for parades, services and formal Scout events.',
			'long'        => '<p>Embroidered group crest on a classic Scouts purple polo. Smart enough for parades, comfortable for everyday meetings.</p>',
			'sku'         => 'DAV-POLO-PR',
			'stock'       => 30,
		),
		array(
			'name'        => 'Scout Group Beanie',
			'price'       => '8.00',
			'category'    => 'group-merchandise',
			'short'       => 'Warm fleece-lined beanie with a small embroidered fleur-de-lis.',
			'long'        => '<p>Soft, double-layer knit beanie in Scouts purple. One size fits most. Perfect for cold-weather camps and winter section nights at Peckmill.</p>',
			'sku'         => 'DAV-BEANIE',
			'stock'       => 50,
		),

		// ── Event Tickets ───────────────────────────────────────────────────────
		array(
			'name'        => 'Summer Camp 2026 — Beavers',
			'price'       => '45.00',
			'category'    => 'event-tickets',
			'short'       => 'Weekend Beaver camp at Peckmill Scout Wood — Friday evening to Sunday lunch.',
			'long'        => '<p>The Beavers\' annual summer camp at Peckmill Scout Wood. Friday evening arrival to Sunday lunchtime pickup. Includes food, activities, badge work, and supervision throughout.</p><p>One ticket per young person. Adult helpers go free — register interest separately via the Contact page.</p>',
			'sku'         => 'CAMP-2026-BEAV',
			'stock'       => 28,
			'virtual'     => true,
		),
		array(
			'name'        => 'Summer Camp 2026 — Cubs',
			'price'       => '65.00',
			'category'    => 'event-tickets',
			'short'       => 'Four-night Cubs camp with hikes, kayaking, archery and bonfires.',
			'long'        => '<p>Four nights at Peckmill Scout Wood for Cubs. Includes evening campfires, archery, kayaking, an off-site day-hike, and a Sunday-morning service. All food and activities included.</p><p>Bring sleeping bag, waterproofs, sturdy boots, and a sense of adventure.</p>',
			'sku'         => 'CAMP-2026-CUBS',
			'stock'       => 30,
			'virtual'     => true,
		),
		array(
			'name'        => 'Summer Camp 2026 — Scouts',
			'price'       => '85.00',
			'category'    => 'event-tickets',
			'short'       => 'Week-long Scouts camp at a national activity centre — full programme.',
			'long'        => '<p>The Scouts\' week-long summer adventure. Held at a national Scout activity centre with a full week of pioneering, water activities, hiking, and team challenges working towards section badges and Chief Scout awards.</p><p>Travel and food included.</p>',
			'sku'         => 'CAMP-2026-SCOUTS',
			'stock'       => 24,
			'virtual'     => true,
		),
		array(
			'name'        => 'Christmas Fair — Family Entry',
			'price'       => '5.00',
			'category'    => 'event-tickets',
			'short'       => 'Family ticket for the Davenham Scouts Christmas Fair (up to 4 people).',
			'long'        => '<p>Annual Christmas Fair at the Centenary Scout Hall. Stalls, raffle, refreshments, and craft activities for the young ones.</p><p>One ticket admits up to four family members. Under 5s free.</p>',
			'sku'         => 'EVT-XMAS-FAIR',
			'stock'       => 200,
			'virtual'     => true,
		),
		array(
			'name'        => 'Beavers Sleepover',
			'price'       => '15.00',
			'category'    => 'event-tickets',
			'short'       => 'Indoor sleepover at the Centenary Scout Hall — pizza, games and films.',
			'long'        => '<p>An indoor sleepover for Beavers. Saturday afternoon arrival, indoor games, themed activities, pizza tea, films and breakfast on Sunday before pickup.</p><p>Often the first night many young people spend away from home — fully supervised and cared for throughout.</p>',
			'sku'         => 'EVT-BEAV-SLP',
			'stock'       => 24,
			'virtual'     => true,
		),

		// ── Fundraising ─────────────────────────────────────────────────────────
		array(
			'name'        => '2026 Davenham Scouts Calendar',
			'price'       => '6.00',
			'category'    => 'fundraising',
			'short'       => 'A4 wall calendar featuring photography from group events through the year.',
			'long'        => '<p>Our annual photography calendar — every page features a different highlight from a Davenham Scouts event. Camps, carnivals, expeditions, badge-work moments and time on the wood.</p><p>Every sale directly supports the group\'s activities, equipment and Peckmill maintenance.</p>',
			'sku'         => 'FUND-CAL-2026',
			'stock'       => 150,
		),
		array(
			'name'        => 'Annual Fundraising Raffle Ticket',
			'price'       => '2.00',
			'category'    => 'fundraising',
			'short'       => 'A single raffle ticket — draw at the Christmas Fair.',
			'long'        => '<p>Tickets for the annual Davenham Scouts raffle. Prize draw at the Christmas Fair — winners notified by email.</p><p>Order multiples to increase your chances. All proceeds fund Scouting in Davenham.</p>',
			'sku'         => 'FUND-RAFFLE-26',
			'stock'       => 2000,
			'virtual'     => true,
		),

		// ── Equipment & Kit ─────────────────────────────────────────────────────
		array(
			'name'        => 'Activity Badge Pack',
			'price'       => '4.50',
			'category'    => 'equipment-kit',
			'short'       => 'Set of 6 cloth activity badges to be sewn onto your section uniform.',
			'long'        => '<p>Awarded by your section leader once activity requirements are complete. Replace lost badges, or pick up extras for new uniform when a young person moves up to the next section.</p>',
			'sku'         => 'KIT-BADGE-PACK',
			'stock'       => 80,
		),
		array(
			'name'        => 'Cooking Skills Book',
			'price'       => '6.00',
			'category'    => 'equipment-kit',
			'short'       => 'A Davenham-curated cookbook covering camp-fire and Dutch oven recipes.',
			'long'        => '<p>A small printed book with practical, kid-friendly camp-cooking recipes used at our group camps. Perfect for older Cubs and Scouts working towards their Cooks badges.</p>',
			'sku'         => 'KIT-COOK-BOOK',
			'stock'       => 40,
		),
	);
}

/**
 * Detect WooCommerce.
 */
function db_shop_seed_woocommerce_active(): bool {
	return class_exists( 'WooCommerce' );
}

/**
 * Create (or return existing) product category.
 */
function db_shop_seed_ensure_category( $slug, $name, $description ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $name, 'product_cat', array(
		'slug'        => $slug,
		'description' => $description,
	) );
	if ( is_wp_error( $created ) ) {
		return 0;
	}
	return (int) $created['term_id'];
}

/**
 * Create a single sample product.
 */
function db_shop_seed_create_product( array $item ): array {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array( 'error' => 'WooCommerce is not active.' );
	}

	// Skip if a sample with the same SKU already exists.
	if ( ! empty( $item['sku'] ) ) {
		$existing_id = wc_get_product_id_by_sku( $item['sku'] );
		if ( $existing_id ) {
			return array( 'skipped' => true, 'id' => $existing_id, 'reason' => 'SKU exists' );
		}
	}

	$product = new WC_Product_Simple();
	$product->set_name( $item['name'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( $item['price'] );
	if ( ! empty( $item['sale_price'] ) ) {
		$product->set_sale_price( $item['sale_price'] );
	}
	$product->set_short_description( $item['short'] );
	$product->set_description( $item['long'] );
	if ( ! empty( $item['sku'] ) ) {
		$product->set_sku( $item['sku'] );
	}
	if ( isset( $item['stock'] ) ) {
		$product->set_manage_stock( true );
		$product->set_stock_quantity( (int) $item['stock'] );
		$product->set_stock_status( 'instock' );
	}
	if ( ! empty( $item['virtual'] ) ) {
		$product->set_virtual( true );
	}

	$pid = $product->save();
	if ( ! $pid ) {
		return array( 'error' => 'Could not create product: ' . $item['name'] );
	}

	// Assign category
	$cats = scouts_shop_canonical_categories();
	if ( isset( $cats[ $item['category'] ] ) ) {
		$term_id = db_shop_seed_ensure_category( $item['category'], $cats[ $item['category'] ]['name'], $cats[ $item['category'] ]['description'] );
		if ( $term_id ) {
			wp_set_object_terms( $pid, array( $term_id ), 'product_cat', false );
		}
	}

	// Tag as a seeded sample for easy removal
	update_post_meta( $pid, '_db_sample_product', '1' );
	update_post_meta( $pid, '_db_seeded_at', current_time( 'mysql' ) );

	return array( 'ok' => true, 'id' => $pid );
}

/**
 * Seed all sample products.
 */
function db_shop_seed_run(): array {
	$cats = scouts_shop_canonical_categories();
	$cat_results = array();
	foreach ( $cats as $slug => $meta ) {
		$id = db_shop_seed_ensure_category( $slug, $meta['name'], $meta['description'] );
		$cat_results[ $slug ] = $id;
	}

	$created = 0;
	$skipped = 0;
	$errors  = array();
	foreach ( db_shop_seed_catalogue() as $item ) {
		$r = db_shop_seed_create_product( $item );
		if ( isset( $r['ok'] ) )      { $created++; }
		elseif ( isset( $r['skipped'] ) ) { $skipped++; }
		else { $errors[] = $r['error'] ?? 'unknown'; }
	}

	return array( 'created' => $created, 'skipped' => $skipped, 'errors' => $errors, 'categories' => $cat_results );
}

/**
 * Shop policy / FAQ pages to create. Each becomes a real WP page using
 * Davenham Builder block markup so editors can refine in the builder.
 */
function db_shop_pages_catalogue(): array {
	return array(
		'shop-faq' => array(
			'title' => 'Shop FAQ',
			'pattern' => 'faq',
			'hero_subtext' => 'Answers to the most common questions about ordering from our group shop.',
			'faq_items' => array(
				array( 'q' => 'How do I pay?', 'a' => 'We accept card payments online and direct bank transfer. Once your order is placed you\'ll receive a confirmation email with payment details if you\'ve chosen bank transfer.' ),
				array( 'q' => 'Can I collect my order locally instead of paying for postage?', 'a' => 'Yes — choose "Free local pickup at Peckmill Scout Wood" at checkout. We\'ll bring your order to the next section meeting your young person attends, or arrange a separate collection slot if you\'re ordering as a supporter rather than a member.' ),
				array( 'q' => 'How long does delivery take?', 'a' => 'Local pickup is usually ready within 3-5 days. Royal Mail postage typically arrives within 5-7 working days. Larger items and bespoke kit may take longer — we\'ll let you know if so.' ),
				array( 'q' => 'How do I cancel an order?', 'a' => 'Get in touch via the contact form within 14 days of placing your order and we\'ll arrange a refund (see Returns & Refunds for the full policy). Event tickets are non-refundable once paid.' ),
				array( 'q' => 'Are event tickets refundable?', 'a' => 'Tickets for camps, sleepovers and group events are non-refundable once paid, in line with how most Scout groups handle event bookings. If your young person can\'t attend due to illness please let your section leader know — we\'ll do our best to help.' ),
				array( 'q' => 'Can I transfer my ticket to someone else?', 'a' => 'Sometimes — get in touch and we\'ll do what we can. The transfer must be to another member of our group, and we need to know any allergies or medical info for the new attendee.' ),
				array( 'q' => 'I need help with sizing on a hoodie/polo', 'a' => 'Sizes vary by garment. Use the contact form and we\'ll send you the relevant size chart, or you can try on samples at the next group event.' ),
				array( 'q' => 'My order arrived damaged or didn\'t arrive', 'a' => 'Use the contact form and we\'ll sort it. Include your order number and a photo of any damage where possible.' ),
				array( 'q' => 'Where does the money from the shop go?', 'a' => 'Every penny stays with 1st Davenham Scout Group and funds local Scouting — equipment, badge programmes, camp subsidies, and Peckmill Scout Wood upkeep.' ),
			),
		),
		'shop-shipping' => array(
			'title' => 'Shipping & Pickup',
			'pattern' => 'simple',
			'hero_subtext' => 'How orders reach you — by post or by collecting from us locally.',
			'content' => "<h3>Free local pickup</h3>\n<p>If your young person attends a section, the easiest option is <strong>free local pickup at Peckmill Scout Wood</strong>. We'll bring your order to the next meeting of the section your young person attends, or arrange a separate collection slot for supporters.</p>\n<p>Pickup is usually ready within 3-5 days of ordering.</p>\n\n<h3>Royal Mail postage</h3>\n<p>We post smaller items via Royal Mail 2nd Class signed-for service. Typical delivery: 5-7 working days. Postage is charged per order, not per item — most orders are £3.50.</p>\n\n<h3>Larger items and bulk orders</h3>\n<p>For larger items (or bulk orders for fundraising events) we'll be in touch directly after your order to agree a sensible delivery or collection arrangement.</p>\n\n<h3>Event tickets</h3>\n<p>Event tickets are emailed to you on order — no postage involved. Keep the confirmation handy as proof of booking.</p>\n\n<h3>If something goes wrong</h3>\n<p>Use the <a href=\"/contact/\">contact form</a> if your order is late, damaged or missing. Include your order number and we'll sort it.</p>",
		),
		'shop-returns' => array(
			'title' => 'Returns & Refunds',
			'pattern' => 'simple',
			'hero_subtext' => 'Our returns policy in plain English.',
			'content' => "<p>1st Davenham Scout Group is a registered charity (1029781). We follow UK consumer law for returns and refunds, with one variation: <strong>event tickets are non-refundable once paid</strong>.</p>\n\n<h3>14-day cooling-off period</h3>\n<p>You have <strong>14 days from receiving a physical product</strong> to let us know you want to return it. After that, you have a further 14 days to send it back. Items must be unused, in their original packaging, and in resaleable condition.</p>\n<p>The cost of returning the item is your responsibility unless the item arrived faulty or wasn't what you ordered.</p>\n\n<h3>Damaged or wrong items</h3>\n<p>If your order arrived damaged, faulty, or isn't what you ordered, get in touch within 30 days. We'll cover the cost of returning it and either replace it or issue a full refund.</p>\n\n<h3>Refund timeframe</h3>\n<p>Once we've received the returned item and confirmed it's in resaleable condition, we'll refund you within 14 days via your original payment method. Bank transfer refunds may take an extra 3-5 working days to land.</p>\n\n<h3>Event tickets</h3>\n<p><strong>Tickets for camps, sleepovers, fairs and other group events are non-refundable once paid.</strong> This is standard practice for Scout group events — once a place is paid for, we commit to that young person's space and the costs that flow from it (food, supplies, transport).</p>\n<p>If your young person can't attend due to illness or family circumstances, please let your section leader know. We'll do what we can to help — sometimes a transfer to another member is possible, but never assume the booking can be moved without checking first.</p>\n\n<h3>How to start a return</h3>\n<p>Use the <a href=\"/contact/\">contact form</a> and tell us:</p>\n<ul><li>Your order number</li><li>What you want to return and why</li><li>Whether you'd like a refund or replacement</li></ul>\n<p>We'll come back to you with a return address and any other details within 2-3 days.</p>",
		),
	);
}

/**
 * Build builder block markup for the FAQ page.
 */
function db_shop_pages_build_faq( $title, $hero_subtext, $faq_items ) {
	$hero_attrs = array( 'heading' => $title, 'subtext' => $hero_subtext );
	$hero = '<!-- wp:davenham/page-hero ' . wp_json_encode( $hero_attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
	$faq_attrs = array(
		'heading' => 'Common questions',
		'items'   => $faq_items,
	);
	$faq = '<!-- wp:davenham/faq ' . wp_json_encode( $faq_attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
	return $hero . "\n\n" . $faq;
}

/**
 * Build builder block markup for a simple text page.
 */
function db_shop_pages_build_simple( $title, $hero_subtext, $content ) {
	$hero_attrs = array( 'heading' => $title, 'subtext' => $hero_subtext );
	$hero = '<!-- wp:davenham/page-hero ' . wp_json_encode( $hero_attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
	$rt_attrs = array( 'content' => $content, 'background' => 'white' );
	$rt = '<!-- wp:davenham/rich-text ' . wp_json_encode( $rt_attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
	return $hero . "\n\n" . $rt;
}

/**
 * Seed the shop policy pages (FAQ, Shipping, Returns).
 */
function db_shop_pages_seed(): array {
	$catalogue = db_shop_pages_catalogue();
	$created = 0;
	$skipped = 0;
	$ids = array();

	foreach ( $catalogue as $slug => $plan ) {
		// Skip if a page with this slug already exists
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$skipped++;
			$ids[ $slug ] = $existing->ID;
			continue;
		}

		switch ( $plan['pattern'] ) {
			case 'faq':
				$content = db_shop_pages_build_faq( $plan['title'], $plan['hero_subtext'], $plan['faq_items'] );
				break;
			case 'simple':
			default:
				$content = db_shop_pages_build_simple( $plan['title'], $plan['hero_subtext'], $plan['content'] );
				break;
		}

		$pid = wp_insert_post( array(
			'post_title'   => $plan['title'],
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		), true );

		if ( is_wp_error( $pid ) ) {
			continue;
		}

		update_post_meta( $pid, '_db_sample_shop_page', '1' );
		$created++;
		$ids[ $slug ] = $pid;
	}

	// Wire up WC settings if the pages exist
	if ( ! empty( $ids['shop-returns'] ) && '' === (string) get_option( 'woocommerce_terms_page_id', '' ) ) {
		update_option( 'woocommerce_terms_page_id', (int) $ids['shop-returns'] );
	}

	return array( 'created' => $created, 'skipped' => $skipped, 'ids' => $ids );
}

/**
 * Build the shop LANDING page content — a composed marketing page using
 * builder blocks. Mirrors the national Scouts shop pattern: category
 * tiles, featured product, then product grids by category, with a
 * promo banner explaining free local pickup.
 */
function db_shop_landing_build_content(): string {
	$blocks = array();

	// 1) Full-bleed shop hero — replaces flat plain page title
	$blocks[] = '<!-- wp:davenham/shop-hero ' . wp_json_encode( array(
		'eyebrow'       => 'Davenham Scouts shop',
		'heading'       => 'Skills for life — kit, tickets, and adventures.',
		'subtext'       => 'Everything we sell goes straight back into local Scouting. Skip the postage with free local pickup at Peckmill Scout Wood.',
		'imageUrl'      => '',
		'primaryText'   => 'Shop event tickets',
		'primaryUrl'    => '/product-category/event-tickets/',
		'secondaryText' => 'Browse merchandise',
		'secondaryUrl'  => '/product-category/group-merchandise/',
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';

	// 2) Large visual category tiles (replaces flat 2-col text list)
	$blocks[] = '<!-- wp:davenham/category-grid ' . wp_json_encode( array(
		'heading'    => 'Shop by category',
		'subtitle'   => 'Four ways to support the group — and find what you need for the next adventure.',
		'categories' => 'event-tickets,group-merchandise,fundraising,equipment-kit',
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';

	// Featured: most expensive event ticket (the headline camp)
	$featured_id = 0;
	$candidate = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array( 'key' => '_db_sample_product', 'value' => '1' ),
		),
		'tax_query'      => array(
			array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'event-tickets' ),
		),
		'orderby'        => 'meta_value_num',
		'meta_key'       => '_price',
		'order'          => 'DESC',
		'fields'         => 'ids',
	) );
	if ( ! empty( $candidate ) ) {
		$featured_id = (int) $candidate[0];
	}
	if ( $featured_id ) {
		$blocks[] = '<!-- wp:davenham/featured-product ' . wp_json_encode( array(
			'productId' => $featured_id,
			'eyebrow'   => 'Featured · Summer Camp',
			'ctaText'   => 'Book a place',
		), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
	}

	// Event tickets row
	$blocks[] = '<!-- wp:davenham/product-grid ' . wp_json_encode( array(
		'heading'     => 'Event tickets',
		'subtitle'    => 'Camps, sleepovers and group events.',
		'category'    => 'event-tickets',
		'count'       => 6,
		'viewAllUrl'  => '/product-category/event-tickets/',
		'viewAllText' => 'View all event tickets',
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';

	// Group merchandise row
	$blocks[] = '<!-- wp:davenham/product-grid ' . wp_json_encode( array(
		'heading'     => 'Group merchandise',
		'subtitle'    => 'Neckers, hoodies and group-branded kit.',
		'category'    => 'group-merchandise',
		'count'       => 6,
		'viewAllUrl'  => '/product-category/group-merchandise/',
		'viewAllText' => 'View all merchandise',
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';

	// Promo banner — pickup info
	$blocks[] = '<!-- wp:davenham/promo-banner ' . wp_json_encode( array(
		'eyebrow' => 'Free local pickup',
		'heading' => 'Skip the postage — collect at Peckmill Scout Wood',
		'text'    => 'Choose "Free local pickup" at checkout and we will bring your order to the next meeting of your section. No postage fee, no waiting for the post.',
		'buttons' => array(
			array( 'text' => 'View FAQ',      'url' => '/shop-faq/',      'style' => 'white' ),
			array( 'text' => 'Shipping info', 'url' => '/shop-shipping/', 'style' => 'outline' ),
		),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';

	// Fundraising row
	$blocks[] = '<!-- wp:davenham/product-grid ' . wp_json_encode( array(
		'heading'     => 'Fundraising',
		'subtitle'    => 'Calendars, raffles and one-off items.',
		'category'    => 'fundraising',
		'count'       => 4,
		'viewAllUrl'  => '/product-category/fundraising/',
		'viewAllText' => 'View all fundraising items',
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';

	// Shop-by-section tiles — the signature shop.scouts.org.uk pattern,
	// 6 coloured tiles with each section's logo.
	$blocks[] = '<!-- wp:davenham/section-shop-grid ' . wp_json_encode( array(
		'heading'  => 'Shop by section',
		'subtitle' => 'Find kit, tickets and resources tagged for your young person\'s section.',
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';

	// Trust strip
	$blocks[] = '<!-- wp:davenham/icon-feature-row ' . wp_json_encode( array(
		'heading' => 'Shopping with confidence',
		'columns' => array(
			array( 'text' => '<p><strong>🎟 Buy online</strong><br />Tickets and items checkout securely with card or bank transfer.</p>' ),
			array( 'text' => '<p><strong>📦 Local pickup</strong><br />Free collection at Peckmill — or post to your door.</p>' ),
			array( 'text' => '<p><strong>💛 Every penny stays here</strong><br />All proceeds support 1st Davenham Scouts directly.</p>' ),
		),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';

	return implode( "\n\n", $blocks );
}

/**
 * Populate the Shop page with the composed builder landing layout.
 * Backs up the existing content first.
 */
function db_shop_landing_seed(): array {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return array( 'error' => 'WooCommerce is not active.' );
	}
	$shop_id = (int) wc_get_page_id( 'shop' );
	if ( $shop_id <= 0 ) {
		return array( 'error' => 'Shop page not found.' );
	}
	$existing = get_post( $shop_id );
	if ( ! $existing ) {
		return array( 'error' => 'Shop page record missing.' );
	}
	update_post_meta( $shop_id, '_db_original_shop_content', $existing->post_content );
	$new_content = db_shop_landing_build_content();
	$result = wp_update_post( array(
		'ID'           => $shop_id,
		'post_content' => $new_content,
	), true );
	if ( is_wp_error( $result ) ) {
		return array( 'error' => $result->get_error_message() );
	}
	update_post_meta( $shop_id, '_db_landing_seeded', '1' );
	return array( 'ok' => true, 'shop_id' => $shop_id, 'bytes' => strlen( $new_content ) );
}

/**
 * Restore the original Shop page content from backup.
 */
function db_shop_landing_restore(): array {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return array( 'error' => 'WooCommerce is not active.' );
	}
	$shop_id = (int) wc_get_page_id( 'shop' );
	if ( $shop_id <= 0 ) {
		return array( 'error' => 'Shop page not found.' );
	}
	$backup = get_post_meta( $shop_id, '_db_original_shop_content', true );
	if ( '' === (string) $backup || null === $backup ) {
		return array( 'error' => 'No backup found.' );
	}
	$result = wp_update_post( array(
		'ID'           => $shop_id,
		'post_content' => $backup,
	), true );
	if ( is_wp_error( $result ) ) {
		return array( 'error' => $result->get_error_message() );
	}
	delete_post_meta( $shop_id, '_db_landing_seeded' );
	delete_post_meta( $shop_id, '_db_original_shop_content' );
	return array( 'ok' => true );
}

/**
 * Remove the seeded shop policy pages.
 */
function db_shop_pages_remove(): array {
	$q = new WP_Query( array(
		'post_type'      => 'page',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_key'       => '_db_sample_shop_page',
		'meta_value'     => '1',
		'fields'         => 'ids',
	) );
	$deleted = 0;
	foreach ( $q->posts as $pid ) {
		if ( wp_delete_post( $pid, true ) ) {
			$deleted++;
		}
	}
	wp_reset_postdata();
	return array( 'deleted' => $deleted );
}

/**
 * Delete all seeded sample products (anything tagged _db_sample_product = 1).
 */
function db_shop_seed_remove(): array {
	$q = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_key'       => '_db_sample_product',
		'meta_value'     => '1',
		'fields'         => 'ids',
	) );
	$deleted = 0;
	foreach ( $q->posts as $pid ) {
		if ( wp_delete_post( $pid, true ) ) {
			$deleted++;
		}
	}
	wp_reset_postdata();
	return array( 'deleted' => $deleted );
}

/**
 * Admin page renderer.
 */
function db_render_shop_seed_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'davenham-builder' ) );
	}

	$msg = '';
	$msg_kind = 'success';

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'db_shop_seed_action' ) ) {
		$action = sanitize_text_field( wp_unslash( $_POST['db_action'] ?? '' ) );
		if ( ! db_shop_seed_woocommerce_active() ) {
			$msg = 'WooCommerce is not active. Activate it under Plugins first.';
			$msg_kind = 'error';
		} elseif ( 'seed' === $action ) {
			$r = db_shop_seed_run();
			$msg = sprintf( 'Seeded %d products. Skipped %d (already existed). Categories: %s.',
				$r['created'], $r['skipped'], implode( ', ', array_keys( $r['categories'] ) )
			);
			if ( ! empty( $r['errors'] ) ) {
				$msg .= ' Errors: ' . implode( ' / ', $r['errors'] );
				$msg_kind = 'warning';
			}
		} elseif ( 'remove' === $action ) {
			$r = db_shop_seed_remove();
			$msg = sprintf( 'Removed %d seeded sample products.', $r['deleted'] );
		} elseif ( 'seed_pages' === $action ) {
			$r = db_shop_pages_seed();
			$msg = sprintf( 'Created %d shop policy page(s). Skipped %d (already existed).', $r['created'], $r['skipped'] );
		} elseif ( 'remove_pages' === $action ) {
			$r = db_shop_pages_remove();
			$msg = sprintf( 'Removed %d seeded shop policy page(s).', $r['deleted'] );
		} elseif ( 'seed_landing' === $action ) {
			$r = db_shop_landing_seed();
			$msg = isset( $r['ok'] ) ? 'Shop landing page populated with the builder layout. Original content backed up.' : ( 'Failed: ' . ( $r['error'] ?? 'unknown' ) );
			if ( ! isset( $r['ok'] ) ) { $msg_kind = 'error'; }
		} elseif ( 'restore_landing' === $action ) {
			$r = db_shop_landing_restore();
			$msg = isset( $r['ok'] ) ? 'Shop landing page reverted to its previous content.' : ( 'Failed: ' . ( $r['error'] ?? 'unknown' ) );
			if ( ! isset( $r['ok'] ) ) { $msg_kind = 'error'; }
		} elseif ( 'launch_store' === $action ) {
			update_option( 'woocommerce_coming_soon', 'no' );
			update_option( 'woocommerce_store_pages_only', 'no' );
			$msg = 'Shop is now live. WooCommerce "Coming Soon" mode disabled.';
		}
	}

	$catalogue = db_shop_seed_catalogue();
	$cats = scouts_shop_canonical_categories();
	?>
	<div class="wrap db-settings-wrap">
		<h1><?php esc_html_e( 'Seed Sample Shop Products', 'davenham-builder' ); ?></h1>
		<p class="db-settings-lede">
			<?php esc_html_e( 'One-click setup: creates the four product categories and adds 14 realistic sample products (neckers, hoodies, t-shirts, event tickets, fundraising items, and equipment). Every seeded product is tagged so you can clean them out before going live.', 'davenham-builder' ); ?>
		</p>

		<?php if ( ! db_shop_seed_woocommerce_active() ) : ?>
			<div class="notice notice-warning">
				<p><strong>WooCommerce is not active.</strong> Install / activate WooCommerce from the Plugins screen, then come back.</p>
			</div>
		<?php endif; ?>

		<?php if ( $msg ) : ?>
			<div class="notice notice-<?php echo esc_attr( $msg_kind ); ?> is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>

		<section class="db-settings-card">
			<h2><?php esc_html_e( 'Run the seeder', 'davenham-builder' ); ?></h2>
			<p class="db-settings-card__desc">
				<?php esc_html_e( 'Adds products if they aren\'t already present (matched by SKU). Safe to re-run. Existing products are never touched.', 'davenham-builder' ); ?>
			</p>
			<form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
				<?php wp_nonce_field( 'db_shop_seed_action' ); ?>
				<button type="submit" name="db_action" value="seed" class="button button-primary" <?php disabled( ! db_shop_seed_woocommerce_active() ); ?>>
					Seed sample products
				</button>
				<button type="submit" name="db_action" value="remove" class="button" <?php disabled( ! db_shop_seed_woocommerce_active() ); ?> onclick="return confirm('Remove all seeded sample products? This deletes only products tagged as samples — your real products are safe.');">
					Remove sample products
				</button>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button">
					View products
				</a>
			</form>
		</section>

		<section class="db-settings-card">
			<h2><?php esc_html_e( 'What gets created', 'davenham-builder' ); ?></h2>
			<p class="db-settings-card__desc">
				<?php esc_html_e( '4 categories and 14 products spanning the realistic mix for a Scout group: neckers and clothing, event tickets, fundraising items, and equipment.', 'davenham-builder' ); ?>
			</p>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th>Name</th>
						<th>Category</th>
						<th style="text-align:right;">Price</th>
						<th>SKU</th>
						<th>Type</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $catalogue as $item ) :
					$cat_label = $cats[ $item['category'] ]['name'] ?? $item['category'];
				?>
					<tr>
						<td><strong><?php echo esc_html( $item['name'] ); ?></strong><br /><small style="color:#6E6E6E;"><?php echo esc_html( $item['short'] ); ?></small></td>
						<td><?php echo esc_html( $cat_label ); ?></td>
						<td style="text-align:right;font-weight:700;color:#590FA9;">£<?php echo esc_html( $item['price'] ); ?></td>
						<td><code><?php echo esc_html( $item['sku'] ); ?></code></td>
						<td><?php echo ! empty( $item['virtual'] ) ? '<em>Ticket (virtual)</em>' : 'Physical'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<?php
		$is_coming_soon = function_exists( 'get_option' ) && 'yes' === get_option( 'woocommerce_coming_soon', 'no' );
		?>
		<?php if ( $is_coming_soon ) : ?>
		<section class="db-settings-card" style="border-left:4px solid #ED3F23;">
			<h2 style="color:#ED3F23;"><?php esc_html_e( 'Your shop is hidden by WooCommerce "Coming Soon" mode', 'davenham-builder' ); ?></h2>
			<p class="db-settings-card__desc">
				<?php esc_html_e( 'WooCommerce 9+ ships with a "Launch Your Store" feature that hides the entire shop behind a placeholder until you turn it on. Your shop is currently hidden. Click the button below to make it visible.', 'davenham-builder' ); ?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'db_shop_seed_action' ); ?>
				<button type="submit" name="db_action" value="launch_store" class="button button-primary" style="background:#ED3F23;border-color:#ED3F23;">
					🚀 Launch the shop (turn off Coming Soon)
				</button>
			</form>
		</section>
		<?php endif; ?>

		<section class="db-settings-card">
			<h2><?php esc_html_e( 'Shop landing page', 'davenham-builder' ); ?></h2>
			<p class="db-settings-card__desc">
				<?php esc_html_e( 'Replaces the default WooCommerce shop layout with a composed marketing page using builder blocks — category tiles, featured product, product grids by category, a free-pickup promo banner, and a trust strip. Mirrors the national Scouts shop layout pattern.', 'davenham-builder' ); ?>
			</p>
			<form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
				<?php wp_nonce_field( 'db_shop_seed_action' ); ?>
				<button type="submit" name="db_action" value="seed_landing" class="button button-primary" <?php disabled( ! db_shop_seed_woocommerce_active() ); ?>>
					Populate shop landing page
				</button>
				<button type="submit" name="db_action" value="restore_landing" class="button" onclick="return confirm('Restore the previous Shop page content from backup?');">
					Restore previous content
				</button>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" target="_blank" class="button">View shop page →</a>
			</form>
			<p style="margin-top:14px;font-size:13px;color:#6E6E6E;">
				<strong>What you get:</strong> a properly composed /shop/ page with category tiles, a featured ticket, product rows by category, and a pickup promo. The previous Shop page content is backed up to post meta — restore any time.
			</p>
		</section>

		<section class="db-settings-card">
			<h2><?php esc_html_e( 'Shop policy pages', 'davenham-builder' ); ?></h2>
			<p class="db-settings-card__desc">
				<?php esc_html_e( 'Creates three real pages that every shop needs: Shop FAQ, Shipping & Pickup, and Returns & Refunds. Content is pre-written for a UK Scout group (charity-compliant, plain-English, includes the standard 14-day cooling-off period). Builds them with builder blocks so editors can refine in the page builder.', 'davenham-builder' ); ?>
			</p>
			<form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
				<?php wp_nonce_field( 'db_shop_seed_action' ); ?>
				<button type="submit" name="db_action" value="seed_pages" class="button button-primary">
					Seed shop policy pages
				</button>
				<button type="submit" name="db_action" value="remove_pages" class="button" onclick="return confirm('Remove the three seeded shop policy pages? This deletes only pages tagged as samples — your real pages are safe.');">
					Remove seeded pages
				</button>
			</form>
			<p style="margin-top:14px;font-size:13px;color:#6E6E6E;">
				<strong>What gets created:</strong> /shop-faq/ · /shop-shipping/ · /shop-returns/ — all published, ready to link from the footer or shop sidebar. The Returns page is also auto-wired as WooCommerce's terms page if you haven't set one.
			</p>
		</section>

		<section class="db-settings-card">
			<h2><?php esc_html_e( 'Notes', 'davenham-builder' ); ?></h2>
			<ul style="margin:0;padding-left:20px;color:#404040;line-height:1.7;">
				<li>Products are created without photos — add product photos via <code>Products → All products → Edit</code> when you have them.</li>
				<li>Tickets are marked <strong>virtual</strong> — no shipping required.</li>
				<li>Physical items have a stock quantity; tickets have a quantity available (also stock).</li>
				<li>Each seeded product carries a <code>_db_sample_product</code> meta tag — "Remove sample products" deletes only those, never your real items.</li>
				<li>Sale prices, variations, and product photos are intentionally left for you to add — these defaults are a realistic starting point, not a finished catalogue.</li>
			</ul>
		</section>
	</div>
	<?php
}

/**
 * Auto-seed sample products + policy pages on the first admin visit once
 * WooCommerce is active. Runs at most once — sets a flag in wp_options
 * to mark completion. The "Remove sample products" action also clears
 * the flag so a re-seed is possible.
 *
 * Triggers when:
 *   - Current user can manage_woocommerce (or manage_options)
 *   - WooCommerce is active
 *   - The flag db_shop_autoseeded is not set
 *   - No published products currently exist (safety: never wipes an
 *     editor's hand-curated catalogue)
 */
function db_shop_seed_maybe_autorun() {
	if ( wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	if ( ! is_admin() ) {
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! db_shop_seed_woocommerce_active() ) {
		return;
	}

	$notice = array( 'products' => 0, 'pages' => 0, 'landing' => false, 'launched' => false );
	$did_anything = false;

	// ── Auto-disable WooCommerce "Coming Soon" / "Launch Your Store" mode
	// once products exist. WC 9+ ships with this enabled by default — it
	// hides the entire shop with a placeholder regardless of the theme.
	// We turn it off automatically when an admin visits and products are
	// present, so the freshly-seeded shop is actually visible.
	if ( '1' !== get_option( 'db_shop_launch_handled', '' ) ) {
		$product_check = new WP_Query( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		if ( ! empty( $product_check->posts ) ) {
			$was_coming_soon = 'yes' === get_option( 'woocommerce_coming_soon', 'no' );
			if ( $was_coming_soon ) {
				update_option( 'woocommerce_coming_soon', 'no' );
				update_option( 'woocommerce_store_pages_only', 'no' );
				$notice['launched'] = true;
				$did_anything = true;
			}
			update_option( 'db_shop_launch_handled', '1', false );
		}
		wp_reset_postdata();
	}

	// ── Products: seed only if there are zero existing products ────────────
	if ( '1' !== get_option( 'db_shop_products_seeded', '' ) ) {
		$existing = new WP_Query( array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		$has_products = ! empty( $existing->posts );
		wp_reset_postdata();

		if ( ! $has_products ) {
			$r = db_shop_seed_run();
			$notice['products'] = (int) ( $r['created'] ?? 0 );
			$did_anything = true;
		}
		update_option( 'db_shop_products_seeded', '1', false );
	}

	// ── Policy pages: seed only if Shop FAQ doesn't already exist ──────────
	if ( '1' !== get_option( 'db_shop_pages_seeded', '' ) ) {
		$faq = get_page_by_path( 'shop-faq' );
		if ( ! $faq ) {
			$r = db_shop_pages_seed();
			$notice['pages'] = (int) ( $r['created'] ?? 0 );
			$did_anything = true;
		}
		update_option( 'db_shop_pages_seeded', '1', false );
	}

	// ── Landing page: seed only if the Shop page doesn't have builder blocks
	if ( '1' !== get_option( 'db_shop_landing_seeded_v2', '' ) ) {
		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_id   = (int) wc_get_page_id( 'shop' );
			$shop_post = $shop_id > 0 ? get_post( $shop_id ) : null;
			$has_blocks = $shop_post && false !== strpos( (string) $shop_post->post_content, '<!-- wp:davenham/' );
			if ( $shop_post && ! $has_blocks ) {
				$r = db_shop_landing_seed();
				$notice['landing'] = ! empty( $r['ok'] );
				$did_anything = true;
			}
		}
		update_option( 'db_shop_landing_seeded_v2', '1', false );
	}

	// Keep the legacy umbrella flag in sync
	update_option( 'db_shop_autoseeded', '1', false );
	update_option( 'db_shop_autoseeded_at', current_time( 'mysql' ), false );

	if ( $did_anything ) {
		set_transient( 'db_shop_autoseed_notice', $notice, HOUR_IN_SECONDS );
	}
}
add_action( 'admin_init', 'db_shop_seed_maybe_autorun', 99 );

/**
 * Show the one-shot admin notice when the auto-seed has just run.
 */
function db_shop_seed_autoseed_notice() {
	$notice = get_transient( 'db_shop_autoseed_notice' );
	if ( ! $notice ) {
		return;
	}
	delete_transient( 'db_shop_autoseed_notice' );
	$bits = array();
	if ( ! empty( $notice['products'] ) ) { $bits[] = sprintf( '%d sample product(s)', (int) $notice['products'] ); }
	if ( ! empty( $notice['pages'] ) )    { $bits[] = sprintf( '%d policy page(s)',  (int) $notice['pages'] ); }
	if ( ! empty( $notice['landing'] ) )  { $bits[] = 'shop landing page layout'; }
	if ( ! empty( $notice['launched'] ) ) { $bits[] = '<strong>disabled WooCommerce "Coming Soon" mode</strong>'; }
	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<strong>Davenham shop set up automatically.</strong>
			<?php echo $bits ? 'Done: ' . wp_kses( implode( ', ', $bits ), array( 'strong' => array() ) ) . '.' : ''; ?>
			Manage products under
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">Products</a>
			or the
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=davenham-builder-shop-seed' ) ); ?>">Sample Shop Products</a>
			screen.
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'db_shop_seed_autoseed_notice' );

/**
 * Wipe the autoseed flag when admin chooses to remove the seeded items.
 * This lets them re-run from the admin screen if they change their mind.
 */
function db_shop_seed_clear_autoseed_flag_on_remove() {
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		return;
	}
	if ( ! isset( $_POST['db_action'] ) || ! check_admin_referer( 'db_shop_seed_action', '_wpnonce' ) ) {
		return;
	}
	$action = sanitize_text_field( wp_unslash( $_POST['db_action'] ) );
	if ( 'remove' === $action || 'remove_pages' === $action ) {
		delete_option( 'db_shop_autoseeded' );
		delete_option( 'db_shop_autoseeded_at' );
	}
}
add_action( 'admin_init', 'db_shop_seed_clear_autoseed_flag_on_remove', 5 );
