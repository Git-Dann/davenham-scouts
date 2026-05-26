<?php
/**
 * Render: davenham/product-grid
 *
 * Pulls products from WooCommerce by category (or all) and renders a
 * compact grid of cards. Safe to use even when WooCommerce isn't active
 * — renders a polite empty state instead of erroring.
 *
 * @var array $attributes Block attributes.
 */
$heading      = trim( (string) ( $attributes['heading']     ?? 'Shop' ) );
$subtitle     = trim( (string) ( $attributes['subtitle']    ?? '' ) );
$category     = trim( (string) ( $attributes['category']    ?? '' ) );
$count        = max( 1, (int) ( $attributes['count']        ?? 6 ) );
$view_all_url = trim( (string) ( $attributes['viewAllUrl']  ?? '/shop/' ) );
$view_all_txt = trim( (string) ( $attributes['viewAllText'] ?? 'View all products' ) );

if ( ! class_exists( 'WooCommerce' ) ) {
	?>
	<section class="product_grid product_grid--unavailable">
		<div class="wrapper">
			<h2 class="product_grid__heading"><?php echo esc_html( $heading ); ?></h2>
			<p class="product_grid__empty">The shop is not available yet — check back soon.</p>
		</div>
	</section>
	<?php
	return;
}

$args = array(
	'post_type'      => 'product',
	'post_status'    => 'publish',
	'posts_per_page' => $count,
	'orderby'        => 'menu_order title',
	'order'          => 'ASC',
	'meta_query'     => array(
		array(
			'key'     => '_visibility',
			'value'   => array( 'hidden', 'search' ),
			'compare' => 'NOT IN',
		),
	),
);
if ( $category ) {
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $category,
		),
	);
}
$query = new WP_Query( $args );

if ( ! $query->have_posts() ) {
	wp_reset_postdata();
	// Silently hide empty grids — better than leaving an empty section
	// staring back at the visitor.
	return;
}
?>
<section class="product_grid">
	<div class="wrapper">
		<div class="product_grid__header">
			<h2 class="product_grid__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( $subtitle ) : ?>
				<p class="product_grid__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>

		<div class="product_grid__items">
			<?php while ( $query->have_posts() ) :
				$query->the_post();
				$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
				if ( ! $product ) { continue; }
			?>
			<a class="product_card" href="<?php the_permalink(); ?>">
				<div class="product_card__media">
					<?php if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => esc_attr( get_the_title() ) ) );
					} else { ?>
						<div class="product_card__placeholder" aria-hidden="true">
							<span>⚜</span>
						</div>
					<?php } ?>
					<?php if ( $product->is_on_sale() ) : ?>
						<span class="product_card__sale">Sale</span>
					<?php endif; ?>
				</div>
				<div class="product_card__body">
					<h3 class="product_card__title"><?php the_title(); ?></h3>
					<div class="product_card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				</div>
			</a>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>

		<?php if ( $view_all_url && $view_all_txt ) : ?>
		<div class="product_grid__footer">
			<a class="product_grid__btn" href="<?php echo esc_url( $view_all_url ); ?>"><?php echo esc_html( $view_all_txt ); ?> →</a>
		</div>
		<?php endif; ?>
	</div>
</section>
