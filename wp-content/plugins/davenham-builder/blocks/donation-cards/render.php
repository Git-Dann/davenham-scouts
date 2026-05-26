<?php
$heading = $attributes['heading'] ?? 'Support our next project';
$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array();
?>
<section class="donation_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="donation_cards">
			<?php foreach ( $items as $item ) : ?>
				<div class="donation_card">
					<?php if ( ! empty( $item['title'] ) ) : ?><div class="donation_card__title"><?php echo esc_html( $item['title'] ); ?></div><?php endif; ?>
					<?php if ( ! empty( $item['text'] ) ) : ?><div class="donation_card__text"><?php echo wp_kses_post( $item['text'] ); ?></div><?php endif; ?>
					<?php if ( ! empty( $item['buttonText'] ) && ! empty( $item['buttonUrl'] ) ) : ?><p><a class="btn outline" href="<?php echo esc_url( $item['buttonUrl'] ); ?>"><?php echo esc_html( $item['buttonText'] ); ?></a></p><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
