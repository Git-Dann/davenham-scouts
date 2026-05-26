<?php
$heading    = $attributes['heading'] ?? 'Stay in the loop';
$text       = $attributes['text'] ?? '';
$embed_code = $attributes['embedCode'] ?? '';
$button_txt = $attributes['buttonText'] ?? '';
$button_url = $attributes['buttonUrl'] ?? '';
$allowed    = wp_kses_allowed_html( 'post' );
$allowed['form'] = array(
	'action' => true,
	'method' => true,
	'class' => true,
	'id' => true,
	'target' => true,
);
$allowed['input'] = array(
	'type' => true,
	'name' => true,
	'value' => true,
	'placeholder' => true,
	'class' => true,
	'id' => true,
	'checked' => true,
	'required' => true,
);
$allowed['button'] = array(
	'type' => true,
	'class' => true,
);
$allowed['label'] = array(
	'for' => true,
	'class' => true,
);
$allowed['iframe'] = array(
	'src' => true,
	'width' => true,
	'height' => true,
	'style' => true,
	'loading' => true,
	'allowfullscreen' => true,
	'referrerpolicy' => true,
	'title' => true,
);
?>
<section class="newsletter_section cf">
	<div class="wrapper">
		<div class="newsletter_signup">
			<?php if ( $heading ) : ?><h2><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php if ( $text ) : ?><div><?php echo wp_kses_post( $text ); ?></div><?php endif; ?>
			<?php if ( $embed_code ) : ?>
				<div class="newsletter_signup__embed"><?php echo do_shortcode( wp_kses( $embed_code, $allowed ) ); ?></div>
			<?php elseif ( $button_txt && $button_url ) : ?>
				<div class="newsletter_signup__form"><a class="btn white" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_txt ); ?></a></div>
			<?php endif; ?>
		</div>
	</div>
</section>
