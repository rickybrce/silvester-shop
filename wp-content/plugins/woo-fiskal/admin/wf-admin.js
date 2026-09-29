/* THIS IS YOUR PLUGIN ADMIN JS FILE
* 
* IT'S LOADED ONCE YOU OPEN YOUR PLUGIN ADMIN PAGE
*/

//WAIT UNTIL ALL ASSETS ARE LOADED!
jQuery(function($) {

    console.log('PLUGIN ADMIN JS FILE LOADED!');

    //save admin settings
    jQuery('#wf-save-settings').on('click', function(){

        //get values
        var wf_api_link = jQuery('#wf-api-link').val();
        var wf_auto_fiskal = parseInt( jQuery('#wf-auto-fiskal').is(":checked") ? 1 : 0 );
        var wf_auto_fiskal_processing = parseInt( jQuery('#wf-auto-fiskal-processing').is(":checked") ? 1 : 0 );
        var wf_send_mail = parseInt (jQuery('#wf-send-mail').is(":checked") ? 1 : 0);
        var wf_r1 = parseInt (jQuery('#wf-r1').is(":checked") ? 1 : 0);
        
        //labels and placeholders
        var wf_r1_label = jQuery('#wf-r1-label').val();
        var wf_company_name_label = jQuery('#wf-company-name-label').val();
        var wf_company_name_placeholder = jQuery('#wf-company-name-placeholder').val();
        var wf_company_oib_label = jQuery('#wf-company-oib-label').val();
        var wf_company_oib_placeholder = jQuery('#wf-company-oib-placeholder').val();

        //handle empy WF API LINK VALUE
        if(!wf_api_link.length){ jQuery('input#wf-api-link').addClass('input-error');  alert('Unesite API LINK!'); }

        else{
            //swap button icon (display visual action to user)
            jQuery('#wf-save-settings span').removeClass('dashicons-lock').addClass('dashicons-update spin');

            //start ajax request
            jQuery.ajax({
                type: 'POST', //use POST to send action
                dataType: 'json',
                url: ajax_admin_data.ajax_url,
                data: {
                    action: 'wf_admin_settings_save', //name of the function inside the main plugin file that is triggered with our AJAX call,
                    security_nonce: ajax_admin_data.admin_nonce,
                    wf_api_link: wf_api_link,
                    wf_auto_fiskal: wf_auto_fiskal,
                    wf_auto_fiskal_processing: wf_auto_fiskal_processing,
                    wf_send_mail: wf_send_mail,
                    wf_r1: wf_r1,
                    wf_r1_label: wf_r1_label,
                    wf_company_name_label: wf_company_name_label,
                    wf_company_name_placeholder: wf_company_name_placeholder,
                    wf_company_oib_label: wf_company_oib_label,
                    wf_company_oib_placeholder: wf_company_oib_placeholder
                },
                success: function(data){
                    //remove error after correct input
                    jQuery('input#wf-api-link').removeClass('input-error');
                    //swap button icon (display visual action to user)
                    jQuery('#wf-save-settings span').removeClass('dashicons-update spin').addClass('dashicons-yes');
                    setTimeout( () => { jQuery('#wf-save-settings span').removeClass('dashicons-yes').addClass('dashicons-lock') }, 1400 );
                    console.log(data);
                },
                error: function(error){
                    console.log(error);
                    alert(error.responseText);
                }
            }); 
        }   
    });

    //SHOW - HIDE options for R1
    jQuery('input#wf-r1').change(function(){
           
        jQuery(this).is(":checked") ? jQuery('#wf-r1-fields').show() : jQuery('#wf-r1-fields').hide(); 
          
     }).change();
 
});