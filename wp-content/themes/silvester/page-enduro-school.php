<?php /* Template Name: Enduro School */ ?>

<?php get_header(); ?>

<div class="sub-page-default w-full mx-auto max-w-6xl px-4 xl:px-0 py-8">
   <?php echo the_content() ?>
</div>

<!-- Features Section -->
<?php include get_template_directory() . "/include/features-section.php"; ?>
<!-- Features Section -->

<?php get_footer(); ?>