<?php /* Template Name: Book Page */ ?>

<?php get_header(); ?>

<div class="sub-page-default w-full mx-auto max-w-6xl px-4 xl:px-0 py-8 default-form">
   <h1 class="page-title"><?php the_title(); ?></h1>
   <?php the_content(); ?>
   <div class="mt-8">
      <?php echo do_shortcode('[contact-form-7 id="147" title="Book form"]'); ?>
   </div>

</div>



<!-- Features Section -->
<?php include get_template_directory() . "/include/features-section.php"; ?>
<!-- Features Section -->

<?php get_footer(); ?>