<?php
/**
 * Public Charity Documents, rendered by [davenham_public_documents].
 *
 * @var array  $groups       Array of [ 'term' => WP_Term, 'docs' => WP_Post[] ].
 * @var string $request_url  Optional CTA link (e.g. contact form).
 * @var string $request_text CTA button label.
 * @var string $request_note Small line under the CTA.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="dpub-docs">

	<?php if ( empty( $groups ) ) : ?>

		<div class="dpub-docs__empty">
			<strong><?php esc_html_e( 'Nothing published here yet', 'davenham-documents' ); ?></strong>
			<p><?php esc_html_e( 'Documents will appear here once they are published. Please check back soon.', 'davenham-documents' ); ?></p>
		</div>

	<?php else : ?>

		<?php foreach ( $groups as $group ) : $term = $group['term']; ?>
			<section class="dpub-docs__section">
				<h2 class="dpub-docs__section-title"><?php echo esc_html( $term->name ); ?></h2>
				<?php if ( ! empty( $term->description ) ) : ?>
					<p class="dpub-docs__section-desc"><?php echo esc_html( $term->description ); ?></p>
				<?php endif; ?>

				<ul class="dpub-docs__grid">
					<?php
					foreach ( $group['docs'] as $doc ) :
						$badge    = Davenham_Documents::file_type_badge( $doc->ID );
						$size     = (int) get_post_meta( $doc->ID, '_davenham_doc_size', true );
						$view_url = Davenham_Documents::public_file_url( $doc->ID, true );
						$dl_url   = Davenham_Documents::public_file_url( $doc->ID, false );
					?>
					<li class="dpub-card">
						<div class="dpub-card__top">
							<span class="dpub-card__badge" style="background: <?php echo esc_attr( $badge[1] ); ?>;"><?php echo esc_html( $badge[0] ); ?></span>
							<?php if ( $size ) : ?>
								<span class="dpub-card__size"><?php echo esc_html( size_format( $size ) ); ?></span>
							<?php endif; ?>
						</div>
						<h3 class="dpub-card__title"><?php echo esc_html( get_the_title( $doc ) ); ?></h3>
						<div class="dpub-card__actions">
							<a class="dpub-card__btn dpub-card__btn--view" href="<?php echo esc_url( $view_url ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'View', 'davenham-documents' ); ?>
							</a>
							<a class="dpub-card__btn dpub-card__btn--download" href="<?php echo esc_url( $dl_url ); ?>">
								<?php esc_html_e( 'Download', 'davenham-documents' ); ?>
							</a>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>

	<?php endif; ?>

	<?php if ( $request_url ) : ?>
		<div class="dpub-docs__request">
			<div class="dpub-docs__request-body">
				<h3><?php esc_html_e( 'Need a specific risk assessment?', 'davenham-documents' ); ?></h3>
				<p><?php echo $request_note ? esc_html( $request_note ) : esc_html__( 'The documents above are our standard, ongoing risk assessments. For a session- or activity-specific one, please get in touch and we will prepare it for you.', 'davenham-documents' ); ?></p>
			</div>
			<a class="dpub-docs__request-btn" href="<?php echo esc_url( $request_url ); ?>"><?php echo esc_html( $request_text ); ?></a>
		</div>
	<?php endif; ?>

</div>
