<div class="w-full mt-0 border-t border-black pt-10 pb-6 bg-[rgba(0,0,0,0.85)] text-gray-300">
    <div class="w-full max-w-7xl mx-auto flex flex-wrap gap-y-8 px-5 lg:px-4">

        <!-- Menu 1 -->
        <div class="w-1/2 lg:w-1/5">
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
        <div class="w-1/2 lg:w-1/5">
            <?php $footer_menu_2_title = get_field('footer_menu_2_title', 'option'); ?>
            <?php if ($footer_menu_2_title) : ?>
                <h4 class="text-white font-semibold text-sm uppercase tracking-widest mb-4"><?php echo esc_html($footer_menu_2_title); ?></h4>
            <?php endif; ?>
            <?php wp_nav_menu([
                'theme_location' => 'main-menu',
                'container_class' => 'footer-menu-cont-2 w-full text-sm',
            ]); ?>
        </div>

        <!-- Legal menu -->
        <div class="w-1/2 lg:w-1/5">
            <?php $footer_legal_menu_title = get_field('footer_legal_menu_title', 'option'); ?>
            <?php if ($footer_legal_menu_title) : ?>
                <h4 class="text-white font-semibold text-sm uppercase tracking-widest mb-4"><?php echo esc_html($footer_legal_menu_title); ?></h4>
            <?php endif; ?>
            <?php wp_nav_menu([
                'theme_location' => 'legal-menu',
                'container_class' => 'footer-menu-cont-2 w-full text-sm',
                'depth'          => 1,
                'fallback_cb'    => false,
            ]); ?>
        </div>

        <!-- Contact -->
        <div class="w-1/2 lg:w-1/5">
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
        <div class="w-full sm:w-1/2 lg:w-1/5 flex flex-col items-center lg:items-end">
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
    var header   = document.getElementById('site-header');
    var lastY    = 0;
    var ticking  = false;

    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                var y = window.scrollY;
                if (y > 100) {
                    if (y > lastY) {
                        // scrolling down — hide
                        header.style.transform = 'translateY(-100%)';
                    } else {
                        // scrolling up — show
                        header.style.transform = 'translateY(0)';
                    }
                } else {
                    header.style.transform = 'translateY(0)';
                }
                lastY   = y;
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
})();
</script>
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
<!-- Cart popup -->
<div id="cart-popup-overlay" class="fixed inset-0 bg-black/60 z-[200] hidden"></div>
<div id="cart-popup" class="fixed top-0 right-0 h-full w-full max-w-sm bg-white z-[201] shadow-2xl translate-x-full transition-transform duration-300" style="display:flex;flex-direction:column;">

    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-enduro-grey-900">Košarica</h2>
        <button id="cart-popup-close" class="text-gray-400 hover:text-enduro-grey-900 text-2xl leading-none">&times;</button>
    </div>

    <div id="cart-popup-items" class="flex-1 overflow-y-auto px-5 py-4 text-sm text-enduro-grey-800">
        <p class="text-center text-gray-400 py-8">Košarica je prazna.</p>
    </div>

    <div class="px-5 py-4 border-t border-gray-200 space-y-2">
        <div id="cart-popup-subtotal" class="flex justify-between font-semibold text-base mb-3 hidden">
            <span>Ukupno:</span>
            <span id="cart-popup-subtotal-value" class="text-enduro-red-100"></span>
        </div>
        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="block text-center w-full border border-enduro-red-100 text-enduro-red-100 hover:bg-enduro-red-100 hover:text-white py-2.5 rounded text-sm font-medium transition">Idi na košaricu</a>
        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="block text-center w-full bg-gradient-to-b from-enduro-red-100 to-enduro-red-200 hover:from-enduro-red-200 hover:to-enduro-red-100 text-white py-2.5 rounded text-sm font-medium transition">Prijeđi na blagajnu</a>
    </div>
</div>

<script>
(function() {
    var overlay = document.getElementById('cart-popup-overlay');
    var popup   = document.getElementById('cart-popup');
    var closeBtn = document.getElementById('cart-popup-close');

    function openPopup() {
        overlay.classList.remove('hidden');
        requestAnimationFrame(function() {
            popup.classList.remove('translate-x-full');
        });
        document.body.style.overflow = 'hidden';
    }
    function closePopup() {
        popup.classList.add('translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closePopup);
    overlay.addEventListener('click', closePopup);

    function renderCart(data) {
        var items   = document.getElementById('cart-popup-items');
        var subtotalWrap = document.getElementById('cart-popup-subtotal');
        var subtotalVal  = document.getElementById('cart-popup-subtotal-value');

        if (!data || !data.items || data.items.length === 0) {
            items.innerHTML = '<p class="text-center text-gray-400 py-8">Košarica je prazna.</p>';
            subtotalWrap.classList.add('hidden');
            return;
        }

        var html = '<ul class="space-y-4">';
        data.items.forEach(function(item) {
            html += '<li class="flex gap-3 items-start">';
            if (item.thumbnail) {
                html += '<img src="' + item.thumbnail + '" class="w-16 h-16 object-contain border border-gray-100 rounded flex-shrink-0">';
            }
            html += '<div class="flex-1 min-w-0">';
            html += '<p class="font-medium text-enduro-grey-900 leading-tight">' + item.name + '</p>';
            html += '<p class="text-gray-400 text-xs mt-0.5">' + item.quantity + ' &times; ' + item.price + '</p>';
            html += '</div></li>';
        });
        html += '</ul>';
        items.innerHTML = html;
        subtotalVal.innerHTML = data.subtotal;
        subtotalWrap.classList.remove('hidden');
    }

    function fetchCart() {
        fetch('<?php echo esc_url( admin_url('admin-ajax.php') ); ?>?action=silvester_cart_popup', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            renderCart(data);
            openPopup();
        });
    }

    // Disable WooCommerce cart redirect + suppress "Vidi košaricu" link
    if (typeof wc_add_to_cart_params !== 'undefined') {
        wc_add_to_cart_params.cart_redirect_after_add = 'no';
        wc_add_to_cart_params.i18n_view_cart = '';
    }

    // Block any .wc-forward link from navigating
    jQuery(document).on('click', '.wc-forward', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
    });

    // WooCommerce fires this on AJAX add-to-cart
    jQuery(document.body).on('added_to_cart', function(e, fragments, hash, $btn) {
        if ($btn) $btn.removeClass('wc-forward');
        // WooCommerce injects "Vidi košaricu" link AFTER this event — remove in next tick
        setTimeout(function() {
            jQuery('.added_to_cart.wc-forward').remove();
        }, 0);
        fetchCart();
    });

    // Also handle non-AJAX (page reload) — check for ?add-to-cart in URL on single product
    if (window.location.search.indexOf('added-to-cart') !== -1 || document.querySelector('.woocommerce-message')) {
        fetchCart();
    }
})();
</script>
</body>

</html>