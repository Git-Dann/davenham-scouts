<?php
$heading = $attributes['heading'] ?? 'Explore more';
$cards   = is_array( $attributes['cards'] ?? null ) ? $attributes['cards'] : array();
?>
<section class="card_grid_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="card_grid">
			<?php foreach ( $cards as $card ) : ?>
				<article class="card_grid__item">
					<?php if ( ! empty( $card['imageId'] ) ) : ?>
						<div class="card_grid__image"><?php echo wp_get_attachment_image( (int) $card['imageId'], 'large', false ); ?></div>
					<?php elseif ( ! empty( $card['imageUrl'] ) ) : ?>
						<div class="card_grid__image"><img src="<?php echo esc_url( $card['imageUrl'] ); ?>" alt="" /></div>
					<?php endif; ?>
					<?php if ( ! empty( $card['title'] ) ) : ?><h3 class="card_grid__title"><?php echo esc_html( $card['title'] ); ?></h3><?php endif; ?>
					<?php if ( ! empty( $card['text'] ) ) : ?><div class="card_grid__text"><?php echo wp_kses_post( $card['text'] ); ?></div><?php endif; ?>
					<?php if ( ! empty( $card['buttonText'] ) && ! empty( $card['buttonUrl'] ) ) : ?><p><a class="btn outline" href="<?php echo esc_url( $card['buttonUrl'] ); ?>"><?php echo esc_html( $card['buttonText'] ); ?></a></p><?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
