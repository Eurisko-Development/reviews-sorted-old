<?php 
use Reviews\Foundation\Functions;
$img_path=site_url().'/wp-content/plugins/review/app/assets/images/loader.gif';
?>
<div class="container">
<br/>
<br/>
<br/>
<h3>Add New Email Template</h3>
<div id="loader" style="display:none">
<div id='overlay_template'></div>
<img src='<?php echo $img_path; ?>'>
</div>
 <div id="editor-field" class="single-field-wrap"  >

 <?php $_Functions = new Functions();
 		$_Functions->SaveNewTemplate();?>
 </div>
</div>
