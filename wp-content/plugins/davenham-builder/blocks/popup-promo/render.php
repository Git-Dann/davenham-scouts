<?php
$heading     = $attributes['heading'] ?? 'Open popup';
$button_text = $attributes['buttonText'] ?? 'Open details';
$body        = $attributes['body'] ?? '';
$popup_id    = 'db-popup-' . substr( md5( wp_json_encode( $attributes ) ), 0, 8 );
?>
<section class="popup_promo_section cf">
	<div class="wrapper">
		<div class="popup_promo__card">
			<h2><?php echo esc_html( $heading ); ?></h2>
			<p><button type="button" class="btn white" data-db-popup-trigger="<?php echo esc_attr( $popup_id ); ?>"><?php echo esc_html( $button_text ); ?></button></p>
		</div>
		<dialog class="popup_promo__dialog db-popup-modal" data-db-popup="<?php echo esc_attr( $popup_id ); ?>">
			<div class="popup_promo__dialog-inner">
				<button class="popup_promo__close" type="button" data-db-popup-close>&times;</button>
				<h3><?php echo esc_html( $heading ); ?></h3>
				<div><?php echo wp_kses_post( $body ); ?></div>
			</div>
		</dialog>
	</div>
</section>
