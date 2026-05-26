<?php
$quote       = $attributes['quote'] ?? '';
$attribution = $attributes['attribution'] ?? '';
?>
<section class="quote_banner_section cf">
	<div class="wrapper">
		<div class="quote_banner">
			<blockquote>
				<?php if ( $quote ) : ?><div class="quote_banner__text"><?php echo wp_kses_post( $quote ); ?></div><?php endif; ?>
				<?php if ( $attribution ) : ?><footer class="quote_banner__source"><?php echo esc_html( $attribution ); ?></footer><?php endif; ?>
			</blockquote>
		</div>
	</div>
</section>
