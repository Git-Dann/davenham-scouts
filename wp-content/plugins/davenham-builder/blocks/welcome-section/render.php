<?php
/**
 * Render: davenham/welcome-section
 *
 * @var array $attributes Block attributes.
 */
$heading           = $attributes['heading']          ?? 'Welcome to';
$heading_highlight = $attributes['headingHighlight'] ?? '1st Davenham';
$content           = $attributes['content']          ?? '';
$button_text       = $attributes['buttonText']       ?? '';
$button_url        = $attributes['buttonUrl']        ?? '#';
$image_url         = $attributes['imageUrl']         ?? '';
$image_id          = (int) ( $attributes['imageId'] ?? 0 );

if ( $image_id ) {
	$img_html = wp_get_attachment_image( $image_id, 'large', false, [
		'class'    => 'bg',
		'decoding' => 'async',
		'alt'      => '',
	] );
} elseif ( $image_url ) {
	$img_html = '<img src="' . esc_url( $image_url ) . '" class="bg" alt="" decoding="async" />';
} else {
	$img_html = '';
}
?>
<section class="welcome_section">
	<div class="wrapper cf">
		<div class="text">
			<h1><?php echo esc_html( $heading ); ?> <span><?php echo esc_html( $heading_highlight ); ?></span></h1>
			<div class="wrap">
				<?php echo wp_kses_post( $content ); ?>
				<?php if ( $button_text ) : ?>
					<a href="<?php echo esc_url( $button_url ); ?>" class="btn green">
						<?php echo esc_html( $button_text ); ?>
					</a>
				<?php endif; ?>
			</div><!-- .wrap -->
		</div><!-- .text -->

		<?php if ( $img_html ) : ?>
		<div class="media">
			<div class="shapes cf">
				<div class="picture_wrap">
					<?php echo $img_html; ?>
				</div><!-- .picture_wrap -->
				<div class="square"></div>
			</div><!-- .shapes -->
		</div><!-- .media -->
		<?php endif; ?>
	</div><!-- .wrapper -->
</section><!-- .welcome_section -->
