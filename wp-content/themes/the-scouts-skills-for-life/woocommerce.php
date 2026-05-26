<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$hero = function_exists( 'scouts_get_woocommerce_hero_data' )
    ? scouts_get_woocommerce_hero_data()
    : [
        'title' => 'Shop',
        'intro' => '',
        'image' => '',
    ];

$support = function_exists( 'scouts_woocommerce_support_copy' )
    ? scouts_woocommerce_support_copy()
    : [
        'title'   => 'Need help with an order?',
        'content' => '',
        'cta'     => home_url( '/contact/' ),
        'label'   => 'Contact the team',
    ];

// On the Shop landing page, if the Shop post has builder block markup,
// render that INSTEAD of the default sidebar+product loop layout. This
// lets us turn the shop landing into a properly composed marketing page
// (category tiles, featured product, promo banner, etc.) while category
// and product detail pages still use the standard product loop.
$is_shop_landing = function_exists( 'is_shop' ) && is_shop() && ! is_product_taxonomy();
$shop_post       = $is_shop_landing && function_exists( 'wc_get_page_id' )
    ? get_post( wc_get_page_id( 'shop' ) )
    : null;
$shop_has_blocks = $shop_post && false !== strpos( (string) $shop_post->post_content, '<!-- wp:davenham/' );
// If the Shop landing has its own shop-hero block, suppress the standard
// theme hero so we don't render two heroes in a row.
$shop_has_own_hero = $shop_post && false !== strpos( (string) $shop_post->post_content, '<!-- wp:davenham/shop-hero' );

// Branded hero for product category archives — replaces the generic
// "Shop" hero with the category's own name, count and brand colour.
$is_product_cat_page = function_exists( 'is_product_category' ) && is_product_category();
$cat_term            = $is_product_cat_page ? get_queried_object() : null;
$cat_accent          = '#590FA9';
$cat_gradient        = 'linear-gradient(135deg, #003982 0%, #590FA9 100%)';
if ( $cat_term && ! is_wp_error( $cat_term ) ) {
    $cat_map = array(
        'event-tickets'     => array( '#590FA9', 'linear-gradient(135deg, #590FA9 0%, #003982 100%)' ),
        'group-merchandise' => array( '#003982', 'linear-gradient(135deg, #003982 0%, #088486 100%)' ),
        'fundraising'       => array( '#ED3F23', 'linear-gradient(135deg, #FF912A 0%, #ED3F23 100%)' ),
        'equipment-kit'     => array( '#008A1C', 'linear-gradient(135deg, #205B41 0%, #008A1C 100%)' ),
    );
    if ( isset( $cat_map[ $cat_term->slug ] ) ) {
        $cat_accent   = $cat_map[ $cat_term->slug ][0];
        $cat_gradient = $cat_map[ $cat_term->slug ][1];
    }
}
?>

<?php if ( $is_product_cat_page && $cat_term && ! is_wp_error( $cat_term ) ) : ?>

<section class="shop_cat_hero" style="--cat-gradient: <?php echo esc_attr( $cat_gradient ); ?>; --cat-accent: <?php echo esc_attr( $cat_accent ); ?>;">
    <div class="shop_cat_hero__overlay" aria-hidden="true"></div>
    <div class="wrapper">
        <nav class="shop_cat_hero__crumbs" aria-label="Breadcrumb">
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Shop</a>
            <span aria-hidden="true">/</span>
            <span><?php echo esc_html( $cat_term->name ); ?></span>
        </nav>
        <span class="shop_cat_hero__eyebrow">Browse category</span>
        <h1 class="shop_cat_hero__title"><?php echo esc_html( $cat_term->name ); ?></h1>
        <?php if ( $cat_term->description ) : ?>
            <p class="shop_cat_hero__desc"><?php echo wp_kses_post( $cat_term->description ); ?></p>
        <?php endif; ?>
        <div class="shop_cat_hero__meta">
            <span class="shop_cat_hero__count"><?php echo (int) $cat_term->count; ?> <?php echo esc_html( _n( 'item', 'items', (int) $cat_term->count, 'the-scouts-skills-for-life' ) ); ?></span>
            <a class="shop_cat_hero__back" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">← All categories</a>
        </div>
    </div>
</section>

<?php elseif ( ! $shop_has_own_hero ) : ?>
<section class="hero standard cf">
    <?php if ( ! empty( $hero['image'] ) ) : ?>
        <img src="<?php echo esc_url( $hero['image'] ); ?>" class="bg" alt="" decoding="async" />
    <?php endif; ?>
    <div class="wrapper alt">
        <div class="inner">
            <span class="section-eyebrow">Support Davenham Scouts</span>
            <h2><?php echo esc_html( $hero['title'] ); ?></h2>
            <?php if ( ! empty( $hero['intro'] ) ) : ?>
                <p><?php echo esc_html( $hero['intro'] ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ( $shop_has_blocks ) : ?>

<div class="scouts-woocommerce scouts-woocommerce--landing cf">
    <?php echo apply_filters( 'the_content', $shop_post->post_content ); ?>
</div>

<?php elseif ( $is_product_cat_page ) : ?>

<div class="scouts-woocommerce scouts-woocommerce--category cf">
    <div class="wrapper">
        <div class="scouts-woocommerce__main">
            <?php woocommerce_content(); ?>
        </div>
    </div>

    <?php
    // Other categories strip — quick links to the other 3 categories
    if ( $cat_term && ! is_wp_error( $cat_term ) ) :
        $other_terms = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'exclude'    => array( $cat_term->term_id, get_option( 'default_product_cat' ) ),
        ) );
        if ( ! empty( $other_terms ) && ! is_wp_error( $other_terms ) ) :
    ?>
    <section class="shop_other_cats">
        <div class="wrapper">
            <h2 class="shop_other_cats__heading">Keep browsing</h2>
            <div class="shop_other_cats__tiles">
                <?php foreach ( $other_terms as $term ) :
                    $url = get_term_link( $term );
                    if ( is_wp_error( $url ) ) continue;
                ?>
                <a class="shop_other_cats__tile" href="<?php echo esc_url( $url ); ?>">
                    <span class="shop_other_cats__name"><?php echo esc_html( $term->name ); ?></span>
                    <span class="shop_other_cats__count"><?php echo (int) $term->count; ?> items</span>
                    <span class="shop_other_cats__arrow" aria-hidden="true">→</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; endif; ?>
</div>

<?php else : ?>

<div class="container scouts-woocommerce cf">
    <div class="wrapper shop-layout cf page_wrapper">
        <div class="shop-main main_content main_content_shop playground cf" id="scroll-access">
            <?php woocommerce_content(); ?>
        </div>

        <aside class="shop-sidebar sidebar cf">
            <div class="block join">
                <h3><?php echo esc_html( $support['title'] ); ?></h3>
                <p><?php echo esc_html( $support['content'] ); ?></p>
                <a href="<?php echo esc_url( $support['cta'] ); ?>" class="btn white"><?php echo esc_html( $support['label'] ); ?></a>
            </div>

            <div class="block green nav">
                <h4>Shop guidance</h4>
                <ul class="support-list">
                    <li>Use the official Scout Store for standard uniform unless a group-specific item is listed here.</li>
                    <li>Check event ticket details carefully before ordering so the right quantity is added to your basket.</li>
                    <li>Use the contact form if you need help with neckers, section-specific kit, or collection arrangements.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<?php endif; ?>

<?php get_footer(); ?>
