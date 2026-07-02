<?php
/**
 * Plugin Name: Davenham Inventory
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Backend-only catalogue of everything the Group owns — log, track, categorise, and mark kit in/out when it's borrowed. Mobile-friendly with bulk actions.
 * Version:     1.0.0
 * Author:      Davenham Scout Group
 * Text Domain: davenham-inventory
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

final class Davenham_Inventory {

	const CPT     = 'dvh_asset';
	const TAX     = 'dvh_asset_cat';
	const MENU    = 'davenham-inventory';
	const CAP     = 'edit_posts';
	const VERSION = '1.0.0';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 9 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_seed' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_toggle' ) );

		// Item edit screen meta box + layout.
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'editor_label' ) );
		add_filter( 'get_user_option_meta-box-order_' . self::CPT, array( __CLASS__, 'meta_box_order' ) );

		// List table columns.
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::CPT . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_query' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'restrict_manage_posts', array( __CLASS__, 'status_filter' ) );

		// Bulk actions.
		add_filter( 'bulk_actions-edit-' . self::CPT, array( __CLASS__, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-' . self::CPT, array( __CLASS__, 'handle_bulk' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'bulk_notice' ) );

		add_action( 'admin_head', array( __CLASS__, 'admin_css' ) );
	}

	/* ── Register CPT + taxonomy ─────────────────────────────────────────── */

	public static function register() {
		register_post_type( self::CPT, array(
			'labels' => array(
				'name'               => __( 'Inventory', 'davenham-inventory' ),
				'singular_name'      => __( 'Item', 'davenham-inventory' ),
				'add_new'            => __( 'Add Item', 'davenham-inventory' ),
				'add_new_item'       => __( 'Add New Item', 'davenham-inventory' ),
				'edit_item'          => __( 'Edit Item', 'davenham-inventory' ),
				'new_item'           => __( 'New Item', 'davenham-inventory' ),
				'view_item'          => __( 'View Item', 'davenham-inventory' ),
				'search_items'       => __( 'Search Inventory', 'davenham-inventory' ),
				'not_found'          => __( 'No items yet.', 'davenham-inventory' ),
				'not_found_in_trash' => __( 'No items in the bin.', 'davenham-inventory' ),
				'all_items'          => __( 'All Items', 'davenham-inventory' ),
				'menu_name'          => __( 'Inventory', 'davenham-inventory' ),
			),
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => self::MENU,
			'show_in_rest'       => false,
			'supports'           => array( 'title', 'thumbnail', 'editor' ),
			'menu_icon'          => 'dashicons-archive',
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		) );

		register_taxonomy( self::TAX, self::CPT, array(
			'labels' => array(
				'name'          => __( 'Categories', 'davenham-inventory' ),
				'singular_name' => __( 'Category', 'davenham-inventory' ),
				'add_new_item'  => __( 'Add New Category', 'davenham-inventory' ),
				'menu_name'     => __( 'Categories', 'davenham-inventory' ),
				'all_items'     => __( 'All Categories', 'davenham-inventory' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'show_in_rest'      => false,
		) );
	}

	public static function statuses() {
		return array( 'in' => __( 'In', 'davenham-inventory' ), 'out' => __( 'Out', 'davenham-inventory' ) );
	}

	public static function conditions() {
		return array(
			'new'  => __( 'New', 'davenham-inventory' ),
			'good' => __( 'Good', 'davenham-inventory' ),
			'fair' => __( 'Fair', 'davenham-inventory' ),
			'poor' => __( 'Poor / repair', 'davenham-inventory' ),
		);
	}

	/* ── Menu: top-level Inventory → Categories dashboard ────────────────── */

	public static function admin_menu() {
		add_menu_page(
			__( 'Inventory', 'davenham-inventory' ),
			__( 'Inventory', 'davenham-inventory' ),
			self::CAP,
			self::MENU,
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-archive',
			26
		);
		add_submenu_page(
			self::MENU,
			__( 'Categories overview', 'davenham-inventory' ),
			__( 'Overview', 'davenham-inventory' ),
			self::CAP,
			self::MENU,
			array( __CLASS__, 'render_dashboard' )
		);
	}

	/* ── Categories dashboard ────────────────────────────────────────────── */

	public static function render_dashboard() {
		$terms = get_terms( array( 'taxonomy' => self::TAX, 'hide_empty' => false ) );
		$total_items = (int) wp_count_posts( self::CPT )->publish;
		$total_out   = self::count_by_status( 'out' );
		$list_url    = admin_url( 'edit.php?post_type=' . self::CPT );
		?>
		<div class="wrap dvh-inv-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Inventory', 'davenham-inventory' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::CPT ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add Item', 'davenham-inventory' ); ?></a>
			<a href="<?php echo esc_url( $list_url ); ?>" class="page-title-action"><?php esc_html_e( 'All Items', 'davenham-inventory' ); ?></a>

			<div class="dvh-inv-summary">
				<div class="dvh-inv-stat"><span class="dvh-inv-fig"><?php echo (int) $total_items; ?></span><span><?php esc_html_e( 'items catalogued', 'davenham-inventory' ); ?></span></div>
				<div class="dvh-inv-stat"><span class="dvh-inv-fig"><?php echo count( is_wp_error( $terms ) ? array() : $terms ); ?></span><span><?php esc_html_e( 'categories', 'davenham-inventory' ); ?></span></div>
				<div class="dvh-inv-stat dvh-inv-stat--out"><span class="dvh-inv-fig"><?php echo (int) $total_out; ?></span><span><?php esc_html_e( 'currently out', 'davenham-inventory' ); ?></span></div>
			</div>

			<h2><?php esc_html_e( 'Categories', 'davenham-inventory' ); ?></h2>
			<?php if ( is_wp_error( $terms ) || empty( $terms ) ) : ?>
				<p><?php esc_html_e( 'No categories yet. Add one to get started.', 'davenham-inventory' ); ?> <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . self::TAX . '&post_type=' . self::CPT ) ); ?>"><?php esc_html_e( 'Manage categories', 'davenham-inventory' ); ?></a></p>
			<?php else : ?>
				<div class="dvh-inv-grid">
					<?php foreach ( $terms as $term ) :
						$out = self::count_by_status( 'out', $term->term_id );
						$cat_url = add_query_arg( array( 'post_type' => self::CPT, self::TAX => $term->slug ), admin_url( 'edit.php' ) );
					?>
					<a class="dvh-inv-card" href="<?php echo esc_url( $cat_url ); ?>">
						<span class="dvh-inv-card__name"><?php echo esc_html( $term->name ); ?></span>
						<span class="dvh-inv-card__count"><?php echo (int) $term->count; ?> <?php esc_html_e( 'items', 'davenham-inventory' ); ?></span>
						<?php if ( $out > 0 ) : ?><span class="dvh-inv-card__out"><?php echo (int) $out; ?> <?php esc_html_e( 'out', 'davenham-inventory' ); ?></span><?php endif; ?>
					</a>
					<?php endforeach; ?>
				</div>
				<p style="margin-top:16px;"><a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . self::TAX . '&post_type=' . self::CPT ) ); ?>"><?php esc_html_e( 'Manage categories', 'davenham-inventory' ); ?></a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function count_by_status( $status, $term_id = 0 ) {
		$args = array(
			'post_type'      => self::CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'meta_query'     => array( array( 'key' => '_dvh_status', 'value' => $status ) ),
		);
		if ( $term_id ) {
			$args['tax_query'] = array( array( 'taxonomy' => self::TAX, 'field' => 'term_id', 'terms' => (int) $term_id ) );
		}
		$q = new WP_Query( $args );
		return (int) $q->found_posts;
	}

	/* ── Meta box ────────────────────────────────────────────────────────── */

	public static function meta_box() {
		add_meta_box( 'dvh_inv_details', __( 'Item details', 'davenham-inventory' ), array( __CLASS__, 'render_meta_box' ), self::CPT, 'side', 'default' );
	}

	// Heading + hint above the description editor (it otherwise has no label).
	public static function editor_label( $post ) {
		if ( ! $post || self::CPT !== $post->post_type ) {
			return;
		}
		echo '<h2 class="dvh-editor-heading">' . esc_html__( 'Item description & notes', 'davenham-inventory' ) . '</h2>';
		echo '<p class="dvh-editor-hint">' . esc_html__( "Optional — record anything useful: what's in the set, serial numbers, repair history or storage notes.", 'davenham-inventory' ) . '</p>';
	}

	// Default side-column order: Publish → Item details → Categories → Featured
	// image. Respects any later manual drag (WordPress saves that over this).
	public static function meta_box_order( $order ) {
		if ( ! empty( $order ) ) {
			return $order;
		}
		return array(
			'side'     => 'submitdiv,dvh_inv_details,dvh_asset_catdiv,postimagediv',
			'normal'   => '',
			'advanced' => '',
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'dvh_inv_save', 'dvh_inv_nonce' );
		$qty        = get_post_meta( $post->ID, '_dvh_qty', true );
		$status     = get_post_meta( $post->ID, '_dvh_status', true ) ?: 'in';
		$condition  = get_post_meta( $post->ID, '_dvh_condition', true ) ?: 'good';
		$location   = get_post_meta( $post->ID, '_dvh_location', true );
		$borrowed   = get_post_meta( $post->ID, '_dvh_borrowed_by', true );
		?>
		<p><label><strong><?php esc_html_e( 'Quantity', 'davenham-inventory' ); ?></strong><br>
			<input type="number" min="0" step="1" name="dvh_qty" value="<?php echo esc_attr( '' === $qty ? 1 : $qty ); ?>" class="widefat"></label></p>
		<p><label><strong><?php esc_html_e( 'Condition', 'davenham-inventory' ); ?></strong><br>
			<select name="dvh_condition" class="widefat">
				<?php foreach ( self::conditions() as $k => $v ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $condition, $k ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select></label></p>
		<p><label><strong><?php esc_html_e( 'Storage location', 'davenham-inventory' ); ?></strong><br>
			<input type="text" name="dvh_location" value="<?php echo esc_attr( $location ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Peckmill store, shelf 3', 'davenham-inventory' ); ?>"></label></p>
		<p><label><strong><?php esc_html_e( 'Status', 'davenham-inventory' ); ?></strong><br>
			<select name="dvh_status" class="widefat">
				<?php foreach ( self::statuses() as $k => $v ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $status, $k ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select></label></p>
		<p><label><strong><?php esc_html_e( 'Borrowed by (if out)', 'davenham-inventory' ); ?></strong><br>
			<input type="text" name="dvh_borrowed_by" value="<?php echo esc_attr( $borrowed ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Scouts – summer camp', 'davenham-inventory' ); ?>"></label></p>
		<?php
	}

	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['dvh_inv_nonce'] ) || ! wp_verify_nonce( $_POST['dvh_inv_nonce'], 'dvh_inv_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_dvh_qty', max( 0, (int) ( $_POST['dvh_qty'] ?? 1 ) ) );
		$status = ( isset( $_POST['dvh_status'] ) && 'out' === $_POST['dvh_status'] ) ? 'out' : 'in';
		update_post_meta( $post_id, '_dvh_status', $status );
		$cond = isset( $_POST['dvh_condition'] ) && array_key_exists( $_POST['dvh_condition'], self::conditions() ) ? $_POST['dvh_condition'] : 'good';
		update_post_meta( $post_id, '_dvh_condition', $cond );
		update_post_meta( $post_id, '_dvh_location', sanitize_text_field( $_POST['dvh_location'] ?? '' ) );
		update_post_meta( $post_id, '_dvh_borrowed_by', sanitize_text_field( $_POST['dvh_borrowed_by'] ?? '' ) );
	}

	/* ── List table columns ──────────────────────────────────────────────── */

	public static function columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['dvh_qty']       = __( 'Qty', 'davenham-inventory' );
				$new['dvh_condition'] = __( 'Condition', 'davenham-inventory' );
				$new['dvh_location']  = __( 'Location', 'davenham-inventory' );
				$new['dvh_status']    = __( 'Status', 'davenham-inventory' );
			}
		}
		return $new;
	}

	public static function column_content( $col, $post_id ) {
		if ( 'dvh_qty' === $col ) {
			echo esc_html( (string) ( get_post_meta( $post_id, '_dvh_qty', true ) ?: '1' ) );
		} elseif ( 'dvh_condition' === $col ) {
			$c = get_post_meta( $post_id, '_dvh_condition', true ) ?: 'good';
			$conds = self::conditions();
			echo esc_html( $conds[ $c ] ?? $c );
		} elseif ( 'dvh_location' === $col ) {
			echo esc_html( get_post_meta( $post_id, '_dvh_location', true ) );
		} elseif ( 'dvh_status' === $col ) {
			$status = get_post_meta( $post_id, '_dvh_status', true ) ?: 'in';
			$out    = 'out' === $status;
			$by     = get_post_meta( $post_id, '_dvh_borrowed_by', true );
			echo '<span class="dvh-badge dvh-badge--' . esc_attr( $status ) . '">' . ( $out ? esc_html__( 'Out', 'davenham-inventory' ) : esc_html__( 'In', 'davenham-inventory' ) ) . '</span>';
			if ( $out && $by ) {
				echo '<br><small>' . esc_html( $by ) . '</small>';
			}
		}
	}

	public static function sortable_columns( $cols ) {
		$cols['dvh_status'] = 'dvh_status';
		$cols['dvh_qty']    = 'dvh_qty';
		return $cols;
	}

	public static function sort_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$ob = $query->get( 'orderby' );
		if ( 'dvh_status' === $ob ) {
			$query->set( 'meta_key', '_dvh_status' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'dvh_qty' === $ob ) {
			$query->set( 'meta_key', '_dvh_qty' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	public static function status_filter() {
		global $typenow;
		if ( self::CPT !== $typenow ) {
			return;
		}
		// Category dropdown (WordPress doesn't auto-add one for custom taxonomies).
		wp_dropdown_categories( array(
			'show_option_all' => __( 'All categories', 'davenham-inventory' ),
			'taxonomy'        => self::TAX,
			'name'            => self::TAX,
			'value_field'     => 'slug',
			'selected'        => isset( $_GET[ self::TAX ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::TAX ] ) ) : '',
			'hierarchical'    => true,
			'hide_empty'      => false,
			'show_count'      => true,
			'orderby'         => 'name',
		) );

		$current = isset( $_GET['dvh_status_f'] ) ? sanitize_key( $_GET['dvh_status_f'] ) : '';
		echo '<select name="dvh_status_f"><option value="">' . esc_html__( 'All statuses', 'davenham-inventory' ) . '</option>';
		foreach ( self::statuses() as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $current, $k, false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select>';
	}

	/* ── Single mark in/out (row action) ─────────────────────────────────── */

	public static function row_actions( $actions, $post ) {
		if ( self::CPT !== $post->post_type ) {
			return $actions;
		}
		$status = get_post_meta( $post->ID, '_dvh_status', true ) ?: 'in';
		$to     = 'out' === $status ? 'in' : 'out';
		$label  = 'out' === $to ? __( 'Mark out', 'davenham-inventory' ) : __( 'Mark in', 'davenham-inventory' );
		$url    = wp_nonce_url( add_query_arg( array( 'dvh_toggle' => $post->ID, 'dvh_to' => $to ) ), 'dvh_toggle_' . $post->ID );
		$actions['dvh_toggle'] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		return $actions;
	}

	public static function handle_toggle() {
		if ( ! isset( $_GET['dvh_toggle'] ) ) {
			return;
		}
		$id = (int) $_GET['dvh_toggle'];
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'dvh_toggle_' . $id ) ) {
			return;
		}
		$to = ( isset( $_GET['dvh_to'] ) && 'out' === $_GET['dvh_to'] ) ? 'out' : 'in';
		update_post_meta( $id, '_dvh_status', $to );
		if ( 'in' === $to ) {
			delete_post_meta( $id, '_dvh_borrowed_by' );
		}
		wp_safe_redirect( remove_query_arg( array( 'dvh_toggle', 'dvh_to', '_wpnonce' ) ) );
		exit;
	}

	/* ── Bulk mark in/out ────────────────────────────────────────────────── */

	public static function bulk_actions( $actions ) {
		$actions['dvh_mark_out'] = __( 'Mark as out', 'davenham-inventory' );
		$actions['dvh_mark_in']  = __( 'Mark as in', 'davenham-inventory' );
		return $actions;
	}

	public static function handle_bulk( $redirect, $action, $ids ) {
		if ( 'dvh_mark_out' !== $action && 'dvh_mark_in' !== $action ) {
			return $redirect;
		}
		$to = 'dvh_mark_out' === $action ? 'out' : 'in';
		$n  = 0;
		foreach ( (array) $ids as $id ) {
			if ( current_user_can( 'edit_post', $id ) ) {
				update_post_meta( $id, '_dvh_status', $to );
				if ( 'in' === $to ) {
					delete_post_meta( $id, '_dvh_borrowed_by' );
				}
				$n++;
			}
		}
		return add_query_arg( 'dvh_bulk_done', $n, $redirect );
	}

	public static function bulk_notice() {
		if ( ! empty( $_GET['dvh_bulk_done'] ) ) {
			$n = (int) $_GET['dvh_bulk_done'];
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( _n( '%d item updated.', '%d items updated.', $n, 'davenham-inventory' ), $n ) ) . '</p></div>';
		}
	}

	/* ── Status filter query ─────────────────────────────────────────────── */

	public static function apply_status_filter( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( self::CPT === $query->get( 'post_type' ) && ! empty( $_GET['dvh_status_f'] ) ) {
			$query->set( 'meta_query', array( array( 'key' => '_dvh_status', 'value' => sanitize_key( $_GET['dvh_status_f'] ) ) ) );
		}
	}

	/* ── Seed sample data ────────────────────────────────────────────────── */

	public static function maybe_seed() {
		if ( get_option( 'dvh_inventory_seeded' ) ) {
			return;
		}
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		update_option( 'dvh_inventory_seeded', 1 );

		$data = array(
			'Camping Gear' => array(
				array( 'Patrol tents', 8 ), array( 'Dining shelter', 3 ), array( 'Trangia cookers', 12 ),
				array( 'Gas stoves', 4 ), array( 'Cutlery sets', 40 ), array( 'Cool boxes', 5 ),
				array( 'Sleeping mats', 20 ), array( 'Groundsheets', 10 ),
			),
			'Kitchen & Catering' => array(
				array( 'Dutch oven', 1 ), array( 'Cooking pots (large)', 6 ), array( 'Water containers', 8 ),
				array( 'Washing-up bowls', 6 ), array( 'Serving spoons', 15 ),
			),
			'Arts & Crafts' => array(
				array( 'Craft box (general)', 1 ), array( 'PVA glue', 10 ), array( 'Scissors', 30 ),
				array( 'Poster paint set', 2 ), array( 'Coloured card (box)', 1 ),
			),
			'Sports Equipment' => array(
				array( 'Footballs', 6 ), array( 'Rounders sets', 2 ), array( 'Frisbees', 10 ),
				array( 'Parachute game', 1 ), array( 'Bibs (set)', 2 ),
			),
			'Furniture' => array(
				array( 'Folding chairs', 40 ), array( 'Trestle tables', 12 ), array( 'Storage crates', 25 ),
			),
			'Tools' => array(
				array( 'Mallets', 15 ), array( 'Bow saws', 6 ), array( 'Loppers', 4 ),
				array( 'Spades', 5 ), array( 'Bill hooks', 3 ),
			),
			'Games & Activities' => array(
				array( 'Board games', 10 ), array( 'Skittles set', 1 ), array( 'Quiz buzzers', 1 ),
			),
			'First Aid & Safety' => array(
				array( 'First aid kits', 6 ), array( 'Fire blankets', 3 ), array( 'Hi-vis vests', 30 ),
			),
		);

		// A couple marked "out" for a realistic starting picture.
		$out_items = array( 'Patrol tents' => 'Scouts – summer camp', 'Dutch oven' => 'Cubs – cookout' );

		foreach ( $data as $cat => $items ) {
			$term = term_exists( $cat, self::TAX );
			if ( ! $term ) {
				$term = wp_insert_term( $cat, self::TAX );
			}
			$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
			foreach ( $items as $item ) {
				$post_id = wp_insert_post( array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'post_title'  => $item[0],
				) );
				if ( $post_id && ! is_wp_error( $post_id ) ) {
					wp_set_object_terms( $post_id, $term_id, self::TAX );
					update_post_meta( $post_id, '_dvh_qty', (int) $item[1] );
					update_post_meta( $post_id, '_dvh_condition', 'good' );
					$status = isset( $out_items[ $item[0] ] ) ? 'out' : 'in';
					update_post_meta( $post_id, '_dvh_status', $status );
					update_post_meta( $post_id, '_dvh_location', 'Peckmill store' );
					if ( 'out' === $status ) {
						update_post_meta( $post_id, '_dvh_borrowed_by', $out_items[ $item[0] ] );
					}
				}
			}
		}
	}

	/* ── Admin CSS (dashboard cards + status badges, mobile-friendly) ─────── */

	public static function admin_css() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}
		$on_inventory = ( self::CPT === $screen->post_type ) || ( isset( $screen->id ) && false !== strpos( (string) $screen->id, self::MENU ) );
		if ( ! $on_inventory ) {
			return;
		}
		?>
		<style>
			.dvh-inv-summary{display:flex;flex-wrap:wrap;gap:14px;margin:18px 0 8px;}
			.dvh-inv-stat{background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:16px 20px;min-width:150px;box-shadow:0 4px 14px rgba(15,23,42,.05);}
			.dvh-inv-stat .dvh-inv-fig{display:block;font-size:2rem;font-weight:800;color:#003982;line-height:1;}
			.dvh-inv-stat span:last-child{color:#55565A;font-size:.9rem;}
			.dvh-inv-stat--out .dvh-inv-fig{color:#C0490B;}
			.dvh-inv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));align-items:stretch;gap:16px;margin-top:14px;}
			.dvh-inv-card{display:flex;flex-direction:column;gap:6px;min-height:120px;background:#fff;border:1px solid #e7e9ee;border-top:4px solid #590FA9;border-radius:14px;padding:20px;text-decoration:none;box-shadow:0 8px 22px rgba(15,23,42,.06);transition:transform .2s cubic-bezier(.2,.7,.3,1),box-shadow .2s ease;}
			.dvh-inv-card:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(89,15,169,.16);}
			.dvh-inv-wrap .dvh-inv-card:focus{outline:none;box-shadow:0 0 0 3px rgba(89,15,169,.4),0 8px 22px rgba(15,23,42,.06);}
			.dvh-inv-wrap .dvh-inv-card:focus:not(:focus-visible){box-shadow:0 8px 22px rgba(15,23,42,.06);}
			.dvh-inv-card__name{font-size:1.15rem;font-weight:800;color:#003982;line-height:1.2;}
			.dvh-inv-card__count{color:#55565A;font-size:.95rem;}
			.dvh-inv-card__out{align-self:flex-start;margin-top:auto;background:#FCE9DC;color:#C0490B;font-weight:700;font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;padding:3px 12px;border-radius:999px;}
			.dvh-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-weight:700;font-size:.78rem;}
			.dvh-badge--in{background:#DEF3E4;color:#1D6F42;}
			.dvh-badge--out{background:#FCE9DC;color:#C0490B;}
			.dvh-editor-heading{margin:18px 0 2px;font-size:1.15rem;font-weight:800;color:#003982;}
			.dvh-editor-hint{margin:0 0 10px;color:#55565A;}
			/* Give the item edit screen a touch more breathing room */
			.post-type-dvh_asset #titlediv #title{font-size:1.4em;padding:12px 14px;}
			@media (max-width:782px){ .dvh-inv-grid{grid-template-columns:repeat(auto-fill,minmax(45%,1fr));} }
		</style>
		<?php
	}
}

Davenham_Inventory::init();
add_action( 'pre_get_posts', array( 'Davenham_Inventory', 'apply_status_filter' ) );
