<?php
/**
 * Render: davenham/category-grid
 *
 * Shows large clickable tiles for browsing shop product categories.
 * Pulls real category terms from WooCommerce when active, so counts
 * and URLs are always live.
 *
 * @var array $attributes
 */
$heading    = trim( (string) ( $attributes['heading']    ?? 'Shop by category' ) );
$subtitle   = trim( (string) ( $attributes['subtitle']   ?? '' ) );
$slugs_raw  = (string) ( $attributes['categories'] ?? '' );

// Comma-separated slug list — fall back to the canonical four.
$slugs = array_filter( array_map( 'trim', explode( ',', $slugs_raw ) ) );
if ( empty( $slugs ) ) {
	$slugs = array( 'event-tickets', 'group-merchandise', 'fundraising', 'equipment-kit' );
}

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

// Map of canonical slugs → icon, accent colour, blurb.
$visual = array(
	'event-tickets'     => array( 'icon' => '🎟', 'accent' => '#590FA9', 'blurb' => 'Camps, sleepovers, fairs and other ticketed events.' ),
	'group-merchandise' => array( 'icon' => '👕', 'accent' => '#003982', 'blurb' => 'Davenham-branded neckers, hoodies, polos and accessories.' ),
	'fundraising'       => array( 'icon' => '💛', 'accent' => '#FFE627', 'blurb' => 'Calendars, raffles and one-off items that fund our adventures.' ),
	'equipment-kit'     => array( 'icon' => '🎒', 'accent' => '#008A1C', 'blurb' => 'Section kit, badge packs and printed resources.' ),
);

$tiles = array();
foreach ( $slugs as $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) {
		continue;
	}
	$url = get_term_link( $term );
	if ( is_wp_error( $url ) ) {
		continue;
	}
	$v = $visual[ $slug ] ?? array( 'icon' => '🛍', 'accent' => '#590FA9', 'blurb' => '' );
	$tiles[] = array(
		'name'   => $term->name,
		'url'    => $url,
		'count'  => (int) $term->count,
		'blurb'  => $term->description ?: $v['blurb'],
		'icon'   => $v['icon'],
		'accent' => $v['accent'],
	);
}

if ( empty( $tiles ) ) {
	return;
}
?>
<section class="category_grid">
	<div class="wrapper">
		<div class="category_grid__header">
			<h2 class="category_grid__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( $subtitle ) : ?>
				<p class="category_grid__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>

		<div class="category_grid__tiles">
			<?php foreach ( $tiles as $tile ) : ?>
			<a class="category_tile" href="<?php echo esc_url( $tile['url'] ); ?>" style="--category-accent: <?php echo esc_attr( $tile['accent'] ); ?>;">
				<div class="category_tile__icon" aria-hidden="true"><?php echo esc_html( $tile['icon'] ); ?></div>
				<div class="category_tile__body">
					<h3 class="category_tile__name"><?php echo esc_html( $tile['name'] ); ?></h3>
					<?php if ( $tile['blurb'] ) : ?>
						<p class="category_tile__blurb"><?php echo esc_html( $tile['blurb'] ); ?></p>
					<?php endif; ?>
					<span class="category_tile__count"><?php echo (int) $tile['count']; ?> <?php echo esc_html( _n( 'item', 'items', $tile['count'], 'davenham-builder' ) ); ?></span>
				</div>
				<span class="category_tile__arrow" aria-hidden="true">→</span>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
