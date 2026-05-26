<?php
/**
 * Render: davenham/sponsors
 * Logo grid for sponsors, partners and supporters.
 *
 * @var array $attributes Block attributes.
 */
$heading = $attributes['heading'] ?? 'Our Supporters';
$logos   = is_array( $attributes['logos'] ?? null ) ? $attributes['logos'] : [];
?>
<section class="sponsors_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?>
		<div class="title_bar blue">
			<h3><?php echo esc_html( $heading ); ?></h3>
		</div><!-- .title_bar -->
		<?php endif; ?>

		<?php if ( ! empty( $logos ) ) : ?>
		<div class="sponsors_grid">
			<?php foreach ( $logos as $logo ) :
				$img  = isset( $logo['imageUrl'] ) ? esc_url( $logo['imageUrl'] ) : '';
				$name = isset( $logo['name']     ) ? esc_attr( $logo['name'] )    : '';
				$url  = isset( $logo['url']      ) ? esc_url( $logo['url'] )      : '';
				if ( ! $img ) continue;
			?>
			<div class="sponsor_item">
				<?php if ( $url ) : ?>
				<a href="<?php echo $url; ?>" target="_blank" rel="noreferrer noopener" title="<?php echo $name; ?>">
					<img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" loading="lazy" />
				</a>
				<?php else : ?>
				<img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" loading="lazy" />
				<?php endif; ?>
			</div><!-- .sponsor_item -->
			<?php endforeach; ?>
		</div><!-- .sponsors_grid -->
		<?php else : ?>
			<p style="padding:20px 0;color:#aaa;">No sponsors added yet.</p>
		<?php endif; ?>
	</div><!-- .wrapper -->
</section><!-- .sponsors_section -->
