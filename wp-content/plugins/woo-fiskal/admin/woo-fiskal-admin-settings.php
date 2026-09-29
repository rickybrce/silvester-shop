<br/>
<?php echo $is_woo ? '': '<span class="no-woo-error">WooCommerce nije instaliran i/ili aktiviran!</span>'; ?>

<label>WebRačun API link:</label>
<input type="text" id="wf-api-link" name="wf-api-link" value="<?php echo $wf_settings[0]->wf_api_link; ?>">

<br/>
<br/>

<span class="checkbox-label">Automatski fiskalizraj narudžbu sa statusom "Završeno"</span>
<input type="checkbox" id="wf-auto-fiskal" name="wf-auto-fiskal" value="<?php echo $wf_settings[0]->wf_auto_fiskal; echo (intval($wf_settings[0]->wf_auto_fiskal) ? '" checked="checked' : '');?>">


<br/>
<br/>

<span class="checkbox-label">Automatski fiskalizraj narudžbu sa statusom "U obradi"</span>
<input type="checkbox" id="wf-auto-fiskal-processing" name="wf-auto-fiskal-processing" value="<?php echo $wf_settings[0]->wf_auto_fiskal_processing; echo (intval($wf_settings[0]->wf_auto_fiskal_processing) ? '" checked="checked' : '');?>">


<br/>
<br/>
<br/>

<span class="checkbox-label">Omogući R1 račune za tvrtke</span>
<input type="checkbox" id="wf-r1" name="wf-r1" value="<?php echo $wf_settings[0]->wf_r1; echo (intval($wf_settings[0]->wf_r1) ? '" checked="checked' : '');?>">

<br/>
<br/>

<div id="wf-r1-fields">
<label>Polje za omogućavanje R1 računa:</label>
<span>Tekst: </span><input type="text" id="wf-r1-label" name="wf-r1-label" value="<?php echo $wf_settings[0]->wf_r1_label; ?>">
<br/>
<br/>
<br/>

<label>Polje za popunjavanje naziva tvrtke:</label>
<span>Tekst: </span><input type="text" id="wf-company-name-label" name="wf-company-name-label" value="<?php echo $wf_settings[0]->wf_company_name_label; ?>">
<br/>
<span>Opis:&nbsp; </span><input type="text" id="wf-company-name-placeholder" name="wf-company-name-placeholder" value="<?php echo $wf_settings[0]->wf_company_name_placeholder; ?>">
<br/>
<br/>
<br/>

<label>Polje za popunjavanje OIB-a tvrtke:</label>
<span>Tekst: </span><input type="text" id="wf-company-oib-label" name="wf-company-oib-label" value="<?php echo $wf_settings[0]->wf_company_oib_label; ?>">
<br/>
<span>Opis:&nbsp; </span><input type="text" id="wf-company-oib-placeholder" name="wf-company-oib-placeholder" value="<?php echo $wf_settings[0]->wf_company_oib_placeholder; ?>">
<br/>
<br/>
<br/>
</div>

<label>Nemate WebRačun API link?</label>
<a href="mailto:info@webracun.com?subject=WebRačun API link" class="wf-button"> Zatraži link </a>

<br/>
<br/>
<br/>

<label><span class="dashicons dashicons-info"></span> WooCommerce kuponi i kodovi za popuste</label>
<br/>
WooFiskal podržava korištenje samo jednog kupona ili koda za popust na istoj narudžbi. Kombinacije više kupona i kodova za popuste na istoj narudžbi nisu podržane! 
<br/>
<hr class="wf-line" />
<br/>
<br/>
<button id="wf-save-settings" class="wf-button btn-right"><span class="dashicons dashicons-lock"></span> Spremi postavke </button>
<br/>