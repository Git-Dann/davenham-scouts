<?php
/**
 * Frontend document library, rendered by the [davenham_documents] shortcode.
 *
 * @var bool   $can_view  Whether the current visitor may see documents.
 * @var string $login_url Login URL that returns here afterwards.
 * @var array  $grouped   Array of [ 'term' => WP_Term|null, 'docs' => WP_Post[] ].
 */

defined( 'ABSPATH' ) || exit;

// Count unique documents across categories (a doc may sit in more than one).
$doc_ids = array();
if ( $can_view && ! empty( $grouped ) ) {
	foreach ( $grouped as $g ) {
		foreach ( $g['docs'] as $d ) {
			$doc_ids[ $d->ID ] = true;
		}
	}
}
$total = count( $doc_ids );
?>
<div class="davenham-docs" data-davenham-docs>

	<?php if ( ! $can_view ) : ?>

		<div class="davenham-docs__gate">
			<h2 class="davenham-docs__gate-title"><?php esc_html_e( 'Members area', 'davenham-documents' ); ?></h2>
			<p class="davenham-docs__gate-text">
				<?php esc_html_e( 'These documents are shared privately with Davenham Scout Group trustees and section leaders. Please log in to view them.', 'davenham-documents' ); ?>
			</p>
			<p>
				<a class="button davenham-docs__login" href="<?php echo esc_url( $login_url ); ?>">
					<?php esc_html_e( 'Log in to view documents', 'davenham-documents' ); ?>
				</a>
			</p>
		</div>

	<?php elseif ( empty( $grouped ) ) : ?>

		<p class="davenham-docs__empty"><?php esc_html_e( 'No documents have been shared yet.', 'davenham-documents' ); ?></p>

	<?php else : ?>

		<div class="davenham-docs__toolbar">
			<span class="davenham-docs__count">
				<?php
				/* translators: %s: number of documents. */
				echo esc_html( sprintf( _n( '%s document', '%s documents', $total, 'davenham-documents' ), number_format_i18n( $total ) ) );
				?>
			</span>
			<label class="davenham-docs__search" for="davenham-docs-search">
				<span class="screen-reader-text"><?php esc_html_e( 'Search documents', 'davenham-documents' ); ?></span>
				<input type="search" id="davenham-docs-search" data-davenham-search placeholder="<?php esc_attr_e( 'Search documents…', 'davenham-documents' ); ?>" autocomplete="off" />
			</label>
		</div>

		<p class="davenham-docs__noresults" data-davenham-noresults hidden>
			<?php esc_html_e( 'No documents match your search.', 'davenham-documents' ); ?>
		</p>

		<?php foreach ( $grouped as $group ) : ?>
			<?php
			$term = isset( $group['term'] ) ? $group['term'] : null;
			$docs = isset( $group['docs'] ) ? $group['docs'] : array();
			if ( empty( $docs ) ) {
				continue;
			}
			$heading = $term ? $term->name : __( 'Other documents', 'davenham-documents' );
			?>
			<section class="davenham-docs__group" data-davenham-group>
				<h3 class="davenham-docs__group-title"><?php echo esc_html( $heading ); ?></h3>
				<ul class="davenham-docs__list">
					<?php foreach ( $docs as $doc ) : ?>
						<?php
						$title     = get_the_title( $doc );
						$file_name = get_post_meta( $doc->ID, '_davenham_doc_name', true );
						$size      = (int) get_post_meta( $doc->ID, '_davenham_doc_size', true );
						$ext       = $file_name ? strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) ) : '';
						$url       = Davenham_Documents::download_url( $doc->ID );
						$updated   = get_the_modified_date( get_option( 'date_format' ), $doc );
						$search    = strtolower( $title . ' ' . $file_name . ' ' . ( $term ? $term->name : '' ) );

						$meta_bits = array();
						if ( $size ) {
							$meta_bits[] = size_format( $size );
						}
						if ( $updated ) {
							/* translators: %s: date the document was last updated. */
							$meta_bits[] = sprintf( __( 'Updated %s', 'davenham-documents' ), $updated );
						}
						?>
						<li class="davenham-docs__item" data-davenham-item data-search="<?php echo esc_attr( $search ); ?>">
							<a class="davenham-docs__link" href="<?php echo esc_url( $url ); ?>" rel="nofollow"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: document title. */ __( 'Download %s', 'davenham-documents' ), $title ) ); ?>">
								<?php if ( $ext ) : ?>
									<span class="davenham-docs__badge davenham-docs__badge--<?php echo esc_attr( $ext ); ?>" aria-hidden="true"><?php echo esc_html( strtoupper( $ext ) ); ?></span>
								<?php endif; ?>
								<span class="davenham-docs__body">
									<span class="davenham-docs__name"><?php echo esc_html( $title ); ?></span>
									<?php if ( ! empty( $meta_bits ) ) : ?>
										<span class="davenham-docs__meta"><?php echo esc_html( implode( ' · ', $meta_bits ) ); ?></span>
									<?php endif; ?>
								</span>
								<span class="davenham-docs__action" aria-hidden="true"><?php esc_html_e( 'Download', 'davenham-documents' ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>

	<?php endif; ?>

</div>
