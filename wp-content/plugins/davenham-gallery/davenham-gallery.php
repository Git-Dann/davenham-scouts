<?php
/**
 * Plugin Name: Davenham Gallery
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Safeguarding-first public gallery. Images are private by default; each one has a "Show on public gallery" setting in the Media Library. Only ticked images appear on the public gallery page.
 * Version:     1.0.0
 * Author:      Davenham Scout Group
 * Text Domain: davenham-gallery
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

final class Davenham_Gallery {

	const META_PUBLIC  = '_dvh_gallery_public';
	const META_CAPTION = '_dvh_gallery_caption';

	public static function init() {
		// Per-image settings in the Media Library (modal + edit screen).
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'attachment_fields' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'save_attachment_fields' ), 10, 2 );

		// Public gallery output.
		add_shortcode( 'davenham_public_gallery', array( __CLASS__, 'shortcode' ) );

		// Admin curation screen under Media.
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_remove' ) );
	}

	/* ── Media Library per-image settings ────────────────────────────────── */

	public static function attachment_fields( $form_fields, $post ) {
		if ( 0 !== strpos( (string) $post->post_mime_type, 'image/' ) ) {
			return $form_fields;
		}
		$public  = get_post_meta( $post->ID, self::META_PUBLIC, true );
		$caption = get_post_meta( $post->ID, self::META_CAPTION, true );

		$form_fields[ self::META_PUBLIC ] = array(
			'label' => __( 'Public gallery', 'davenham-gallery' ),
			'input' => 'html',
			'html'  => '<label style="display:flex;gap:8px;align-items:flex-start;"><input type="checkbox" name="attachments[' . $post->ID . '][' . self::META_PUBLIC . ']" value="1" ' . checked( $public, '1', false ) . ' style="margin-top:2px;" /> <span>' . esc_html__( 'Show this image on the public gallery page', 'davenham-gallery' ) . '</span></label>',
			'helps' => __( 'Safeguarding: only tick images you have consent to publish. Photos of young people must have photo consent on file. Everything is private until ticked.', 'davenham-gallery' ),
		);
		$form_fields[ self::META_CAPTION ] = array(
			'label' => __( 'Gallery caption', 'davenham-gallery' ),
			'input' => 'text',
			'value' => $caption,
			'helps' => __( 'Optional caption shown under the photo in the gallery.', 'davenham-gallery' ),
		);
		return $form_fields;
	}

	public static function save_attachment_fields( $post, $attachment ) {
		$id = (int) $post['ID'];
		if ( ! current_user_can( 'edit_post', $id ) ) {
			return $post;
		}
		update_post_meta( $id, self::META_PUBLIC, empty( $attachment[ self::META_PUBLIC ] ) ? '' : '1' );
		if ( isset( $attachment[ self::META_CAPTION ] ) ) {
			update_post_meta( $id, self::META_CAPTION, sanitize_text_field( $attachment[ self::META_CAPTION ] ) );
		}
		return $post;
	}

	/* ── Public gallery output (reuses the theme's .gallery-grid styling
	 *    + lightbox) ─────────────────────────────────────────────────────── */

	public static function public_image_ids() {
		$q = new WP_Query( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array( array( 'key' => self::META_PUBLIC, 'value' => '1' ) ),
			'no_found_rows'  => true,
		) );
		return $q->posts;
	}

	public static function shortcode( $atts ) {
		$ids = self::public_image_ids();
		if ( empty( $ids ) ) {
			return '<p class="gallery-empty" style="color:#55565A;">' . esc_html__( 'Photos are coming soon — check back shortly.', 'davenham-gallery' ) . '</p>';
		}
		$out = '<div class="gallery-grid">';
		foreach ( $ids as $id ) {
			$caption = get_post_meta( $id, self::META_CAPTION, true );
			if ( '' === $caption ) {
				$caption = get_the_title( $id );
			}
			$img = wp_get_attachment_image( $id, 'large', false, array(
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => $caption,
			) );
			if ( ! $img ) {
				continue;
			}
			$out .= '<article class="gallery-card">' . $img;
			if ( '' !== $caption ) {
				$out .= '<div class="gallery-card__body"><h3>' . esc_html( $caption ) . '</h3></div>';
			}
			$out .= '</article>';
		}
		$out .= '</div>';
		return $out;
	}

	/* ── Admin curation screen (Media → Public Gallery) ──────────────────── */

	public static function admin_menu() {
		add_submenu_page(
			'upload.php',
			__( 'Public Gallery', 'davenham-gallery' ),
			__( 'Public Gallery', 'davenham-gallery' ),
			'upload_files',
			'dvh-public-gallery',
			array( __CLASS__, 'render_admin' )
		);
	}

	public static function handle_remove() {
		if ( empty( $_GET['dvh_gal_remove'] ) ) {
			return;
		}
		$id = (int) $_GET['dvh_gal_remove'];
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'dvh_gal_remove_' . $id ) ) {
			return;
		}
		update_post_meta( $id, self::META_PUBLIC, '' );
		wp_safe_redirect( remove_query_arg( array( 'dvh_gal_remove', '_wpnonce' ) ) );
		exit;
	}

	public static function render_admin() {
		$ids = self::public_image_ids();
		?>
		<div class="wrap dvh-gal-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Public Gallery', 'davenham-gallery' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add from Media Library', 'davenham-gallery' ); ?></a>

			<div class="dvh-gal-note">
				<strong><?php esc_html_e( 'Safeguarding', 'davenham-gallery' ); ?></strong>
				<?php esc_html_e( 'Only images you deliberately tick appear here. Everything else stays private. Please only publish photos you have consent to use — photos of young people need photo consent on file.', 'davenham-gallery' ); ?>
			</div>

			<p><?php esc_html_e( 'To add a photo: open it in the Media Library and tick "Show this image on the public gallery page". It will then appear on your public gallery page and below.', 'davenham-gallery' ); ?></p>

			<?php if ( empty( $ids ) ) : ?>
				<p class="dvh-gal-empty"><?php esc_html_e( 'No photos are published to the public gallery yet.', 'davenham-gallery' ); ?></p>
			<?php else : ?>
				<p><strong><?php echo (int) count( $ids ); ?></strong> <?php esc_html_e( 'image(s) currently public.', 'davenham-gallery' ); ?></p>
				<div class="dvh-gal-grid">
					<?php foreach ( $ids as $id ) :
						$remove = wp_nonce_url( add_query_arg( 'dvh_gal_remove', $id ), 'dvh_gal_remove_' . $id );
						$cap    = get_post_meta( $id, self::META_CAPTION, true );
					?>
					<div class="dvh-gal-tile">
						<?php echo wp_get_attachment_image( $id, 'medium', false, array( 'class' => 'dvh-gal-thumb' ) ); ?>
						<div class="dvh-gal-tile__meta">
							<span class="dvh-gal-tile__cap"><?php echo esc_html( $cap ? $cap : get_the_title( $id ) ); ?></span>
							<a class="dvh-gal-remove" href="<?php echo esc_url( $remove ); ?>"><?php esc_html_e( 'Remove from gallery', 'davenham-gallery' ); ?></a>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<style>
			.dvh-gal-note{background:#FFF7ED;border:1px solid #F3D9BE;border-left:4px solid #C0490B;border-radius:10px;padding:14px 18px;margin:14px 0;max-width:820px;}
			.dvh-gal-note strong{color:#C0490B;display:block;margin-bottom:2px;}
			.dvh-gal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-top:14px;}
			.dvh-gal-tile{background:#fff;border:1px solid #e7e9ee;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(15,23,42,.06);display:flex;flex-direction:column;}
			.dvh-gal-thumb{width:100%;height:150px;object-fit:cover;display:block;}
			.dvh-gal-tile__meta{padding:12px 14px;display:flex;flex-direction:column;gap:6px;}
			.dvh-gal-tile__cap{font-weight:700;color:#003982;font-size:.95rem;}
			.dvh-gal-remove{color:#C0490B;font-weight:600;font-size:.85rem;text-decoration:none;}
			.dvh-gal-remove:hover{text-decoration:underline;}
		</style>
		<?php
	}
}

Davenham_Gallery::init();
