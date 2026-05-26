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
?>

<?php if ( ! $shop_has_own_hero ) : ?>
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

<?php else : ?>

<div class="container scouts-woocommerce cf">
    <div class="wrapper shop-layout cf page_wrapper">
        <div class="shop-main main_content main_content_shop playground cf" id="scroll-access">
            <?php if ( $is_shop_landing ) : ?>
                <div class="shop-intro-card">
                    <p>The shop is the home for tickets, fundraising items, and any group-specific resources we sell directly. For standard Scout uniform, we usually recommend the official Scout Store.</p>
                    <p>If you cannot find what you need here yet, use the contact form and we will point you in the right direction.</p>
                </div>
            <?php endif; ?>

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
