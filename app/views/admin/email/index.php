<?php 
use Reviews\Foundation\Functions;
$img_path=site_url().'/wp-content/plugins/review/app/assets/images/loader.gif';
?>
<div id="loader" style="display:none">
<div id='overlay_template'></div>
<img src='<?php echo $img_path; ?>'>
</div>
<div class="modal fade send-mail-wrapper" id="sendmailmodel" role="dialog">
 <div class="modal-dialog modal-sm">
 
 <div class="modal-content"> 
 
 <div class="modal-header"> 
 
 <button type="button" class="close" data-dismiss="modal">&times;</button>
 
 <h4 class="modal-title">Add a Recipient</h4> 
 
 </div> <div class="modal-body"> 
 
 <form method="post" action="#">
 <div class="single-field-wrap flex-field">
 
 <div class="flex-grid-item">
 
 <select name="useremail[]" id="select_email" multiple > 
 <option name="" rows="5" cols="60" > Select Email </option>
 <?php 
 // $test=Functions::CheckEmailAlreadySend();
 
 ?>
 </select> <label class="or-highlight"> OR</label> 
 <input type="email" name="email" id="manual_email" placeholder="Enter Email">
 
 </div>
 
 <div class="flex-grid-item">
 
 <div class="notice-paragraph">
	Select individual reciepient from list of all users on the website or manually enter an email. They'll get the email along with your message.
 </div>
 
 </div>
 
 
 </div>
 
  <div id="fname-field" class="single-field-wrap">
 <label for="fname">First Name*(Required)</label>
 <input type="text" name="fname" placeholder="eg. John" id="fname" required>
 </div>
 
  <div  id="lname-field"  class="single-field-wrap">
 <label for="lname">Last Name</label>
 <input type="text" name="lname" placeholder="eg. Doe" id="lname">
 </div>
 <div id="editor-field" class="single-field-wrap" >
 <?php Functions::TextareaEditor();?>
 </div>
 <br/>
 <br/>
 <input type="checkbox" value="I certify that all recipients have opted in to receive these communications" checked> I certify that all recipients have opted in to receive these communications
 <p class="submit">
 <input type="submit" value="Add a Recipient" name="semail" class="button button-primary" > 
 <button type="button" class="button btn-info" data-dismiss="modal">Cancel</button>
 </p>
 </form>
 </div> 
 </div>
 </div> 
 </div>
 </div> 
 <button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#sendmailmodel">Add a Recipient</button>   
<?php 
function wpse27856_set_content_type(){
    return "text/html";
}
add_filter( 'wp_mail_content_type','wpse27856_set_content_type' );
 
 print("<script>jQuery('.button-primary').click(function(){
var email=jQuery('input[name=email]').val();
var strUser= jQuery('#select_email').val();
if(email === '' && strUser ===  ''){
jQuery('.submit').append('<div class=msg style=color:red;text-align:center;font-size:16px;font-weight:800;>Please Select Email Or Manual Enter Email</div>');
return false;
}
else if(email != '' && strUser !=  ''){
jQuery('.submit').append('<div class=msg style=color:red;text-align:center;font-size:16px;font-weight:800;>Please Choose One Form Select Email Or Manual Enter Email </div>');
return false;
}
else{
return true;	
}
  
});
jQuery('#sendmailmodel').click(function(){
jQuery('.msg').hide();
});
</script>");
 
 
 
 
if(isset($_POST['semail'])){
$form=Functions::insertFromData($_POST);
 
}
?> 