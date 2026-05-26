<?php
/**
 * Render: davenham/contact-info
 * Address, phone, email, opening hours + optional map embed.
 *
 * @var array $attributes Block attributes.
 */
$heading       = $attributes['heading']      ?? 'Get in Touch';
$address       = $attributes['address']      ?? '';
$phone         = $attributes['phone']        ?? '';
$email         = $attributes['email']        ?? '';
$opening_hours = $attributes['openingHours'] ?? '';
$map_url       = $attributes['mapEmbedUrl']  ?? '';
$btn_text      = $attributes['buttonText']   ?? 'Send us a message';
$btn_url       = $attributes['buttonUrl']    ?? '/contact';
$has_map       = ! empty( $map_url );
?>
<section class="contact_section cf">
	<div class="wrapper">
		<div class="title_bar blue">
			<h3><?php echo esc_html( $heading ); ?></h3>
		</div><!-- .title_bar -->

		<div class="contact_inner<?php echo $has_map ? ' contact_inner--has-map' : ''; ?>">
			<div class="contact_details">
				<?php if ( $address ) : ?>
				<div class="contact_row">
					<span class="contact_icon">📍</span>
					<div>
						<strong>Address</strong>
						<address><?php echo nl2br( esc_html( $address ) ); ?></address>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $phone ) : ?>
				<div class="contact_row">
					<span class="contact_icon">📞</span>
					<div>
						<strong>Phone</strong>
						<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $email ) : ?>
				<div class="contact_row">
					<span class="contact_icon">✉️</span>
					<div>
						<strong>Email</strong>
						<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $opening_hours ) : ?>
				<div class="contact_row">
					<span class="contact_icon">🕐</span>
					<div>
						<strong>Meetings</strong>
						<p><?php echo esc_html( $opening_hours ); ?></p>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $btn_text && $btn_url ) : ?>
				<div class="contact_cta" style="margin-top:24px;">
					<a href="<?php echo esc_url( $btn_url ); ?>" class="btn outline"><?php echo esc_html( $btn_text ); ?></a>
				</div>
				<?php endif; ?>
			</div><!-- .contact_details -->

			<?php if ( $has_map ) : ?>
			<div class="contact_map">
				<iframe
					src="<?php echo esc_url( $map_url ); ?>"
					width="100%"
					height="380"
					style="border:0;border-radius:8px;"
					allowfullscreen=""
					loading="lazy"
					referrerpolicy="no-referrer-when-downgrade"
					title="Map"
				></iframe>
			</div><!-- .contact_map -->
			<?php endif; ?>
		</div><!-- .contact_inner -->
	</div><!-- .wrapper -->
</section><!-- .contact_section -->
