<?php
/**
 * Render: davenham/category-grid
 *
 * Large image-driven category tiles. Each tile is a coloured panel with
 * a decorative pattern, big category icon, name and count. Designed to
 * match the scale of shop.scouts.org.uk's "Shop by section" pattern.
 *
 * @var array $attributes
 */
$heading    = trim( (string) ( $attributes['heading']    ?? 'Shop by category' ) );
$subtitle   = trim( (string) ( $attributes['subtitle']   ?? '' ) );
$slugs_raw  = (string) ( $attributes['categories'] ?? '' );

$slugs = array_filter( array_map( 'trim', explode( ',', $slugs_raw ) ) );
if ( empty( $slugs ) ) {
	$slugs = array( 'event-tickets', 'group-merchandise', 'fundraising', 'equipment-kit' );
}

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

// Per-category visual treatment. Background uses brand gradients,
// not just flat colours, for proper retail feel.
$visual = array(
	'event-tickets' => array(
		'icon'     => '🎟',
		'bg'       => 'linear-gradient(135deg, #590FA9 0%, #003982 100%)',
		'pattern'  => 'tickets',
		'blurb'    => 'Camps, sleepovers and group events',
		'cta'      => 'Browse tickets',
	),
	'group-merchandise' => array(
		'icon'     => '👕',
		'bg'       => 'linear-gradient(135deg, #003982 0%, #088486 100%)',
		'pattern'  => 'stripes',
		'blurb'    => 'Neckers, hoodies and branded kit',
		'cta'      => 'Shop the look',
	),
	'fundraising' => array(
		'icon'     => '💛',
		'bg'       => 'linear-gradient(135deg, #FF912A 0%, #ED3F23 100%)',
		'pattern'  => 'dots',
		'blurb'    => 'Calendars, raffles and one-off items',
		'cta'      => 'Support the group',
	),
	'equipment-kit' => array(
		'icon'     => '🎒',
		'bg'       => 'linear-gradient(135deg, #205B41 0%, #008A1C 100%)',
		'pattern'  => 'diagonals',
		'blurb'    => 'Section kit, badge packs and resources',
		'cta'      => 'Get kitted out',
	),
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
	$v = $visual[ $slug ] ?? array( 'icon' => '🛍', 'bg' => 'linear-gradient(135deg, #590FA9 0%, #003982 100%)', 'pattern' => 'dots', 'blurb' => '', 'cta' => 'Browse' );
	$tiles[] = array(
		'name'    => $term->name,
		'url'     => $url,
		'count'   => (int) $term->count,
		'blurb'   => $term->description ?: $v['blurb'],
		'icon'    => $v['icon'],
		'bg'      => $v['bg'],
		'pattern' => $v['pattern'],
		'cta'     => $v['cta'],
	);
}

if ( empty( $tiles ) ) {
	return;
}
?>
<section class="category_showcase">
	<div class="wrapper">
		<?php if ( $heading || $subtitle ) : ?>
		<div class="category_showcase__header">
			<?php if ( $heading ) : ?>
				<h2 class="category_showcase__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $subtitle ) : ?>
				<p class="category_showcase__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="category_showcase__tiles">
			<?php foreach ( $tiles as $tile ) : ?>
			<a class="category_showcase__tile category_showcase__tile--<?php echo esc_attr( $tile['pattern'] ); ?>"
			   href="<?php echo esc_url( $tile['url'] ); ?>"
			   style="--tile-bg: <?php echo esc_attr( $tile['bg'] ); ?>;">
				<div class="category_showcase__tile-bg" aria-hidden="true"></div>
				<div class="category_showcase__tile-pattern" aria-hidden="true"></div>

				<div class="category_showcase__tile-content">
					<span class="category_showcase__tile-icon" aria-hidden="true"><?php echo esc_html( $tile['icon'] ); ?></span>
					<div class="category_showcase__tile-meta">
						<h3 class="category_showcase__tile-name"><?php echo esc_html( $tile['name'] ); ?></h3>
						<?php if ( $tile['blurb'] ) : ?>
							<p class="category_showcase__tile-blurb"><?php echo esc_html( $tile['blurb'] ); ?></p>
						<?php endif; ?>
					</div>
					<div class="category_showcase__tile-footer">
						<span class="category_showcase__tile-cta"><?php echo esc_html( $tile['cta'] ); ?> →</span>
						<span class="category_showcase__tile-count"><?php echo (int) $tile['count']; ?> <?php echo esc_html( _n( 'item', 'items', $tile['count'], 'davenham-builder' ) ); ?></span>
					</div>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
