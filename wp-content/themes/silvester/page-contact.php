<?php /* Template Name: Contact Page */ ?>

<?php get_header(); ?>

<div class="w-full mx-auto max-w-6xl px-4 py-8">
    <div class="lg:flex lg:-mx-4">
        <div class="default-form w-full lg:8/12 lg:px-4">
            <?php echo do_shortcode('[contact-form-7 id="119" title="Contact form"]'); ?>
        </div>
        <div class="w-full lg:w-4/12 lg:px-4 lg:px-4">
        <?php
            wp_nav_menu(array(
                'theme_location' => 'footer-menu',
                'container_class' => 'footer-menu-cont footer-menu-contact w-full text-sm py-2 w-full lg:flex lg:relative left-0 top-0 mt-[60px] lg:mt-0 px-0 z-10 lg:items-center hidden lg:block'
            ));
            ?>
        </div>
    </div>
</div>

<div class=""><?php echo the_content() ?></div>

<!-- Features Section -->
<?//php include get_template_directory() . "/include/features-section.php"; ?>
<!-- Features Section -->

<?php get_footer(); ?>