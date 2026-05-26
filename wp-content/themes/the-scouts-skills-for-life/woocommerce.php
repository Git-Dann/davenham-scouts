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
?>

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

<div class="container scouts-woocommerce cf">
    <div class="wrapper shop-layout cf page_wrapper">
        <div class="shop-main main_content main_content_shop playground cf" id="scroll-access">
            <?php if ( function_exists( 'is_shop' ) && is_shop() ) : ?>
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

<?php get_footer(); ?>
