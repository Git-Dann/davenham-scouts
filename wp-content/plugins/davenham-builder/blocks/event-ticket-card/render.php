<?php
/**
 * Render: davenham/event-ticket-card
 *
 * Highlights one ticketed event. Pulls price + add-to-cart URL from a
 * linked WooCommerce product when productId is set. Otherwise renders
 * as a pure information card (admin can link "secondary URL" instead).
 *
 * @var array $attributes
 */
$eyebrow       = trim( (string) ( $attributes['eyebrow']       ?? 'Event' ) );
$title_attr    = trim( (string) ( $attributes['title']         ?? '' ) );
$product_id    = (int) ( $attributes['productId'] ?? 0 );
$date_line     = trim( (string) ( $attributes['dateLine']      ?? '' ) );
$time_line     = trim( (string) ( $attributes['timeLine']      ?? '' ) );
$location      = trim( (string) ( $attributes['location']      ?? '' ) );
$age_range     = trim( (string) ( $attributes['ageRange']      ?? '' ) );
$included      = trim( (string) ( $attributes['included']      ?? '' ) );
$bring         = trim( (string) ( $attributes['bring']         ?? '' ) );
$cta_text      = trim( (string) ( $attributes['ctaText']       ?? 'Book your place' ) );
$secondary_url = trim( (string) ( $attributes['secondaryUrl']  ?? '' ) );
$secondary_txt = trim( (string) ( $attributes['secondaryText'] ?? '' ) );

// Resolve product info if linked
$display_title = $title_attr;
$price_html    = '';
$primary_url   = '';
$can_book      = false;
$is_in_stock   = true;

if ( $product_id && class_exists( 'WooCommerce' ) ) {
	$product = wc_get_product( $product_id );
	if ( $product && 'publish' === $product->get_status() ) {
		if ( ! $display_title ) {
			$display_title = $product->get_name();
		}
		$price_html  = $product->get_price_html();
		$primary_url = get_permalink( $product_id );
		$can_book    = true;
		$is_in_stock = $product->is_in_stock();
	}
}

// If no product linked but admin provided a secondary URL, use it
if ( ! $can_book && $secondary_url ) {
	$primary_url = $secondary_url;
	$can_book    = true;
}

if ( ! $display_title ) {
	return;
}

$details = array();
if ( $date_line ) { $details[] = array( 'icon' => '📅', 'label' => __( 'When',  'davenham-builder' ), 'value' => $date_line ); }
if ( $time_line ) { $details[] = array( 'icon' => '🕐', 'label' => __( 'Time',  'davenham-builder' ), 'value' => $time_line ); }
if ( $location )  { $details[] = array( 'icon' => '📍', 'label' => __( 'Where', 'davenham-builder' ), 'value' => $location ); }
if ( $age_range ) { $details[] = array( 'icon' => '👥', 'label' => __( 'Ages',  'davenham-builder' ), 'value' => $age_range ); }
?>
<section class="event_ticket cf">
	<div class="wrapper">
		<article class="event_ticket__card">
			<header class="event_ticket__header">
				<?php if ( $eyebrow ) : ?>
					<span class="event_ticket__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>
				<h2 class="event_ticket__title"><?php echo esc_html( $display_title ); ?></h2>
				<?php if ( $price_html ) : ?>
					<div class="event_ticket__price"><?php echo wp_kses_post( $price_html ); ?></div>
				<?php endif; ?>
				<?php if ( $can_book && ! $is_in_stock ) : ?>
					<span class="event_ticket__sold-out"><?php esc_html_e( 'Sold out', 'davenham-builder' ); ?></span>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $details ) ) : ?>
			<ul class="event_ticket__details">
				<?php foreach ( $details as $d ) : ?>
				<li class="event_ticket__detail">
					<span class="event_ticket__icon" aria-hidden="true"><?php echo esc_html( $d['icon'] ); ?></span>
					<div class="event_ticket__detail-text">
						<span class="event_ticket__label"><?php echo esc_html( $d['label'] ); ?></span>
						<span class="event_ticket__value"><?php echo esc_html( $d['value'] ); ?></span>
					</div>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<?php if ( $included || $bring ) : ?>
			<div class="event_ticket__lists">
				<?php if ( $included ) : ?>
				<div class="event_ticket__list">
					<h3 class="event_ticket__list-heading"><?php esc_html_e( 'What\'s included', 'davenham-builder' ); ?></h3>
					<ul>
						<?php foreach ( preg_split( '/\r?\n/', $included ) as $line ) :
							$line = trim( $line );
							if ( $line === '' ) continue;
						?>
							<li><?php echo esc_html( $line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
				<?php if ( $bring ) : ?>
				<div class="event_ticket__list">
					<h3 class="event_ticket__list-heading"><?php esc_html_e( 'What to bring', 'davenham-builder' ); ?></h3>
					<ul>
						<?php foreach ( preg_split( '/\r?\n/', $bring ) as $line ) :
							$line = trim( $line );
							if ( $line === '' ) continue;
						?>
							<li><?php echo esc_html( $line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( $can_book ) : ?>
			<footer class="event_ticket__footer">
				<a class="event_ticket__cta" href="<?php echo esc_url( $primary_url ); ?>">
					<?php echo esc_html( $is_in_stock ? $cta_text : __( 'View details', 'davenham-builder' ) ); ?> →
				</a>
				<?php if ( $secondary_url && $secondary_txt && $product_id ) : ?>
					<a class="event_ticket__secondary" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_txt ); ?></a>
				<?php endif; ?>
			</footer>
			<?php endif; ?>
		</article>
	</div>
</section>
