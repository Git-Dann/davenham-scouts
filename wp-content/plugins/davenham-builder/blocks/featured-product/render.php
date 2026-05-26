<?php
/**
 * Render: davenham/featured-product
 *
 * Highlights one product as a horizontal hero card. Useful at the top of
 * the homepage or inside news articles to promote a campaign or event.
 *
 * @var array $attributes
 */
$product_id = (int) ( $attributes['productId'] ?? 0 );
$eyebrow    = trim( (string) ( $attributes['eyebrow']  ?? 'Featured' ) );
$cta_text   = trim( (string) ( $attributes['ctaText']  ?? 'View product' ) );

if ( ! class_exists( 'WooCommerce' ) || ! $product_id ) {
	return;
}

$product = wc_get_product( $product_id );
if ( ! $product || 'publish' !== $product->get_status() ) {
	return;
}

$url   = get_permalink( $product_id );
$title = $product->get_name();
$short = $product->get_short_description() ?: $product->get_description();
$price = $product->get_price_html();
$thumb = get_the_post_thumbnail_url( $product_id, 'large' );
?>
<section class="featured_product">
	<div class="wrapper">
		<div class="featured_product__card">
			<div class="featured_product__media">
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
				<?php else : ?>
					<div class="featured_product__placeholder" aria-hidden="true">⚜</div>
				<?php endif; ?>
			</div>
			<div class="featured_product__body">
				<?php if ( $eyebrow ) : ?>
					<span class="featured_product__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>
				<h2 class="featured_product__title"><?php echo esc_html( $title ); ?></h2>
				<?php if ( $short ) : ?>
					<div class="featured_product__desc"><?php echo wp_kses_post( wpautop( $short ) ); ?></div>
				<?php endif; ?>
				<div class="featured_product__price"><?php echo wp_kses_post( $price ); ?></div>
				<a class="featured_product__btn" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $cta_text ); ?> →</a>
			</div>
		</div>
	</div>
</section>
