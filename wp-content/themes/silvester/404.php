<?php /* Template Name: 404 */ ?>

<?php get_header(); ?>
<div class="w-auto bg-cover bg-no-repeat bg-top-center h-[250px] lg:h-[400px] flex items-center justify-center">
    <h1 class="w-full lg:w-auto relative text-black uppercase text-6xl px-4 lg:px-20 py-10 text-center">
        <div class="relative z-10 box-head">Greška 404</div>
        <div class="absolute w-full h-full left-0 top-0 opacity-80 z-0"></div>
    </h1>
</div>

<div class="text-center text-lg">Stranica ne postoji</div> 


<div class="sub-page-default w-full mx-auto max-w-7xl px-4 xl:px-0 py-8 text-center my-4">
    <a class="red-button mb-4" href="<?php echo home_url(); ?>"><i class="fa-solid fa-home mr-2"></i> Povratak</a>
</div>

<?php get_footer(); ?>