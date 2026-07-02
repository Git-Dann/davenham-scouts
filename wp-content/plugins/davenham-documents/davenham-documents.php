<?php
/**
 * Plugin Name: Davenham Documents
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Gated document library — leaders and trustees log in to share and download key documents (risk assessments, policies, minutes). Files are stored outside the public media library and served only to authorised users.
 * Version:     1.0.4
 * Author:      Davenham Scout Group
 * Text Domain: davenham-documents
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'DDOC_VERSION', '1.0.4' );
define( 'DDOC_FILE', __FILE__ );
define( 'DDOC_DIR', plugin_dir_path( __FILE__ ) );
define( 'DDOC_URL', plugin_dir_url( __FILE__ ) );

final class Davenham_Documents {

	const POST_TYPE     = 'davenham_document';
	const TAXONOMY      = 'davenham_doc_category';
	const STORAGE_DIR   = 'davenham-documents'; // relative to uploads basedir
	const READ_CAP      = 'read_davenham_documents';
	const SAVE_NONCE    = 'davenham_doc_save';
	const DOWNLOAD_NONCE = 'davenham_doc_download';
	const VERSION_OPTION = 'davenham_documents_version';
	const PUBLIC_META    = 'davenham_doc_public'; // term meta: '1' = public category

	/**
	 * Roles that can use the library (view + manage), beyond administrator.
	 */
	private static function managed_roles() {
		return array(
			'davenham_trustee' => 'Trustee',
			'davenham_leader'  => 'Section Leader',
		);
	}

	/**
	 * Default document categories seeded on activation.
	 * name => is_public (public categories appear on the public Charity
	 * Documents page; private ones stay trustee/leader only).
	 */
	private static function default_categories() {
		return array(
			'Annual Reports'   => true,
			'Accounts'         => true,
			'Policies'         => true,
			'Risk Assessments' => true,
			'Governance'       => true,
			'Minutes'          => false,
			'Forms'            => false,
			'Other'            => false,
		);
	}

	/**
	 * Allowed upload types: extension => mime.
	 */
	private static function allowed_types() {
		return array(
			'pdf'      => 'application/pdf',
			'doc'      => 'application/msword',
			'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'      => 'application/vnd.ms-excel',
			'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'ppt'      => 'application/vnd.ms-powerpoint',
			'pptx'     => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'png'      => 'image/png',
			'jpg|jpeg' => 'image/jpeg',
		);
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcode' ) );
		add_action( 'init', array( __CLASS__, 'ensure_default_categories' ), 20 );

		// Self-heal: if files were FTP-deployed without a clean (re)activation,
		// make sure roles, storage dir and categories exist.
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );

		// Category "public" flag — categories ticked as public are shown on
		// the public Charity Documents page and are downloadable by anyone.
		add_action( self::TAXONOMY . '_add_form_fields', array( __CLASS__, 'category_add_field' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'category_edit_field' ) );
		add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'save_category_public' ) );
		add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'save_category_public' ) );

		// Admin: upload meta box + multipart form + save handler.
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'post_edit_form_tag', array( __CLASS__, 'add_form_enctype' ) );
		add_action( 'edit_form_top', array( __CLASS__, 'render_uploader_intro' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_document' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );

		// Frontend assets.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_public_assets' ) );

		// Gated download handler (admin-ajax — no custom REST routes on Krystal).
		// Both logged-in and logged-out go through one handler; it serves
		// public-category documents to anyone and gates the rest to trustees.
		add_action( 'wp_ajax_davenham_download_document', array( __CLASS__, 'handle_download' ) );
		add_action( 'wp_ajax_nopriv_davenham_download_document', array( __CLASS__, 'handle_download' ) );
	}

	/* ---------------------------------------------------------------------
	 * Activation / setup
	 * ------------------------------------------------------------------- */

	public static function activate() {
		self::register_post_type();
		self::register_taxonomy();
		self::create_roles();
		self::ensure_storage_dir();
		self::ensure_default_categories();
		update_option( self::VERSION_OPTION, DDOC_VERSION, false );
		flush_rewrite_rules();
	}

	public static function deactivate() {
		// Leave roles and stored files in place so nothing is lost on a toggle.
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( get_option( self::VERSION_OPTION ) === DDOC_VERSION ) {
			return;
		}
		self::create_roles();
		self::ensure_storage_dir();
		self::ensure_default_categories();
		update_option( self::VERSION_OPTION, DDOC_VERSION, false );
	}

	/**
	 * Full primitive capability set for the document CPT.
	 */
	private static function document_caps() {
		return array(
			'read'                                => true,
			self::READ_CAP                        => true,
			'edit_davenham_documents'             => true,
			'edit_others_davenham_documents'      => true,
			'edit_published_davenham_documents'   => true,
			'edit_private_davenham_documents'     => true,
			'publish_davenham_documents'          => true,
			'read_private_davenham_documents'     => true,
			'delete_davenham_documents'           => true,
			'delete_others_davenham_documents'    => true,
			'delete_published_davenham_documents' => true,
			'delete_private_davenham_documents'   => true,
		);
	}

	public static function create_roles() {
		$caps = self::document_caps();

		foreach ( self::managed_roles() as $slug => $label ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				add_role( $slug, $label, $caps );
			} else {
				foreach ( $caps as $cap => $grant ) {
					$role->add_cap( $cap );
				}
			}
		}

		// Administrators get the full set too (so they manage everything).
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( $caps as $cap => $grant ) {
				$admin->add_cap( $cap );
			}
		}
	}

	public static function ensure_storage_dir() {
		$dir = self::storage_path();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Block direct URL access (Apache 2.2 + 2.4 syntaxes).
		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$rules  = "# Davenham Documents — deny all direct access. Files are served via PHP only.\n";
			$rules .= "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n";
			$rules .= "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n";
			@file_put_contents( $htaccess, $rules );
		}

		// Silence directory listing.
		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, "<?php // Silence is golden.\n" );
		}
	}

	public static function ensure_default_categories() {
		if ( ! taxonomy_exists( self::TAXONOMY ) ) {
			return;
		}
		foreach ( self::default_categories() as $name => $is_public ) {
			$existing = term_exists( $name, self::TAXONOMY );
			if ( ! $existing ) {
				$created = wp_insert_term( $name, self::TAXONOMY );
				if ( ! is_wp_error( $created ) && isset( $created['term_id'] ) ) {
					update_term_meta( (int) $created['term_id'], self::PUBLIC_META, $is_public ? '1' : '' );
				}
				continue;
			}
			// Existing category: only seed the public flag if it has never
			// been set, so we never override a choice an admin has made.
			$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
			if ( ! metadata_exists( 'term', $term_id, self::PUBLIC_META ) ) {
				update_term_meta( $term_id, self::PUBLIC_META, $is_public ? '1' : '' );
			}
		}
	}

	/* ---------------------------------------------------------------------
	 * Registration
	 * ------------------------------------------------------------------- */

	public static function register_post_type() {
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => __( 'Documents', 'davenham-documents' ),
					'singular_name'      => __( 'Document', 'davenham-documents' ),
					'add_new_item'       => __( 'Add New Document', 'davenham-documents' ),
					'edit_item'          => __( 'Edit Document', 'davenham-documents' ),
					'new_item'           => __( 'New Document', 'davenham-documents' ),
					'view_item'          => __( 'View Document', 'davenham-documents' ),
					'search_items'       => __( 'Search Documents', 'davenham-documents' ),
					'not_found'          => __( 'No documents found.', 'davenham-documents' ),
					'not_found_in_trash' => __( 'No documents in Trash.', 'davenham-documents' ),
					'menu_name'          => __( 'Documents', 'davenham-documents' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => false, // classic editor
				'menu_icon'       => 'dashicons-media-document',
				'menu_position'   => 25,
				'hierarchical'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => array( 'davenham_document', 'davenham_documents' ),
				'map_meta_cap'    => true,
			)
		);
	}

	public static function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Document Categories', 'davenham-documents' ),
					'singular_name' => __( 'Category', 'davenham-documents' ),
					'menu_name'     => __( 'Categories', 'davenham-documents' ),
					'add_new_item'  => __( 'Add New Category', 'davenham-documents' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => false,
				'hierarchical'      => true,
				'rewrite'           => false,
				'capabilities'      => array(
					'manage_terms' => 'manage_options',
					'edit_terms'   => 'manage_options',
					'delete_terms' => 'manage_options',
					'assign_terms' => 'edit_davenham_documents',
				),
			)
		);
	}

	public static function register_shortcode() {
		add_shortcode( 'davenham_documents', array( __CLASS__, 'render_library_shortcode' ) );
		add_shortcode( 'davenham_public_documents', array( __CLASS__, 'render_public_shortcode' ) );
	}

	/* ---------------------------------------------------------------------
	 * Category "public" flag (term meta) + helpers
	 * ------------------------------------------------------------------- */

	public static function is_public_category( $term_id ) {
		return '1' === get_term_meta( (int) $term_id, self::PUBLIC_META, true );
	}

	public static function document_is_public( $doc_id ) {
		$terms = get_the_terms( (int) $doc_id, self::TAXONOMY );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( self::is_public_category( $term->term_id ) ) {
					return true;
				}
			}
		}
		return false;
	}

	public static function category_add_field() {
		?>
		<div class="form-field">
			<input type="hidden" name="davenham_doc_public_present" value="1" />
			<label style="font-weight:600;"><input type="checkbox" name="davenham_doc_public" value="1" /> <?php esc_html_e( 'Show on the public Charity Documents page', 'davenham-documents' ); ?></label>
			<p><?php esc_html_e( 'Tick for categories the public should see (Accounts, Annual Reports, Risk Assessments, Policies…). Leave unticked for internal-only categories (e.g. Minutes) — those stay downloadable by trustees and leaders only.', 'davenham-documents' ); ?></p>
		</div>
		<?php
	}

	public static function category_edit_field( $term ) {
		$checked = self::is_public_category( $term->term_id ) ? ' checked' : '';
		?>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Visibility', 'davenham-documents' ); ?></th>
			<td>
				<input type="hidden" name="davenham_doc_public_present" value="1" />
				<label style="font-weight:600;"><input type="checkbox" name="davenham_doc_public" value="1"<?php echo $checked; // phpcs:ignore ?> /> <?php esc_html_e( 'Show on the public Charity Documents page', 'davenham-documents' ); ?></label>
				<p class="description"><?php esc_html_e( 'Public categories (Accounts, Annual Reports, Risk Assessments…) appear on the public page and are downloadable by anyone. Unticked categories stay trustee/leader only.', 'davenham-documents' ); ?></p>
			</td>
		</tr>
		<?php
	}

	public static function save_category_public( $term_id ) {
		// Only act on our own term-edit form (the hidden field distinguishes it
		// from quick-edit / programmatic term saves, which would otherwise wipe
		// the flag because an unticked checkbox posts nothing).
		if ( ! isset( $_POST['davenham_doc_public_present'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_term_meta( (int) $term_id, self::PUBLIC_META, ! empty( $_POST['davenham_doc_public'] ) ? '1' : '' );
	}

	/* ---------------------------------------------------------------------
	 * Storage helpers
	 * ------------------------------------------------------------------- */

	private static function uploads_basedir() {
		$uploads = wp_upload_dir();
		return untrailingslashit( $uploads['basedir'] );
	}

	private static function storage_path() {
		return self::uploads_basedir() . '/' . self::STORAGE_DIR;
	}

	/* ---------------------------------------------------------------------
	 * Admin: upload meta box + save
	 * ------------------------------------------------------------------- */

	public static function register_meta_boxes() {
		add_meta_box(
			'davenham_doc_file',
			__( 'Document file', 'davenham-documents' ),
			array( __CLASS__, 'render_file_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function add_form_enctype() {
		$screen = get_current_screen();
		if ( $screen && self::POST_TYPE === $screen->post_type ) {
			echo ' enctype="multipart/form-data"';
		}
	}

	public static function render_uploader_intro( $post ) {
		if ( ! isset( $post->post_type ) || self::POST_TYPE !== $post->post_type ) {
			return;
		}
		echo '<div class="davenham-doc-intro">';
		echo '<strong>' . esc_html__( 'Adding a document is quick:', 'davenham-documents' ) . '</strong> ';
		echo esc_html__( '1. Give it a clear name above.  2. Choose the file below.  3. Tick a category on the right.  4. Click Publish.', 'davenham-documents' );
		echo '</div>';
	}

	public static function render_file_meta_box( $post ) {
		wp_nonce_field( self::SAVE_NONCE, 'davenham_doc_nonce' );

		$name = get_post_meta( $post->ID, '_davenham_doc_name', true );
		$size = (int) get_post_meta( $post->ID, '_davenham_doc_size', true );

		echo '<div class="davenham-doc-file-box">';

		if ( $name ) {
			echo '<p class="davenham-doc-current"><span class="dashicons dashicons-media-document" aria-hidden="true"></span> <strong>' . esc_html__( 'Current file:', 'davenham-documents' ) . '</strong> ';
			echo esc_html( $name );
			if ( $size ) {
				echo ' <span class="davenham-doc-size">(' . esc_html( size_format( $size ) ) . ')</span>';
			}
			echo '</p>';
		}

		echo '<div class="davenham-doc-upload">';
		echo '<label class="davenham-doc-upload__label" for="davenham_doc_file_input">' . ( $name ? esc_html__( 'Replace the file', 'davenham-documents' ) : esc_html__( 'Choose the file to upload', 'davenham-documents' ) ) . '</label>';
		echo '<input type="file" id="davenham_doc_file_input" name="davenham_doc_file" class="davenham-doc-upload__input" />';
		echo '<p class="davenham-doc-upload__hint">' . esc_html__( 'PDF, Word, Excel, PowerPoint, or an image (PNG / JPG).', 'davenham-documents' ) . '</p>';
		echo '</div>';

		echo '<p class="davenham-doc-visnote">' . wp_kses_post( __( '<strong>Who can see this?</strong> It depends on the category you tick on the right. Categories marked <em>public</em> (e.g. Accounts, Annual Reports, Risk Assessments) appear on the website for anyone to view or download. Every other category stays private — only logged-in trustees and leaders can open it.', 'davenham-documents' ) ) . '</p>';

		echo '</div>';
	}

	public static function save_document( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( self::POST_TYPE !== $post->post_type ) {
			return;
		}
		if ( ! isset( $_POST['davenham_doc_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['davenham_doc_nonce'] ) ), self::SAVE_NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_davenham_document', $post_id ) ) {
			return;
		}

		// No new file uploaded — keep the existing one.
		if ( empty( $_FILES['davenham_doc_file'] ) || empty( $_FILES['davenham_doc_file']['name'] ) ) {
			return;
		}

		$file = $_FILES['davenham_doc_file'];

		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			self::flag_error( $post_id, __( 'The file could not be uploaded. Please try again.', 'davenham-documents' ) );
			return;
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			self::flag_error( $post_id, __( 'Upload failed a security check.', 'davenham-documents' ) );
			return;
		}

		$original = sanitize_file_name( wp_unslash( $file['name'] ) );
		$check    = wp_check_filetype_and_ext( $file['tmp_name'], $original, self::allowed_types() );

		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			self::flag_error( $post_id, __( 'That file type is not allowed. Use PDF, Word, Excel, PowerPoint, PNG or JPG.', 'davenham-documents' ) );
			return;
		}

		self::ensure_storage_dir();

		$ext         = $check['ext'];
		$stored_name = wp_generate_password( 24, false, false ) . '.' . $ext;
		$target      = self::storage_path() . '/' . $stored_name;

		if ( ! move_uploaded_file( $file['tmp_name'], $target ) ) {
			self::flag_error( $post_id, __( 'The file could not be saved on the server.', 'davenham-documents' ) );
			return;
		}

		// chmod to typical upload perms.
		$perms = ( fileperms( self::storage_path() ) & 0666 );
		if ( $perms ) {
			@chmod( $target, $perms );
		}

		// Remove the previous file if this is a replacement.
		$old = get_post_meta( $post_id, '_davenham_doc_file', true );
		if ( $old ) {
			$old_path = self::resolve_path( $old );
			if ( $old_path && file_exists( $old_path ) ) {
				@unlink( $old_path );
			}
		}

		update_post_meta( $post_id, '_davenham_doc_file', self::STORAGE_DIR . '/' . $stored_name );
		update_post_meta( $post_id, '_davenham_doc_name', $original );
		update_post_meta( $post_id, '_davenham_doc_mime', $check['type'] );
		update_post_meta( $post_id, '_davenham_doc_size', (int) $file['size'] );
	}

	private static function flag_error( $post_id, $message ) {
		set_transient( 'davenham_doc_error_' . get_current_user_id(), $message, 60 );
	}

	public static function render_admin_notices() {
		$key = 'davenham_doc_error_' . get_current_user_id();
		$msg = get_transient( $key );
		if ( $msg ) {
			delete_transient( $key );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}

	public static function admin_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['davenham_doc_file'] = __( 'File', 'davenham-documents' );
			}
		}
		return $new;
	}

	public static function admin_column_content( $column, $post_id ) {
		if ( 'davenham_doc_file' !== $column ) {
			return;
		}
		$name = get_post_meta( $post_id, '_davenham_doc_name', true );
		echo $name ? esc_html( $name ) : '<span style="color:var(--scout-danger,#ED3F23);">' . esc_html__( 'No file', 'davenham-documents' ) . '</span>';
	}

	public static function enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( $screen && self::POST_TYPE === $screen->post_type ) {
			wp_enqueue_style( 'davenham-documents', DDOC_URL . 'assets/documents.css', array(), DDOC_VERSION );
		}
	}

	/* ---------------------------------------------------------------------
	 * Frontend library
	 * ------------------------------------------------------------------- */

	public static function register_public_assets() {
		wp_register_style( 'davenham-documents', DDOC_URL . 'assets/documents.css', array(), DDOC_VERSION );
		wp_register_script( 'davenham-documents', DDOC_URL . 'assets/documents.js', array(), DDOC_VERSION, true );
	}

	public static function current_user_can_view() {
		return is_user_logged_in() && current_user_can( self::READ_CAP );
	}

	public static function download_url( $doc_id ) {
		$url = add_query_arg(
			array(
				'action'   => 'davenham_download_document',
				'doc'      => (int) $doc_id,
				'_wpnonce' => wp_create_nonce( self::DOWNLOAD_NONCE . '_' . (int) $doc_id ),
			),
			admin_url( 'admin-ajax.php' )
		);
		return $url;
	}

	/**
	 * Public file URL — for documents in public categories. No nonce (the file
	 * is public, and a nonce would just expire / break under page caching).
	 * $inline = true serves it for in-browser viewing; false forces download.
	 */
	public static function public_file_url( $doc_id, $inline = false ) {
		$args = array(
			'action' => 'davenham_download_document',
			'doc'    => (int) $doc_id,
		);
		if ( $inline ) {
			$args['disposition'] = 'inline';
		}
		return add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
	}

	public static function render_library_shortcode( $atts ) {
		wp_enqueue_style( 'davenham-documents' );
		wp_enqueue_script( 'davenham-documents' );

		$can_view = self::current_user_can_view();

		$here      = get_permalink();
		$login_url = wp_login_url( $here ? $here : home_url( '/' ) );

		// Build grouped data only when allowed.
		$grouped = array();
		if ( $can_view ) {
			$grouped = self::get_grouped_documents();
		}

		ob_start();
		include DDOC_DIR . 'templates/library.php';
		return ob_get_clean();
	}

	/**
	 * Returns array of [ 'term' => WP_Term|null, 'docs' => WP_Post[] ], ordered by category.
	 */
	private static function get_grouped_documents() {
		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$by_term = array();
		$uncateg = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $doc ) {
				// Only list documents that actually have a stored file.
				if ( ! get_post_meta( $doc->ID, '_davenham_doc_file', true ) ) {
					continue;
				}
				$terms = get_the_terms( $doc->ID, self::TAXONOMY );
				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( ! isset( $by_term[ $term->term_id ] ) ) {
							$by_term[ $term->term_id ] = array( 'term' => $term, 'docs' => array() );
						}
						$by_term[ $term->term_id ]['docs'][] = $doc;
					}
				} else {
					$uncateg[] = $doc;
				}
			}
		}

		// Sort categories by name.
		usort(
			$by_term,
			function ( $a, $b ) {
				return strcasecmp( $a['term']->name, $b['term']->name );
			}
		);

		$result = array_values( $by_term );
		if ( ! empty( $uncateg ) ) {
			$result[] = array( 'term' => null, 'docs' => $uncateg );
		}
		return $result;
	}

	/* ---------------------------------------------------------------------
	 * Public Charity Documents (front-facing, shop-styled)
	 * ------------------------------------------------------------------- */

	public static function render_public_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'category'     => '',   // optional slug — limit to one public category
				'request_url'  => '',   // optional CTA (e.g. contact form) link
				'request_text' => 'Request a risk assessment',
				'request_note' => '',   // small line under the CTA
			),
			$atts,
			'davenham_public_documents'
		);

		wp_enqueue_style( 'davenham-documents' );

		$groups       = self::get_public_grouped_documents( $atts['category'] );
		$request_url  = $atts['request_url'] ? esc_url( $atts['request_url'] ) : '';
		$request_text = $atts['request_text'];
		$request_note = $atts['request_note'];

		ob_start();
		include DDOC_DIR . 'templates/public-documents.php';
		return ob_get_clean();
	}

	/**
	 * Grouped public documents: only categories flagged public, only documents
	 * that actually have a stored file. Optionally limited to one category slug.
	 */
	private static function get_public_grouped_documents( $category_slug = '' ) {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => true,
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$public_terms = array();
		foreach ( $terms as $term ) {
			if ( ! self::is_public_category( $term->term_id ) ) {
				continue;
			}
			if ( $category_slug && $term->slug !== $category_slug ) {
				continue;
			}
			$public_terms[] = $term;
		}
		if ( empty( $public_terms ) ) {
			return array();
		}

		usort(
			$public_terms,
			function ( $a, $b ) {
				return strcasecmp( $a->name, $b->name );
			}
		);

		$result = array();
		foreach ( $public_terms as $term ) {
			$query = new WP_Query(
				array(
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					'tax_query'      => array(
						array(
							'taxonomy' => self::TAXONOMY,
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
				)
			);

			$docs = array();
			foreach ( $query->posts as $doc ) {
				if ( ! get_post_meta( $doc->ID, '_davenham_doc_file', true ) ) {
					continue;
				}
				$docs[] = $doc;
			}
			if ( ! empty( $docs ) ) {
				$result[] = array( 'term' => $term, 'docs' => $docs );
			}
		}
		return $result;
	}

	/**
	 * File-type label + brand colour for a document (from its stored name/mime),
	 * matching the design.md file-type badge palette.
	 */
	public static function file_type_badge( $doc_id ) {
		$name = (string) get_post_meta( $doc_id, '_davenham_doc_name', true );
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		$map  = array(
			'pdf'  => array( 'PDF', '#ED3F23' ),
			'doc'  => array( 'DOC', '#003982' ),
			'docx' => array( 'DOC', '#003982' ),
			'xls'  => array( 'XLS', '#008A1C' ),
			'xlsx' => array( 'XLS', '#008A1C' ),
			'ppt'  => array( 'PPT', '#FF912A' ),
			'pptx' => array( 'PPT', '#FF912A' ),
			'png'  => array( 'IMG', '#590FA9' ),
			'jpg'  => array( 'IMG', '#590FA9' ),
			'jpeg' => array( 'IMG', '#590FA9' ),
		);
		return isset( $map[ $ext ] ) ? $map[ $ext ] : array( strtoupper( $ext ? $ext : 'FILE' ), '#6E6E6E' );
	}

	/* ---------------------------------------------------------------------
	 * Gated download handler
	 * ------------------------------------------------------------------- */

	public static function handle_download_nopriv() {
		$target = isset( $_SERVER['HTTP_REFERER'] ) ? wp_validate_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] ), home_url( '/' ) ) : home_url( '/' );
		wp_safe_redirect( wp_login_url( $target ) );
		exit;
	}

	public static function handle_download() {
		$doc_id = isset( $_GET['doc'] ) ? absint( $_GET['doc'] ) : 0;

		$doc = get_post( $doc_id );
		if ( ! $doc || self::POST_TYPE !== $doc->post_type || 'publish' !== $doc->post_status ) {
			status_header( 404 );
			wp_die( esc_html__( 'Document not found.', 'davenham-documents' ), '', array( 'response' => 404 ) );
		}

		// Public-category documents are downloadable by anyone (charity
		// transparency). Everything else stays gated to logged-in trustees /
		// leaders and requires a valid, per-document nonce.
		if ( ! self::document_is_public( $doc_id ) ) {
			if ( ! self::current_user_can_view() ) {
				if ( ! is_user_logged_in() ) {
					$target = isset( $_SERVER['HTTP_REFERER'] ) ? wp_validate_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] ), home_url( '/' ) ) : home_url( '/' );
					wp_safe_redirect( wp_login_url( $target ) );
					exit;
				}
				status_header( 403 );
				wp_die( esc_html__( 'You do not have permission to download this document.', 'davenham-documents' ), '', array( 'response' => 403 ) );
			}

			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, self::DOWNLOAD_NONCE . '_' . $doc_id ) ) {
				status_header( 403 );
				wp_die( esc_html__( 'This download link has expired. Please reload the documents page and try again.', 'davenham-documents' ), '', array( 'response' => 403 ) );
			}
		}

		$rel  = get_post_meta( $doc_id, '_davenham_doc_file', true );
		$path = $rel ? self::resolve_path( $rel ) : '';

		if ( ! $path || ! file_exists( $path ) ) {
			status_header( 404 );
			wp_die( esc_html__( 'The file is missing.', 'davenham-documents' ), '', array( 'response' => 404 ) );
		}

		$name = get_post_meta( $doc_id, '_davenham_doc_name', true );
		$name = $name ? $name : ( $doc->post_title . '.' . pathinfo( $path, PATHINFO_EXTENSION ) );
		$mime = get_post_meta( $doc_id, '_davenham_doc_mime', true );
		$mime = $mime ? $mime : 'application/octet-stream';

		$ascii_name = str_replace( array( '"', "\r", "\n" ), '', $name );

		// "View" links pass disposition=inline so the browser opens the file;
		// otherwise force a download.
		$disposition = ( isset( $_GET['disposition'] ) && 'inline' === $_GET['disposition'] ) ? 'inline' : 'attachment';

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( "Content-Disposition: {$disposition}; filename=\"{$ascii_name}\"; filename*=UTF-8''" . rawurlencode( $name ) );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex, nofollow', true );

		// Drop any buffering so the file streams cleanly, then send it in
		// chunks — readfile() on a large PDF can exceed the shared-host memory
		// limit; an 8KB fread loop stays flat regardless of file size.
		while ( ob_get_level() ) {
			@ob_end_clean();
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 );
		}

		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) {
			status_header( 500 );
			exit;
		}
		while ( ! feof( $handle ) ) {
			echo fread( $handle, 8192 ); // phpcs:ignore WordPress.Security.EscapeOutput
			flush();
		}
		fclose( $handle );
		exit;
	}

	/**
	 * Resolve a stored relative path to an absolute path, guarding against
	 * path traversal — the resolved file MUST live inside the storage dir.
	 */
	private static function resolve_path( $relative ) {
		$relative = ltrim( (string) $relative, '/' );
		$base     = self::storage_path();
		$full     = self::uploads_basedir() . '/' . $relative;

		$real_base = realpath( $base );
		$real_full = realpath( $full );

		if ( ! $real_base || ! $real_full ) {
			return '';
		}
		// Must be inside the storage dir.
		if ( 0 !== strpos( $real_full, $real_base . DIRECTORY_SEPARATOR ) ) {
			return '';
		}
		return $real_full;
	}
}

register_activation_hook( __FILE__, array( 'Davenham_Documents', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Davenham_Documents', 'deactivate' ) );

Davenham_Documents::init();
