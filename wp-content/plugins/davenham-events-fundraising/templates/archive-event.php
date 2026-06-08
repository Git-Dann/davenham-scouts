<?php
defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main-content" tabindex="-1">

<section class="event_archive_hero">
	<div class="event_archive_hero__overlay" aria-hidden="true"></div>
	<div class="wrapper">
		<span class="event_archive_hero__eyebrow"><?php esc_html_e( 'Events & activities', 'davenham-events-fundraising' ); ?></span>
		<h1 class="event_archive_hero__title"><?php esc_html_e( 'Upcoming events', 'davenham-events-fundraising' ); ?></h1>
		<p class="event_archive_hero__desc"><?php esc_html_e( 'Camps, sleepovers, fairs and group events. Book tickets, support fundraising, and find practical details.', 'davenham-events-fundraising' ); ?></p>
	</div>
</section>

<div class="event_archive">
	<div class="wrapper">
		<?php if ( have_posts() ) : ?>
			<div class="event_archive__grid">
				<?php
				$loop_index = 0;
				while ( have_posts() ) :
					the_post();
					$loop_index++;
					$is_lead    = ( 1 === $loop_index );
					$event_id   = get_the_ID();
					$time_label = Davenham_Events_Fundraising::event_time_label( $event_id );
					$date_label = Davenham_Events_Fundraising::event_date_label( $event_id );
					$evmeta     = Davenham_Events_Fundraising::event_meta( $event_id );
				?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( $is_lead ? 'event_card event_card--lead' : 'event_card' ); ?>>
						<a href="<?php the_permalink(); ?>" class="event_card__image" aria-hidden="true" tabindex="-1">
							<?php if ( has_post_thumbnail() ) :
								the_post_thumbnail( $is_lead ? 'full' : 'large', array( 'alt' => '', 'loading' => 'lazy' ) );
							else : ?>
								<span class="event_card__placeholder" aria-hidden="true">📅</span>
							<?php endif; ?>
						</a>
						<div class="event_card__body">
							<div class="event_card__meta">
								<span class="event_card__tag"><?php echo esc_html( $date_label ); ?></span>
								<?php if ( $time_label ) : ?>
									<span class="event_card__time"><?php echo esc_html( $time_label ); ?></span>
								<?php endif; ?>
							</div>
							<h2 class="event_card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ( ! empty( $evmeta['location'] ) ) : ?>
								<span class="event_card__location">📍 <?php echo esc_html( $evmeta['location'] ); ?></span>
							<?php endif; ?>
							<?php if ( has_excerpt() ) : ?>
								<div class="event_card__excerpt"><?php echo wp_kses_post( wpautop( get_the_excerpt() ) ); ?></div>
							<?php endif; ?>
							<a href="<?php the_permalink(); ?>" class="event_card__cta"><?php esc_html_e( 'View event', 'davenham-events-fundraising' ); ?> →</a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( array(
				'class'              => 'event_archive__pagination',
				'prev_text'          => __( '&larr; Previous', 'davenham-events-fundraising' ),
				'next_text'          => __( 'Next &rarr;', 'davenham-events-fundraising' ),
				'before_page_number' => '<span class="screen-reader-text">' . __( 'Page', 'davenham-events-fundraising' ) . ' </span>',
			) ); ?>
		<?php else : ?>
			<div class="event_archive__empty">
				<p><?php esc_html_e( 'No upcoming events just now — check back soon.', 'davenham-events-fundraising' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

</main>

<?php
get_footer();
