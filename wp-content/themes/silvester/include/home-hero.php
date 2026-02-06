<?php
/**
 * Home Hero – full-width hero block.
 * ACF group: home_page_hero
 * Fields: image (array), title (text), description (text), cta (link array)
 */
$hero = get_field('home_page_hero');

if (!$hero || (!isset($hero['image']) && empty($hero['title']) && empty($hero['description']))) {
    return;
}

$bg_image_url = '';
if (!empty($hero['image'])) {
    $img = $hero['image'];
    $bg_image_url = is_array($img) ? ($img['url'] ?? '') : wp_get_attachment_image_url($img, 'full');
}

$cta = $hero['cta'] ?? null;
$cta_url = is_array($cta) ? ($cta['url'] ?? '') : '';
$cta_title = is_array($cta) ? ($cta['title'] ?? '') : '';
$cta_target = is_array($cta) && !empty($cta['target']) ? $cta['target'] : '_self';
?>

<section class="home-hero relative w-full min-h-[50vh] lg:min-h-[60vh] flex items-center justify-center bg-enduro-grey-900">
    <?php if ($bg_image_url) : ?>
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat z-0" style="background-image: url(<?php echo esc_url($bg_image_url); ?>);"></div>
        <div class="absolute inset-0 bg-black/20 z-[1]"></div>
    <?php endif; ?>

    <div class="relative z-10 w-full max-w-4xl mx-auto px-4 py-24 lg:py-32 text-center">
        <?php if (!empty($hero['title'])) : ?>
            <h1 class="home-hero__title text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-medium text-white uppercase mb-4 lg:mb-6">
                <?php echo esc_html($hero['title']); ?>
            </h1>
        <?php endif; ?>

        <?php if (!empty($hero['description'])) : ?>
            <div class="home-hero__description text-lg sm:text-xl lg:text-2xl text-white/90 max-w-2xl mx-auto mb-8 lg:mb-10">
                <?php echo wp_kses_post(wpautop($hero['description'])); ?>
            </div>
        <?php endif; ?>

        <?php if ($cta_url && $cta_title) : ?>
            <a href="<?php echo esc_url($cta_url); ?>" target="<?php echo esc_attr($cta_target); ?>" class="home-hero__cta inline-block bg-gradient-to-b from-enduro-red-100 to-enduro-red-200 hover:from-enduro-red-200 hover:to-enduro-red-100 text-white font-medium uppercase py-3 px-8 rounded text-base lg:text-lg transition ease-in-out">
                <?php echo esc_html($cta_title); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
