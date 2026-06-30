<?php
/**
 * Parent dashboard ([davenham_parent_dashboard]).
 *
 * @var bool    $can       Whether the current user is an approved parent.
 * @var string  $login_url Login URL returning here.
 * @var array   $children  Array of [name, dob, section].
 * @var array   $events    WP_Post[] of upcoming events.
 * @var WP_User $user      Current user.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="dpp-dashboard">

	<?php if ( ! $can ) : ?>

		<div class="dpp-gate">
			<h2><?php esc_html_e( 'Parent login', 'davenham-parent-portal' ); ?></h2>
			<p><?php esc_html_e( 'This area is for registered Davenham Scout Group parents. Please log in to see your dashboard.', 'davenham-parent-portal' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Log in', 'davenham-parent-portal' ); ?></a></p>
		</div>

	<?php else : ?>

		<header class="dpp-dash-header">
			<h2><?php echo esc_html( sprintf( /* translators: %s: parent name */ __( 'Welcome, %s', 'davenham-parent-portal' ), $user->display_name ) ); ?></h2>
		</header>

		<section class="dpp-card">
			<h3><?php esc_html_e( 'Your children', 'davenham-parent-portal' ); ?></h3>
			<?php if ( ! empty( $children ) ) : ?>
				<ul class="dpp-kids">
					<?php foreach ( $children as $kid ) : ?>
						<?php $label = Davenham_Parent_Portal::section_label_public( isset( $kid['section'] ) ? $kid['section'] : '' ); ?>
						<li>
							<span class="dpp-kid-name"><?php echo esc_html( isset( $kid['name'] ) ? $kid['name'] : '' ); ?></span>
							<?php if ( $label ) : ?>
								<span class="dpp-kid-section"><?php echo esc_html( $label ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="dpp-muted"><?php esc_html_e( 'No children are recorded yet. Please contact a leader if this looks wrong.', 'davenham-parent-portal' ); ?></p>
			<?php endif; ?>
		</section>

		<section class="dpp-card">
			<h3><?php esc_html_e( 'Upcoming events', 'davenham-parent-portal' ); ?></h3>
			<?php if ( ! empty( $events ) ) : ?>
				<ul class="dpp-events">
					<?php foreach ( $events as $event ) : ?>
						<?php
						$date     = get_post_meta( $event->ID, 'event_date', true );
						$location = get_post_meta( $event->ID, 'event_location', true );
						$nice     = $date ? date_i18n( get_option( 'date_format' ), strtotime( $date ) ) : '';
						?>
						<li class="dpp-event">
							<a href="<?php echo esc_url( get_permalink( $event ) ); ?>"><?php echo esc_html( get_the_title( $event ) ); ?></a>
							<span class="dpp-event-meta">
								<?php
								$bits = array();
								if ( $nice ) {
									$bits[] = $nice;
								}
								if ( $location ) {
									$bits[] = $location;
								}
								echo esc_html( implode( ' · ', $bits ) );
								?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="dpp-muted"><?php esc_html_e( 'No upcoming events right now — check back soon.', 'davenham-parent-portal' ); ?></p>
			<?php endif; ?>
		</section>

		<p class="dpp-logout"><a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log out', 'davenham-parent-portal' ); ?></a></p>

	<?php endif; ?>

</div>
