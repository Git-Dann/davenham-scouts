<?php
/**
 * Render: davenham/faq
 * FAQ accordion — JS-powered open/close, CSS fallback.
 *
 * @var array $attributes Block attributes.
 */
$heading = $attributes['heading'] ?? 'Frequently Asked Questions';
$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : [];
$block_id = 'faq-' . substr( md5( serialize( $attributes ) ), 0, 8 );
?>
<section class="faq_section cf">
	<div class="wrapper">
		<div class="title_bar blue">
			<h3><?php echo esc_html( $heading ); ?></h3>
		</div><!-- .title_bar -->

		<?php if ( ! empty( $items ) ) : ?>
		<div class="faq_list" id="<?php echo esc_attr( $block_id ); ?>">
			<?php foreach ( $items as $i => $item ) :
				$q   = isset( $item['question'] ) ? trim( $item['question'] ) : '';
				$a   = isset( $item['answer']   ) ? trim( $item['answer']   ) : '';
				$iid = $block_id . '-item-' . $i;
				if ( ! $q ) continue;
			?>
			<div class="faq_item" id="<?php echo esc_attr( $iid ); ?>">
				<button
					class="faq_question"
					aria-expanded="false"
					aria-controls="<?php echo esc_attr( $iid . '-answer' ); ?>"
					type="button"
				>
					<?php echo esc_html( $q ); ?>
					<span class="faq_chevron" aria-hidden="true"></span>
				</button>
				<div
					class="faq_answer"
					id="<?php echo esc_attr( $iid . '-answer' ); ?>"
					hidden
				>
					<p><?php echo wp_kses_post( $a ); ?></p>
				</div>
			</div><!-- .faq_item -->
			<?php endforeach; ?>
		</div><!-- .faq_list -->
		<?php else : ?>
			<p style="padding:20px 0;color:#aaa;">No FAQ items added yet.</p>
		<?php endif; ?>
	</div><!-- .wrapper -->
</section><!-- .faq_section -->

<script>
(function(){
	var list = document.getElementById( '<?php echo esc_js( $block_id ); ?>' );
	if ( ! list ) return;
	list.addEventListener( 'click', function( e ) {
		var btn = e.target.closest( '.faq_question' );
		if ( ! btn ) return;
		var item   = btn.closest( '.faq_item' );
		var answer = item.querySelector( '.faq_answer' );
		var open   = btn.getAttribute( 'aria-expanded' ) === 'true';
		// Close all
		list.querySelectorAll( '.faq_question' ).forEach( function( b ) {
			b.setAttribute( 'aria-expanded', 'false' );
			b.closest( '.faq_item' ).classList.remove( 'faq_item--open' );
			var a = b.closest( '.faq_item' ).querySelector( '.faq_answer' );
			if ( a ) a.hidden = true;
		} );
		// Open clicked (if it was closed)
		if ( ! open ) {
			btn.setAttribute( 'aria-expanded', 'true' );
			item.classList.add( 'faq_item--open' );
			answer.hidden = false;
		}
	} );
})();
</script>
