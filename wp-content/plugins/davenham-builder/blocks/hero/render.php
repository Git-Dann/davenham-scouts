<?php
/**
 * Render: davenham/hero
 *
 * @var array $attributes Block attributes.
 */
$heading   = $attributes['heading']            ?? 'We help young people gain the skills they need to shine bright';
$subtext   = $attributes['subtext']            ?? '';
$bg_url    = $attributes['backgroundImageUrl'] ?? '';
$bg_id     = (int) ( $attributes['backgroundImageId'] ?? 0 );
$buttons   = $attributes['buttons']            ?? [];

// Prefer responsive srcset markup when an attachment ID is available.
if ( $bg_id ) {
	$img_html = wp_get_attachment_image( $bg_id, 'full', false, [
		'class'        => 'bg',
		'fetchpriority' => 'high',
		'decoding'     => 'async',
		'alt'          => '',
	] );
} elseif ( $bg_url ) {
	$img_html = '<img src="' . esc_url( $bg_url ) . '" class="bg" alt="" fetchpriority="high" decoding="async" />';
} else {
	$img_html = '';
}
?>
<section class="hero cf">
	<?php echo $img_html; ?>
	<div class="wrapper">
		<div class="box cf">
			<div class="wrap">
				<h2><?php echo wp_kses_post( $heading ); ?></h2>
				<?php if ( $subtext ) : ?>
					<div class="hero-subtext"><?php echo wp_kses_post( $subtext ); ?></div>
				<?php endif; ?>
				<?php if ( ! empty( $buttons ) ) : ?>
				<div class="btn_row">
					<?php foreach ( $buttons as $btn ) : ?>
						<a href="<?php echo esc_url( $btn['url'] ?? '#' ); ?>"
						   class="btn <?php echo esc_attr( $btn['style'] ?? 'outline' ); ?>">
							<?php echo esc_html( $btn['text'] ?? '' ); ?>
						</a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div><!-- .wrap -->
		</div><!-- .box -->
	</div><!-- .wrapper -->
</section><!-- .hero -->
