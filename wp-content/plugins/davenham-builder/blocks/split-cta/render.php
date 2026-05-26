<?php
$heading = $attributes['heading'] ?? 'How can we help?';
?>
<section class="split_cta_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="split_cta">
			<div class="split_cta__card">
				<?php if ( ! empty( $attributes['leftTitle'] ) ) : ?><h3><?php echo esc_html( $attributes['leftTitle'] ); ?></h3><?php endif; ?>
				<?php if ( ! empty( $attributes['leftText'] ) ) : ?><div><?php echo wp_kses_post( $attributes['leftText'] ); ?></div><?php endif; ?>
				<?php if ( ! empty( $attributes['leftButtonText'] ) && ! empty( $attributes['leftButtonUrl'] ) ) : ?><p><a class="btn white" href="<?php echo esc_url( $attributes['leftButtonUrl'] ); ?>"><?php echo esc_html( $attributes['leftButtonText'] ); ?></a></p><?php endif; ?>
			</div>
			<div class="split_cta__card split_cta__card--alt">
				<?php if ( ! empty( $attributes['rightTitle'] ) ) : ?><h3><?php echo esc_html( $attributes['rightTitle'] ); ?></h3><?php endif; ?>
				<?php if ( ! empty( $attributes['rightText'] ) ) : ?><div><?php echo wp_kses_post( $attributes['rightText'] ); ?></div><?php endif; ?>
				<?php if ( ! empty( $attributes['rightButtonText'] ) && ! empty( $attributes['rightButtonUrl'] ) ) : ?><p><a class="btn white" href="<?php echo esc_url( $attributes['rightButtonUrl'] ); ?>"><?php echo esc_html( $attributes['rightButtonText'] ); ?></a></p><?php endif; ?>
			</div>
		</div>
	</div>
</section>
