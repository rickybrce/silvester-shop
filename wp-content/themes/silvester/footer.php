<div class="w-full mt-0 border-t border-black pt-10 pb-6 bg-black text-gray-300">
    <div class="w-full max-w-7xl mx-auto flex flex-wrap gap-y-8 px-5 lg:px-4">

        <!-- Menu 1 -->
        <div class="w-1/2 lg:w-1/5">
            <?php $footer_menu_1_title = get_field('footer_menu_1_title', 'option'); ?>
            <?php if ($footer_menu_1_title) : ?>
                <h4 class="text-white font-semibold text-base uppercase tracking-widest mb-4"><?php echo esc_html($footer_menu_1_title); ?></h4>
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
                <h4 class="text-white font-semibold text-base uppercase tracking-widest mb-4"><?php echo esc_html($footer_menu_2_title); ?></h4>
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
                <h4 class="text-white font-semibold text-base uppercase tracking-widest mb-4"><?php echo esc_html($footer_legal_menu_title); ?></h4>
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
                <h4 class="text-white font-semibold text-base uppercase tracking-widest mb-4"><?php echo esc_html($footer_contact_title); ?></h4>
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
            <a href="<?php echo home_url(); ?>"><img class="w-20 mb-4 lg:w-36" src="<?php bloginfo('template_directory'); ?>/images/logo.jpeg" width="168" height="205" /></a>
            <div class="text-center lg:text-right text-sm"><?php the_field('footer_text', 'option'); ?></div>
        </div>

    </div>
</div>


<div class="w-full py-10 bg-[rgba(0,0,0,0.85)] text-gray-400"> 
    <div class="w-full max-w-7xl mx-auto text-xs text-center">
    Copyright <?php echo date("Y"); ?> S.P. MOTO | Design by <a target="_blank" href="https://sapa-design.com" class="text-gray-300 hover:text-enduro-red-100">SAPA DESIGN</a>
    </div>
</div>

<?php wp_footer(); ?>

<!-- GDPR Cookie Consent -->
<div id="gdpr-banner" style="display:none" role="dialog" aria-label="Postavke kolačića">
  <style>
    #gdpr-banner {
      position: fixed; bottom: 0; left: 0; right: 0; z-index: 9999;
      background: rgba(17,17,17,0.97); color: #d1d5db;
      font-family: 'Roboto', sans-serif; font-size: 14px; line-height: 1.6;
      box-shadow: 0 -4px 24px rgba(0,0,0,0.4);
    }
    .gdpr-inner {
      max-width: 1280px; margin: 0 auto; padding: 20px 24px;
    }
    .gdpr-top { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; justify-content: space-between; }
    .gdpr-text { flex: 1; min-width: 240px; }
    .gdpr-text strong { color: #fff; font-size: 15px; display: block; margin-bottom: 4px; }
    .gdpr-text a { color: #de423d; text-decoration: underline; }
    .gdpr-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; flex-shrink: 0; }
    .gdpr-btn {
      padding: 8px 18px; border-radius: 4px; border: none; font-size: 14px;
      font-family: inherit; cursor: pointer; white-space: nowrap; font-weight: 500;
    }
    .gdpr-btn-all {
      background: linear-gradient(to bottom, #de423d, #8d1f1a); color: #fff;
    }
    .gdpr-btn-all:hover { background: linear-gradient(to bottom, #8d1f1a, #de423d); }
    .gdpr-btn-selected { background: transparent; color: #d1d5db; border: 1px solid #4b5563; }
    .gdpr-btn-selected:hover { border-color: #de423d; color: #fff; }
    .gdpr-btn-settings { background: transparent; color: #d1d5db; border: 1px solid #4b5563; }
    .gdpr-btn-settings:hover { border-color: #de423d; color: #fff; }
    .gdpr-categories {
      display: none; border-top: 1px solid #374151; margin-top: 16px; padding-top: 16px;
      display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;
    }
    .gdpr-categories.hidden { display: none !important; }
    .gdpr-cat {
      background: #1f2937; border-radius: 6px; padding: 12px 14px;
      display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;
    }
    .gdpr-cat-info strong { color: #fff; font-size: 13px; display: block; margin-bottom: 2px; }
    .gdpr-cat-info span { font-size: 12px; color: #9ca3af; }
    /* Toggle switch */
    .gdpr-toggle { position: relative; flex-shrink: 0; width: 40px; height: 22px; margin-top: 2px; }
    .gdpr-toggle input { opacity: 0; width: 0; height: 0; }
    .gdpr-toggle-slider {
      position: absolute; inset: 0; border-radius: 22px;
      background: #4b5563; cursor: pointer; transition: background .2s;
    }
    .gdpr-toggle-slider::before {
      content: ''; position: absolute; width: 16px; height: 16px; border-radius: 50%;
      background: #fff; left: 3px; top: 3px; transition: transform .2s;
    }
    .gdpr-toggle input:checked + .gdpr-toggle-slider { background: #de423d; }
    .gdpr-toggle input:checked + .gdpr-toggle-slider::before { transform: translateX(18px); }
    .gdpr-toggle input:disabled + .gdpr-toggle-slider { background: #de423d; cursor: not-allowed; opacity: 0.7; }
    @media (max-width: 640px) {
      .gdpr-actions { width: 100%; }
      .gdpr-btn { flex: 1; text-align: center; }
    }
  </style>

  <div class="gdpr-inner">
    <div class="gdpr-top">
      <div class="gdpr-text">
        <strong>Koristimo kolačiće</strong>
        Koristimo kolačiće kako bismo poboljšali vaše iskustvo na stranici. Možete prihvatiti sve ili prilagoditi postavke.
        <a href="<?php echo esc_url( get_permalink( get_page_by_path('kolacici') ) ); ?>">Politika kolačića</a>
      </div>
      <div class="gdpr-actions">
        <button class="gdpr-btn gdpr-btn-settings" id="gdpr-toggle-settings">Postavke</button>
        <button class="gdpr-btn gdpr-btn-selected" id="gdpr-accept-selected" style="display:none">Prihvati odabrano</button>
        <button class="gdpr-btn gdpr-btn-all" id="gdpr-accept-all">Prihvati sve</button>
      </div>
    </div>

    <div class="gdpr-categories hidden" id="gdpr-categories">
      <div class="gdpr-cat">
        <div class="gdpr-cat-info">
          <strong>Nužni</strong>
          <span>Neophodni za rad stranice</span>
        </div>
        <label class="gdpr-toggle">
          <input type="checkbox" checked disabled data-cat="necessary">
          <span class="gdpr-toggle-slider"></span>
        </label>
      </div>
      <div class="gdpr-cat">
        <div class="gdpr-cat-info">
          <strong>Analitički</strong>
          <span>Statistika posjeta (Google Analytics)</span>
        </div>
        <label class="gdpr-toggle">
          <input type="checkbox" data-cat="analytics">
          <span class="gdpr-toggle-slider"></span>
        </label>
      </div>
      <div class="gdpr-cat">
        <div class="gdpr-cat-info">
          <strong>Marketinški</strong>
          <span>Personalizirani oglasi</span>
        </div>
        <label class="gdpr-toggle">
          <input type="checkbox" data-cat="marketing">
          <span class="gdpr-toggle-slider"></span>
        </label>
      </div>
      <div class="gdpr-cat">
        <div class="gdpr-cat-info">
          <strong>Funkcionalni</strong>
          <span>Pamćenje postavki i košarice</span>
        </div>
        <label class="gdpr-toggle">
          <input type="checkbox" checked data-cat="functional">
          <span class="gdpr-toggle-slider"></span>
        </label>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var CONSENT_KEY = 'silvester_gdpr_consent';
  var banner      = document.getElementById('gdpr-banner');
  var categories  = document.getElementById('gdpr-categories');

  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  // Show banner if no consent cookie
  if ( ! getCookie(CONSENT_KEY) ) {
    banner.style.display = 'block';
  }

  function saveConsent(data) {
    var expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1); // 1 year
    document.cookie = CONSENT_KEY + '=' + encodeURIComponent(JSON.stringify({ ts: Date.now(), cats: data }))
      + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
    banner.style.display = 'none';
  }

  function getSelected() {
    var cats = { necessary: true };
    banner.querySelectorAll('[data-cat]:not([disabled])').forEach(function(cb) {
      cats[cb.dataset.cat] = cb.checked;
    });
    return cats;
  }

  document.getElementById('gdpr-accept-all').addEventListener('click', function() {
    saveConsent({ necessary: true, analytics: true, marketing: true, functional: true });
  });

  document.getElementById('gdpr-accept-selected').addEventListener('click', function() {
    saveConsent(getSelected());
  });

  var settingsBtn  = document.getElementById('gdpr-toggle-settings');
  var selectedBtn  = document.getElementById('gdpr-accept-selected');

  settingsBtn.addEventListener('click', function() {
    var isOpen = !categories.classList.toggle('hidden');
    settingsBtn.style.display  = isOpen ? 'none'  : '';
    selectedBtn.style.display  = isOpen ? ''      : 'none';
  });
})();
</script>
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