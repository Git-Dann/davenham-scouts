<?php
$heading = $attributes['heading'] ?? 'Trusted by';
$logos   = is_array( $attributes['logos'] ?? null ) ? $attributes['logos'] : array();
?>
<section class="logo_strip_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="logo_strip">
			<?php foreach ( $logos as $logo ) : ?>
				<div class="logo_strip__item">
					<?php
					$img = '';
					if ( ! empty( $logo['imageId'] ) ) {
						$img = wp_get_attachment_image( (int) $logo['imageId'], 'medium', false );
					} elseif ( ! empty( $logo['imageUrl'] ) ) {
						$img = '<img src="' . esc_url( $logo['imageUrl'] ) . '" alt="' . esc_attr( $logo['name'] ?? '' ) . '" />';
					}
					if ( ! $img ) {
						continue;
					}
					?>
					<?php if ( ! empty( $logo['url'] ) ) : ?><a href="<?php echo esc_url( $logo['url'] ); ?>"><?php echo $img; ?></a><?php else : ?><?php echo $img; ?><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
