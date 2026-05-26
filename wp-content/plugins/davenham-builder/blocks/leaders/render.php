<?php
/**
 * Render: davenham/leaders
 * Photo cards for leaders, helpers and trustees.
 *
 * @var array $attributes Block attributes.
 */
$heading = $attributes['heading'] ?? 'Our Leaders';
$leaders = is_array( $attributes['leaders'] ?? null ) ? $attributes['leaders'] : [];
?>
<section class="leaders_section cf">
	<div class="wrapper">
		<div class="title_bar blue">
			<h3><?php echo esc_html( $heading ); ?></h3>
		</div><!-- .title_bar -->

		<?php if ( ! empty( $leaders ) ) : ?>
		<div class="leaders_grid">
			<?php foreach ( $leaders as $leader ) :
				$img     = isset( $leader['imageUrl'] ) ? esc_url( $leader['imageUrl'] ) : '';
				$name    = isset( $leader['name']     ) ? esc_html( $leader['name'] )    : '';
				$role    = isset( $leader['role']     ) ? esc_html( $leader['role'] )    : '';
				$section = isset( $leader['section']  ) ? esc_html( $leader['section'] ) : '';
				if ( ! $name ) continue;
			?>
			<div class="leader_card">
				<?php if ( $img ) : ?>
				<div class="leader_card__photo">
					<img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" loading="lazy" />
				</div>
				<?php else : ?>
				<div class="leader_card__photo leader_card__photo--placeholder">
					<span>👤</span>
				</div>
				<?php endif; ?>
				<div class="leader_card__info">
					<h4 class="leader_card__name"><?php echo $name; ?></h4>
					<?php if ( $role ) : ?>
					<p class="leader_card__role"><?php echo $role; ?></p>
					<?php endif; ?>
					<?php if ( $section ) : ?>
					<span class="leader_card__section"><?php echo $section; ?></span>
					<?php endif; ?>
				</div>
			</div><!-- .leader_card -->
			<?php endforeach; ?>
		</div><!-- .leaders_grid -->
		<?php else : ?>
			<p style="padding:20px 0;color:#aaa;">No leaders added yet.</p>
		<?php endif; ?>
	</div><!-- .wrapper -->
</section><!-- .leaders_section -->
