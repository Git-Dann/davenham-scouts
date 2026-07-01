<?php
/**
 * Render: davenham/leaders
 * Photo cards for leaders, helpers and trustees.
 *
 * @var array $attributes Block attributes.
 */
$heading = $attributes['heading'] ?? 'Our Leaders';
$leaders = is_array( $attributes['leaders'] ?? null ) ? $attributes['leaders'] : [];

// Cards get a wider layout when any entry carries a bio, so the copy has
// room to breathe (a photo roster with no bios stays compact).
$has_bio = false;
foreach ( $leaders as $l ) {
	if ( ! empty( $l['bio'] ) ) { $has_bio = true; break; }
}
?>
<section class="leaders_section cf">
	<div class="wrapper">
		<div class="title_bar blue">
			<h3><?php echo esc_html( $heading ); ?></h3>
		</div><!-- .title_bar -->

		<?php if ( ! empty( $leaders ) ) : ?>
		<div class="leaders_grid<?php echo $has_bio ? ' leaders_grid--bio' : ''; ?>">
			<?php foreach ( $leaders as $leader ) :
				$img     = isset( $leader['imageUrl'] ) ? esc_url( $leader['imageUrl'] ) : '';
				$name    = isset( $leader['name']     ) ? esc_html( $leader['name'] )    : '';
				$role    = isset( $leader['role']     ) ? esc_html( $leader['role'] )    : '';
				$section = isset( $leader['section']  ) ? esc_html( $leader['section'] ) : '';
				$bio     = isset( $leader['bio']      ) ? esc_html( $leader['bio'] )     : '';
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
					<?php if ( $bio ) : ?>
					<p class="leader_card__bio"><?php echo $bio; ?></p>
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
