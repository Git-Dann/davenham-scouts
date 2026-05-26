<?php
/**
 * Render: davenham/video-embed
 * YouTube or Vimeo embed with optional heading and caption.
 * davenham_video_to_embed() is declared once in the main plugin file.
 *
 * @var array $attributes Block attributes.
 */
$heading   = $attributes['heading']  ?? '';
$video_url = $attributes['videoUrl'] ?? '';
$caption   = $attributes['caption']  ?? '';
$embed_url = function_exists( 'davenham_video_to_embed' ) ? davenham_video_to_embed( $video_url ) : $video_url;
?>
<?php if ( $embed_url ) : ?>
<section class="video_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?>
		<div class="title_bar blue">
			<h3><?php echo esc_html( $heading ); ?></h3>
		</div>
		<?php endif; ?>
		<div class="video_wrap">
			<div class="video_responsive">
				<iframe
					src="<?php echo esc_url( $embed_url ); ?>"
					title="<?php echo esc_attr( $heading ?: 'Video' ); ?>"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen
					loading="lazy"
				></iframe>
			</div>
			<?php if ( $caption ) : ?>
			<p class="video_caption"><?php echo esc_html( $caption ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>
