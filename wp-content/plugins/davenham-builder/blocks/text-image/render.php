<?php
/**
 * Render: davenham/text-image
 *
 * @var array $attributes Block attributes.
 */
$heading        = $attributes['heading']       ?? '';
$content        = $attributes['content']       ?? '';
$image_url      = $attributes['imageUrl']      ?? '';
$image_id       = (int) ( $attributes['imageId'] ?? 0 );
$image_position = $attributes['imagePosition'] ?? 'right';

if ( $image_id ) {
	$img_html = wp_get_attachment_image( $image_id, 'large', false, [
		'decoding' => 'async',
		'alt'      => '',
	] );
} elseif ( $image_url ) {
	$img_html = '<img src="' . esc_url( $image_url ) . '" alt="" decoding="async" />';
} else {
	$img_html = '';
}

$is_left = ( $image_position === 'left' );
?>
<div class="white_container cf">
	<div class="wrapper cf<?php echo $is_left ? ' image-left' : ''; ?>">
		<?php if ( $is_left && $img_html ) : ?>
			<div class="media"><?php echo $img_html; ?></div>
		<?php endif; ?>

		<div class="text">
			<?php if ( $heading ) : ?>
				<h2><?php echo wp_kses_post( $heading ); ?></h2>
			<?php endif; ?>
			<div class="wrap">
				<?php echo wp_kses_post( $content ); ?>
			</div><!-- .wrap -->
		</div><!-- .text -->

		<?php if ( ! $is_left && $img_html ) : ?>
			<div class="media"><?php echo $img_html; ?></div>
		<?php endif; ?>
	</div><!-- .wrapper -->
</div><!-- .white_container -->
