<?php
$features = get_field('features_section', 'option');
if (isset($features["background_image"]) && $features["background_image"]) : 
?>



<div class="features-section relative w-full bg-cover bg-center bg-no-repeat bg-fixed" style="background-image: url(<?php echo $features["background_image"]; ?>);">
    <div class="absolute w-full h-full top-0 left-0 bg-black opacity-50 z-0"></div>
    <div class="w-full mx-auto max-w-7xl px-4 py-10 lg:py-20 relative">
        <?php if ($features["title"]) { ?>
            <div class="flex justify-center mb-6 lg:mb-12">
                <h2 class="box-head mx-auto inline-block"><?php echo $features["title"]; ?></h2>
            </div>
        <?php } ?>
        <?php if ($features["sub_title"]) { ?>
            <h3 class="sm:text-center text-lg lg:text-2xl mb-4 lg:mb-10"><?php echo $features["sub_title"]; ?></h3>
        <?php } ?>
        <?php if ($features["text"]) { ?>
            <div class="flex justify-center"><?php echo $features["text"]; ?></div>
        <?php } ?>
    </div>
</div>
 <div class=""><?php echo $features["google_map"]; ?></div>

<?php endif; ?>