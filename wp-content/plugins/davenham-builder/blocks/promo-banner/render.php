<?php
$eyebrow    = $attributes['eyebrow'] ?? '';
$heading    = $attributes['heading'] ?? '';
$text       = $attributes['text'] ?? '';
$button_txt = $attributes['buttonText'] ?? '';
$button_url = $attributes['buttonUrl'] ?? '';
?>
<section class="promo_banner_section cf">
	<div class="wrapper">
		<div class="promo_banner">
			<?php if ( $eyebrow ) : ?><div class="promo_banner__eyebrow"><?php echo esc_html( $eyebrow ); ?></div><?php endif; ?>
			<?php if ( $heading ) : ?><h2><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php if ( $text ) : ?><div><?php echo wp_kses_post( $text ); ?></div><?php endif; ?>
			<?php if ( $button_txt && $button_url ) : ?><div class="promo_banner__actions"><a class="btn white" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_txt ); ?></a></div><?php endif; ?>
		</div>
	</div>
</section>
