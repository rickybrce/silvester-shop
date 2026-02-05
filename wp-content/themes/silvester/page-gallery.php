<?php /* Template Name: Gallery Page */ ?>

<?php get_header(); ?>

<div class="sub-page-default w-full mx-auto max-w-6xl px-4 xl:px-0 py-8">
   <?php echo the_content() ?>
</div>


<div class="sub-page-default w-full px-4 py-8">
   <?php $gallery = get_field('main_gallery');
   if ($gallery) : ?>
      <div class="w-full flex flex-wrap">
         <?php
         // Loop through rows.
         foreach ($gallery as $cat) : ?>
            <div class="w-full">
               <?php if ($cat['title']) { ?>
                  <div class="mb-4 box-head-gallery"><?php echo $cat['title']; ?></div>
               <?php } ?>

               <?php if ($cat['category']) { ?>
                  <div class="w-full flex flex-wrap mb-8">
                     <?php foreach ($cat['category'] as $item) : ?>

                        <?php if ($item['video']) { ?>
                           <a data-ilb2-video='{"controls":"controls", "autoplay":"autoplay", "sources":[{"src":"<?php echo $item['video']; ?>", "type":"video/mp4"}], "width": 1920, "height": 1080}' data-imagelightbox="x" class="relative w-6/12 xs:w-4/12 lg:w-2/12 2xl:w-[12.5%] h-[200px] bg-cover bg-no-repeat bg-top-center" style="background-image: url(<?php echo $item['image']; ?>)">
                              <span class="cursor-pointer hover:scale-150 transition ease-in-out block absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                 <i class="fa-solid fa-play text-enduro-red-100 text-2xl"></i>
                              </span>
                           </a>
                        <?php } else { ?>
                           <a data-imagelightbox="x" data-ilb2-caption="caption text" href="<?php echo $item['image']; ?>" class="relative w-6/12 xs:w-4/12 lg:w-2/12 2xl:w-[12.5%] h-[200px] bg-cover bg-no-repeat bg-top-center" style="background-image: url(<?php echo $item['image']; ?>)">
                              <span class="cursor-pointer hover:scale-150 transition ease-in-out block absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                 <i class="fa-solid fa-image text-enduro-white text-2xl"></i>
                              </span>
                           </a>
                        <?php } ?>

                     <?php endforeach; ?>
                  </div>
               <?php } ?>
            </div>
         <?php endforeach; ?>

      </div>
   <?php endif; ?>
</div>


<!-- Features Section -->
<?php include get_template_directory() . "/include/features-section.php"; ?>
<!-- Features Section -->

<?php get_footer(); ?>