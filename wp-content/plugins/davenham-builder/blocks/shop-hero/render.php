<?php
/**
 * Render: davenham/shop-hero
 *
 * Full-bleed shop hero — background image (or brand gradient), purple
 * gradient overlay, eyebrow label, large heading, body text and up to
 * two CTAs. Built to match the visual weight of national retail shop
 * landing heroes.
 *
 * @var array $attributes
 */
$eyebrow        = trim( (string) ( $attributes['eyebrow']        ?? '' ) );
$heading        = trim( (string) ( $attributes['heading']        ?? '' ) );
$subtext        = trim( (string) ( $attributes['subtext']        ?? '' ) );
$image_url      = trim( (string) ( $attributes['imageUrl']       ?? '' ) );
$primary_text   = trim( (string) ( $attributes['primaryText']    ?? '' ) );
$primary_url    = trim( (string) ( $attributes['primaryUrl']     ?? '' ) );
$secondary_text = trim( (string) ( $attributes['secondaryText']  ?? '' ) );
$secondary_url  = trim( (string) ( $attributes['secondaryUrl']   ?? '' ) );

$has_image = $image_url !== '';
?>
<section class="shop_hero <?php echo $has_image ? 'shop_hero--with-image' : 'shop_hero--gradient'; ?>">
	<?php if ( $has_image ) : ?>
		<img class="shop_hero__bg" src="<?php echo esc_url( $image_url ); ?>" alt="" aria-hidden="true" loading="eager" />
	<?php endif; ?>
	<div class="shop_hero__overlay" aria-hidden="true"></div>

	<div class="wrapper">
		<div class="shop_hero__inner">
			<?php if ( $eyebrow ) : ?>
				<span class="shop_hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
			<?php endif; ?>
			<?php if ( $heading ) : ?>
				<h1 class="shop_hero__heading"><?php echo esc_html( $heading ); ?></h1>
			<?php endif; ?>
			<?php if ( $subtext ) : ?>
				<p class="shop_hero__subtext"><?php echo esc_html( $subtext ); ?></p>
			<?php endif; ?>
			<?php if ( $primary_text || $secondary_text ) : ?>
			<div class="shop_hero__actions">
				<?php if ( $primary_text && $primary_url ) : ?>
					<a class="shop_hero__btn shop_hero__btn--primary" href="<?php echo esc_url( $primary_url ); ?>">
						<?php echo esc_html( $primary_text ); ?> →
					</a>
				<?php endif; ?>
				<?php if ( $secondary_text && $secondary_url ) : ?>
					<a class="shop_hero__btn shop_hero__btn--outline" href="<?php echo esc_url( $secondary_url ); ?>">
						<?php echo esc_html( $secondary_text ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
