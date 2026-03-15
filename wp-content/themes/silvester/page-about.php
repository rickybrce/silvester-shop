<?php /* Template Name: About Page */ ?>

<?php get_header(); ?>

<div class="sub-page-default w-full mx-auto max-w-7xl px-4 xl:px-0 py-8">
   <h1 class="page-title"><?php the_title(); ?></h1>
   <?php the_content(); ?>
</div>

<!-- Features Section -->
<?php include get_template_directory() . "/include/features-section.php"; ?>
<!-- Features Section -->

<?php get_footer(); ?>