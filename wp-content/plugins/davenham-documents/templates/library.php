<?php
/**
 * Frontend document library, rendered by the [davenham_documents] shortcode.
 *
 * @var bool   $can_view  Whether the current visitor may see documents.
 * @var string $login_url Login URL that returns here afterwards.
 * @var array  $grouped   Array of [ 'term' => WP_Term|null, 'docs' => WP_Post[] ].
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="davenham-docs">

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

		<?php foreach ( $grouped as $group ) : ?>
			<?php
			$term = isset( $group['term'] ) ? $group['term'] : null;
			$docs = isset( $group['docs'] ) ? $group['docs'] : array();
			if ( empty( $docs ) ) {
				continue;
			}
			$heading = $term ? $term->name : __( 'Other documents', 'davenham-documents' );
			?>
			<section class="davenham-docs__group">
				<h3 class="davenham-docs__group-title"><?php echo esc_html( $heading ); ?></h3>
				<ul class="davenham-docs__list">
					<?php foreach ( $docs as $doc ) : ?>
						<?php
						$file_name = get_post_meta( $doc->ID, '_davenham_doc_name', true );
						$size      = (int) get_post_meta( $doc->ID, '_davenham_doc_size', true );
						$ext       = $file_name ? strtoupper( pathinfo( $file_name, PATHINFO_EXTENSION ) ) : '';
						$url       = Davenham_Documents::download_url( $doc->ID );
						?>
						<li class="davenham-docs__item">
							<a class="davenham-docs__link" href="<?php echo esc_url( $url ); ?>">
								<span class="davenham-docs__name"><?php echo esc_html( get_the_title( $doc ) ); ?></span>
								<span class="davenham-docs__meta">
									<?php
									$bits = array();
									if ( $ext ) {
										$bits[] = $ext;
									}
									if ( $size ) {
										$bits[] = size_format( $size );
									}
									echo esc_html( implode( ' · ', $bits ) );
									?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>

	<?php endif; ?>

</div>
