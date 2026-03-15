<div class="w-full mt-0 border-t border-black pt-10 pb-6 bg-[rgba(0,0,0,0.85)] text-gray-300">
    <div class="w-full max-w-7xl mx-auto flex flex-wrap gap-y-8 px-5 lg:px-4">

        <!-- Menu 1 -->
        <div class="w-1/2 lg:w-3/12">
            <?php $footer_menu_1_title = get_field('footer_menu_1_title', 'option'); ?>
            <?php if ($footer_menu_1_title) : ?>
                <h4 class="text-white font-semibold text-sm uppercase tracking-widest mb-4"><?php echo esc_html($footer_menu_1_title); ?></h4>
            <?php endif; ?>
            <?php wp_nav_menu([
                'theme_location' => 'footer-menu',
                'container_class' => 'footer-menu-cont-2 w-full text-sm',
            ]); ?>
        </div>

        <!-- Menu 2 -->
        <div class="w-1/2 lg:w-3/12">
            <?php $footer_menu_2_title = get_field('footer_menu_2_title', 'option'); ?>
            <?php if ($footer_menu_2_title) : ?>
                <h4 class="text-white font-semibold text-sm uppercase tracking-widest mb-4"><?php echo esc_html($footer_menu_2_title); ?></h4>
            <?php endif; ?>
            <?php wp_nav_menu([
                'theme_location' => 'main-menu',
                'container_class' => 'footer-menu-cont-2 w-full text-sm',
            ]); ?>
        </div>

        <!-- Contact -->
        <div class="w-full sm:w-1/2 lg:w-3/12">
            <?php $footer_contact_title = get_field('footer_contact_title', 'option'); ?>
            <?php if ($footer_contact_title) : ?>
                <h4 class="text-white font-semibold text-sm uppercase tracking-widest mb-4"><?php echo esc_html($footer_contact_title); ?></h4>
            <?php endif; ?>
            <ul class="text-sm space-y-3">
                <?php $footer_address = get_field('footer_address', 'option'); ?>
                <?php if ($footer_address) : ?>
                    <li class="flex gap-2">
                        <i class="fa-solid fa-location-dot mt-1 text-enduro-red-100 shrink-0"></i>
                        <span><?php echo nl2br(esc_html($footer_address)); ?></span>
                    </li>
                <?php endif; ?>
                <?php $footer_email = get_field('footer_email', 'option'); ?>
                <?php if ($footer_email) : ?>
                    <li class="flex gap-2 items-center">
                        <i class="fa-solid fa-envelope text-enduro-red-100 shrink-0"></i>
                        <a href="mailto:<?php echo esc_attr($footer_email); ?>" class="hover:text-white transition"><?php echo esc_html($footer_email); ?></a>
                    </li>
                <?php endif; ?>
                <?php $footer_phone = get_field('footer_phone', 'option'); ?>
                <?php if ($footer_phone) : ?>
                    <li class="flex gap-2 items-center">
                        <i class="fa-solid fa-phone text-enduro-red-100 shrink-0"></i>
                        <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $footer_phone)); ?>" class="hover:text-white transition"><?php echo esc_html($footer_phone); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Logo + text -->
        <div class="w-full sm:w-1/2 lg:w-3/12 flex flex-col items-center lg:items-end">
            <a href="<?php echo home_url(); ?>"><img class="w-20 mb-4 lg:w-36" src="<?php bloginfo('template_directory'); ?>/images/logo.png" width="168" height="205" /></a>
            <div class="text-center lg:text-right text-sm"><?php the_field('footer_text', 'option'); ?></div>
        </div>

    </div>
</div>

<div class="w-full py-10 bg-black text-gray-400">
    <div class="w-full max-w-7xl mx-auto text-xs text-center">
    Copyright <?php echo date("Y"); ?> Croatia Enduro | Design by <a target="_blank" href="https://sapa-design.com" class="text-gray-300 hover:text-enduro-red-100">SAPA DESIGN</a>
    </div>
</div>

<?php wp_footer(); ?>
<script>
(function() {
    var panel = document.getElementById('mobile-menu-panel');
    var toggle = document.querySelector('.mobile-menu-toggle');
    var closeBtn = document.querySelector('.mobile-menu-close');
    function openMenu() {
        if (panel) { panel.classList.add('is-open'); panel.setAttribute('aria-hidden', 'false'); }
        if (toggle) { toggle.setAttribute('aria-expanded', 'true'); }
        document.body.style.overflow = 'hidden';
    }
    function closeMenu() {
        if (panel) { panel.classList.remove('is-open'); panel.setAttribute('aria-hidden', 'true'); }
        if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
        document.body.style.overflow = '';
    }
    if (toggle) toggle.addEventListener('click', function() { panel && panel.classList.contains('is-open') ? closeMenu() : openMenu(); });
    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    if (panel) {
        panel.addEventListener('click', function(e) { if (e.target === panel) closeMenu(); });
        panel.querySelectorAll('#menu-main-menu a').forEach(function(a) { a.addEventListener('click', closeMenu); });
    }
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && panel && panel.classList.contains('is-open')) closeMenu(); });
})();
</script>
<script>
    jQuery(document).ready(function($) {
    $("a[data-imagelightbox]:not([data-imagelightbox='product'])").imageLightbox({
        selector: 'a[data-imagelightbox]', // string;
        id: 'imagelightbox', // string;
        allowedTypes: 'png|jpg|jpeg|gif', // string;          use empty string to allow any file type
        animationSpeed: 250, // integer;
        activity: false, // bool;            show activity indicator
        arrows: true, // bool;            show left/right arrows
        button: true, // bool;            show close button
        caption: false, // bool;            show captions
        enableKeyboard: true, // bool;            enable keyboard shortcuts (arrows Left/Right and Esc)
        history: false, // bool;            enable image permalinks and history
        fullscreen: true, // bool;            enable fullscreen (enter/return key)
        gutter: 10, // integer;         window height less height of image as a percentage
        offsetY: 0, // integer;         vertical offset in terms of gutter
        navigation: true, // bool;            show navigation
        overlay: true, // bool;            display the lightbox as an overlay
        preloadNext: true, // bool;            silently preload the next image
        quitOnEnd: false, // bool;            quit after viewing the last image
        quitOnImgClick: false, // bool;            quit when the viewed image is clicked
        quitOnDocClick: true, // bool;            quit when anything but the viewed image is clicked
        quitOnEscKey: true // bool;            quit when Esc key is pressed
    });
    });
</script>
</body>

</html>