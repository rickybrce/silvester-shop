<div class="w-full mt-0 border-t border-gray-200 pt-10 bg-white lg:px-4 text-enduro-grey-800">
    <div class="w-full max-w-6xl mx-auto flex flex-wrap">
        <div class="w-full sm:w-4/12 mb-2">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'footer-menu',
                'container_class' => 'footer-menu-cont-2 w-full text-sm py-2 w-full lg:flex lg:relative left-0 top-0 mt-0 px-[20px] lg:px-0 z-10 lg:items-center'
            ));
            ?>
        </div>
        <div class="w-full sm:w-4/12 mb-2">
        <?php
            wp_nav_menu(array(
                'theme_location' => 'main-menu',
                'container_class' => 'footer-menu-cont-2 w-full text-sm py-2 w-full lg:flex lg:relative left-0 top-0 mt-0 px-[20px] lg:px-0 z-10 lg:items-center'
            ));
            ?>
        </div>
        <div class="w-full sm:w-4/12 mb-2 text-right">
        <div class="flex justify-items-end flex-wrap"><a class="w-full flex justify-items-center" href="<?php echo home_url(); ?>"><img class="w-[80px] mx-auto mb-4" src="<?php bloginfo('template_directory'); ?>/images/logo.png" width="168" height="205" /></a>
        <div class="w-full text-center"><?php the_field('footer_text', 'option'); ?></a>
    </div>
        </div>
    </div>
</div>

<div class="w-full border-t border-gray-200 py-10 bg-gray-50 text-enduro-grey-700">
    <div class="w-full max-w-6xl mx-auto text-xs text-center">
    Copyright <?php echo date("Y"); ?> Croatia Enduro | Design by <a target="_blank" href="https://sapa-design.com" class="text-enduro-grey-800 hover:text-enduro-red-100">SAPA DESIGN</a>
    </div>
</div>

<?php wp_footer(); ?>
<script>
    $("a[data-imagelightbox]").imageLightbox({
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
</script>
</body>

</html>