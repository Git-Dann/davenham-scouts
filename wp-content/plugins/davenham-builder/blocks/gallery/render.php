<?php
/**
 * Render: davenham/gallery
 * Responsive photo grid.
 *
 * @var array $attributes Block attributes.
 */
$heading = $attributes['heading'] ?? 'Photo Gallery';
$images  = is_array( $attributes['images'] ?? null ) ? $attributes['images'] : [];
?>
<section class="gallery_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?>
		<div class="title_bar blue">
			<h3><?php echo esc_html( $heading ); ?></h3>
		</div><!-- .title_bar -->
		<?php endif; ?>

		<?php if ( ! empty( $images ) ) : ?>
		<div class="gallery_grid">
			<?php foreach ( $images as $img ) :
				$url     = isset( $img['url']     ) ? esc_url( $img['url'] )     : '';
				$alt     = isset( $img['alt']     ) ? esc_attr( $img['alt'] )    : '';
				$caption = isset( $img['caption'] ) ? trim( $img['caption'] )   : '';
				if ( ! $url ) continue;
			?>
			<figure class="gallery_item">
				<a href="<?php echo $url; ?>" class="gallery_item__link" target="_blank" rel="noreferrer">
					<img src="<?php echo $url; ?>" alt="<?php echo $alt; ?>" loading="lazy" />
				</a>
				<?php if ( $caption ) : ?>
				<figcaption class="gallery_item__caption"><?php echo esc_html( $caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
			<?php endforeach; ?>
		</div><!-- .gallery_grid -->
		<?php else : ?>
			<p style="padding:20px 0;color:#aaa;">No images added yet.</p>
		<?php endif; ?>
	</div><!-- .wrapper -->
</section><!-- .gallery_section -->
