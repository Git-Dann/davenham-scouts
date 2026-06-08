<?php
/**
 * Server-side render of the Davenham admin shell.
 *
 * Output here mirrors the DOM the previous client-only JS used to build,
 * but rendered as HTML before any client script runs. This guarantees the
 * shell is present on every admin page — regardless of whether body_class()
 * fires, whether Backbone/Gutenberg take over, or whether JS runs at all.
 *
 * Receives these locals from Davenham_Admin_Suite::render_admin_shell():
 *   $settings      array     plugin settings (from self::settings())
 *   $nav           array     output of app_nav_items()
 *   $admin_groups  array     output of app_admin_groups()
 *   $brand         string    label
 *   $logo_url      string    admin logo URL (may be empty)
 *   $current_user  string    display name
 */

defined( 'ABSPATH' ) || exit;

// Helpers used only inside this template.
if ( ! function_exists( 'das_shell_icon_class' ) ) {
	function das_shell_icon_class( $icon ) {
		$map = array(
			'dashboard'  => 'dashicons-dashboard',
			'tickets'    => 'dashicons-tickets-alt',
			'calendar'   => 'dashicons-calendar-alt',
			'pages'      => 'dashicons-admin-page',
			'media'      => 'dashicons-format-image',
			'cart'       => 'dashicons-cart',
			'orders'     => 'dashicons-cart',
			'products'   => 'dashicons-products',
			'users'      => 'dashicons-admin-users',
			'posts'      => 'dashicons-admin-post',
			'forms'      => 'dashicons-feedback',
			'links'      => 'dashicons-admin-links',
			'payments'   => 'dashicons-money-alt',
			'marketing'  => 'dashicons-megaphone',
			'builder'    => 'dashicons-layout',
			'appearance' => 'dashicons-admin-appearance',
			'analytics'  => 'dashicons-chart-bar',
			'folder'     => 'dashicons-portfolio',
			'admin'      => 'dashicons-admin-tools',
			'plugins'    => 'dashicons-admin-plugins',
			'tools'      => 'dashicons-admin-tools',
			'updates'    => 'dashicons-update',
			'security'   => 'dashicons-shield',
			'backup'     => 'dashicons-database',
			'health'     => 'dashicons-heart',
			'speed'      => 'dashicons-performance',
			'pin'        => 'dashicons-marker',
		);
		return isset( $map[ $icon ] ) ? $map[ $icon ] : 'dashicons-marker';
	}
}

if ( ! function_exists( 'das_shell_is_active' ) ) {
	function das_shell_is_active( $url ) {
		if ( empty( $url ) ) {
			return false;
		}
		$current = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		// Reduce the candidate URL to a path-or-relative form for matching.
		$parts  = wp_parse_url( $url );
		$target = ( isset( $parts['path'] ) ? $parts['path'] : '' )
			. ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
		if ( '' === $target ) {
			return false;
		}
		// Normalise both to drop trailing slashes and any anchor.
		$norm = function ( $u ) {
			$u = preg_replace( '/#.*$/', '', $u );
			$u = preg_replace( '/\/+$/', '', $u );
			return $u;
		};
		$cur = $norm( $current );
		$tar = $norm( $target );
		if ( $cur === $tar ) {
			return true;
		}
		// Exact path match with different/no query string — still "in this section".
		$cur_path = preg_replace( '/\?.*$/', '', $cur );
		$tar_path = preg_replace( '/\?.*$/', '', $tar );
		return $cur_path === $tar_path;
	}
}

if ( ! function_exists( 'das_shell_item_active' ) ) {
	function das_shell_item_active( $item, $admin_groups ) {
		if ( ! empty( $item['url'] ) && das_shell_is_active( $item['url'] ) ) {
			return true;
		}
		if ( isset( $item['kind'] ) && 'admin-tools' === $item['kind'] ) {
			foreach ( (array) $admin_groups as $group ) {
				$links = isset( $group['links'] ) && is_array( $group['links'] ) ? $group['links'] : array();
				foreach ( $links as $link ) {
					if ( ! empty( $link['url'] ) && das_shell_is_active( $link['url'] ) ) {
						return true;
					}
				}
			}
		}
		if ( isset( $item['children'] ) && is_array( $item['children'] ) ) {
			foreach ( $item['children'] as $child ) {
				if ( ! empty( $child['url'] ) && das_shell_is_active( $child['url'] ) ) {
					return true;
				}
			}
		}
		return false;
	}
}

if ( ! function_exists( 'das_shell_has_flyout' ) ) {
	function das_shell_has_flyout( $item ) {
		if ( isset( $item['kind'] ) && 'admin-tools' === $item['kind'] ) {
			return true;
		}
		return isset( $item['children'] ) && is_array( $item['children'] ) && count( $item['children'] ) > 0;
	}
}

// Tag each nav item with a stable index so flyout IDs match.
$indexed = array();
foreach ( (array) $nav as $i => $item ) {
	$item['shellIndex'] = $i;
	$indexed[] = $item;
}

$main_items   = array_filter( $indexed, function ( $it ) { return ! isset( $it['placement'] ) || 'bottom' !== $it['placement']; } );
$bottom_items = array_filter( $indexed, function ( $it ) { return isset( $it['placement'] ) && 'bottom' === $it['placement']; } );

// Page title (taken from the WP admin page title without the WP suffix).
$page_title = '';
if ( function_exists( 'get_admin_page_title' ) ) {
	$page_title = wp_strip_all_tags( get_admin_page_title() );
}
if ( '' === $page_title ) {
	$page_title = 'Davenham Admin';
}

/**
 * Render a single nav item (button if it has a flyout, otherwise a link).
 */
$render_item = function ( $item ) use ( $admin_groups ) {
	$is_flyout = das_shell_has_flyout( $item );
	$classes   = 'das-app-nav-item' . ( das_shell_item_active( $item, $admin_groups ) ? ' is-active' : '' ) . ( $is_flyout ? ' has-flyout' : '' );
	$icon      = '<span class="dashicons ' . esc_attr( das_shell_icon_class( $item['icon'] ?? '' ) ) . '" aria-hidden="true"></span>';
	$label     = '<span class="das-app-nav-label">' . esc_html( $item['label'] ?? '' ) . '</span>';
	$divider   = ! empty( $item['dividerBefore'] ) ? '<div class="das-app-divider" aria-hidden="true"></div>' : '';

	if ( $is_flyout ) {
		$chevron = '<span class="dashicons dashicons-arrow-right-alt2 das-app-nav-chevron" aria-hidden="true"></span>';
		return $divider . '<button type="button" class="' . esc_attr( $classes ) . '" data-das-flyout-target="das-flyout-' . (int) $item['shellIndex'] . '" aria-expanded="false">' . $icon . $label . $chevron . '</button>';
	}

	return $divider . '<a href="' . esc_url( $item['url'] ?? '#' ) . '" class="' . esc_attr( $classes ) . '">' . $icon . $label . '</a>';
};

/**
 * Render the inner content of a flyout panel.
 */
$render_flyout = function ( $item ) use ( $admin_groups ) {
	if ( isset( $item['kind'] ) && 'admin-tools' === $item['kind'] ) {
		$out  = '<div class="das-app-flyout-heading"><strong>' . esc_html( $item['label'] ?? 'Admin' ) . '</strong>';
		$out .= '<a href="' . esc_url( $item['url'] ?? '#' ) . '">Open overview</a></div>';
		foreach ( (array) $admin_groups as $group ) {
			$links = isset( $group['links'] ) && is_array( $group['links'] ) ? $group['links'] : array();
			$out  .= '<section class="das-flyout-folder"><h3><span class="dashicons dashicons-portfolio" aria-hidden="true"></span>' . esc_html( $group['label'] ?? '' ) . '</h3>';
			if ( $links ) {
				foreach ( $links as $link ) {
					$out .= '<a href="' . esc_url( $link['url'] ?? '#' ) . '">' . esc_html( $link['label'] ?? '' ) . '</a>';
				}
			} else {
				$out .= '<p>No links assigned</p>';
			}
			$out .= '</section>';
		}
		return $out;
	}

	$children = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
	$out  = '<div class="das-app-flyout-heading"><strong>' . esc_html( $item['label'] ?? '' ) . '</strong>';
	$out .= '<a href="' . esc_url( $item['url'] ?? '#' ) . '">Open main page</a></div>';
	$out .= '<div class="das-app-flyout-list">';
	foreach ( $children as $link ) {
		$out .= '<a href="' . esc_url( $link['url'] ?? '#' ) . '">' . esc_html( $link['label'] ?? '' ) . '</a>';
	}
	$out .= '</div>';
	return $out;
};
?>
<div class="das-app-shell">
	<aside class="das-app-rail" aria-label="Davenham admin navigation">
		<div class="das-app-brand">
			<?php if ( ! empty( $logo_url ) ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="" />
			<?php else : ?>
				<span class="das-fleur" aria-hidden="true">&#9884;</span>
			<?php endif; ?>
			<strong><?php echo esc_html( $brand ?: 'Davenham Scouts' ); ?></strong>
		</div>
		<nav class="das-app-nav das-app-nav-main">
			<?php foreach ( $main_items as $item ) { echo $render_item( $item ); } ?>
		</nav>
		<nav class="das-app-nav das-app-nav-bottom">
			<?php foreach ( $bottom_items as $item ) { echo $render_item( $item ); } ?>
			<button type="button" class="das-app-nav-item das-app-collapse"><span class="dashicons dashicons-leftright" aria-hidden="true"></span><span class="das-app-nav-label">Collapse</span></button>
		</nav>
	</aside>

	<div class="das-app-flyouts">
		<?php foreach ( $indexed as $item ) :
			if ( ! das_shell_has_flyout( $item ) ) continue; ?>
			<div class="das-app-flyout" id="das-flyout-<?php echo (int) $item['shellIndex']; ?>" role="menu">
				<?php echo $render_flyout( $item ); ?>
			</div>
		<?php endforeach; ?>
	</div>

	<header class="das-app-topbar">
		<button type="button" class="das-app-mobile-menu" aria-label="Open admin navigation">
			<span class="dashicons dashicons-menu-alt3" aria-hidden="true"></span>
		</button>
		<div>
			<strong><?php echo esc_html( $page_title ); ?></strong>
			<span>Scout group operations</span>
		</div>
		<span class="das-app-user"><?php echo esc_html( $current_user ); ?></span>
	</header>

	<button type="button" class="das-app-overlay" aria-label="Close admin navigation"></button>
</div>
