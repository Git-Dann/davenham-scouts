<?php
/**
 * Render: davenham/page-hero
 * Outputs .hero.standard — the inner-page banner used on About Us, Events, etc.
 * Falls back to the current page title when no heading is set.
 *
 * @var array $attributes Block attributes.
 */
$heading = $attributes['heading'] ?? '';
$subtext = $attributes['subtext'] ?? '';

if ( ! $heading ) {
	$heading = get_the_title();
}
?>
<section class="hero standard cf">
	<div class="wrapper alt">
		<div class="inner">
			<h2><?php echo esc_html( $heading ); ?></h2>
			<?php if ( $subtext ) : ?>
				<p><?php echo wp_kses_post( $subtext ); ?></p>
			<?php endif; ?>
		</div><!-- .inner -->
	</div><!-- .wrapper -->
</section><!-- .hero.standard -->
