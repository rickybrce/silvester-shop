<?php

global 
$wpdb, 
$_SESSION,
$wf_table,
$wf_settings,
$plugin_admin_url,
$is_woo,
$wf_payment_types,
$wf_version;

//if the "tab" parameter is missing from the URL set it to SETTINGS tab. 
$active_tab = (isset($_GET['tab']) ? trim($_GET['tab']) : 'settings');

?>

<div class="wf-content">
    <div class="wf-heading">
        <img src="<?php echo plugin_dir_url( __FILE__ ).'wf-heading.png' ?>" />
    </div>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo $plugin_admin_url.'&tab=settings';?>" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><span class="dashicons dashicons-admin-tools"></span> Postavke</a>
        <a href="<?php echo $plugin_admin_url.'&tab=general';?>" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><span class="dashicons dashicons-editor-help"></span> Općenito</a>
    </h2>
    <!--TAB CONTENT-->
    <?php require('woo-fiskal-admin-'.$active_tab.'.php'); ?>

    
</div>
<span class="wf-version">WooFiskal v<?php echo $wf_version;?></span>