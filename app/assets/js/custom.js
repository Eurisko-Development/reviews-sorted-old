jQuery(document).ready(function(){

jQuery( "#activation_form .hidden" ).closest('tr').remove();

jQuery('.bxslider').bxSlider({

    auto: true,

    minSlides: 1,

    maxSlides: 1,

    slideWidth: 1000

  });

  

    jQuery('.testimonial-tooltip.tooltip').click(function(){	 

	jQuery('#testimonial-tooltip-content').fadeToggle();});	

		/// jQuery('.colopickersetting').wpColorPicker();

		

		

		jQuery('.multi-field-wrapper').each(function() {

			

		jQuery(".add-field",jQuery(this)).click(function(e) {

			

			

			var counts=jQuery('.counttotals').val();

			var count= parseInt(counts);

			

			

			if(count == '0'){

			var count=1;

			}

			else{

 

			count =count + 1;	

			}

			

			jQuery('.counttotals').val(count);

			

			var countotal=count++

			

			var urlname='Url'+countotal;

		jQuery('.form-table').append('<tr><th scope="row"><div>  ' +urlname+'</div></th><td><input type="text" placeholder="https://" class="customlable textbox " name="reviews-mailchimp-settings[mailchimpcustom'+urlname+']" value="" style="width: 200px"><input type="hidden" class=count'+countotal+' value="'+countotal+'"><button class="button button-primary remove-primary" onclick="removeField(this); return false;">Remove</button></td></tr>');

		

		console.log(countotal);

		

			if(countotal == 5){

				

				jQuery('.form-table').append("<tr class='errortr' ><th colspan='2' class='headingerror' ><div class='errorurl' > You can't add  more then 5  additional Urls</div></th><td></td></tr>");

				

				jQuery('.add-field').attr('disabled',true);

				

			}

		

		

		});

		

		

		});

		

		

		

			

            jQuery(".Reviews-Page a.submitdelete").click(function(event) {

				

				event.preventDefault();

                var del_id = jQuery(this).attr("id");

				var  tr = jQuery(this).closest('tr');

                var info = 'id=' + del_id;

			   

                if (confirm("Please be aware that deleting or changing customer testimonials could be considered as false, misleading or deceptive conduct and could contravene local laws.  Click OK to continue or Cancel to return.")) {

                    jQuery.ajax({

                        type : "POST",

                        url :"admin.php?page=reviews/delete.php", //URL to the delete php script

                        data : info,

                        success : function(data) {

				

					

						 tr.fadeOut(1000, function(){

                         jQuery(this).remove();

						 window.location.reload(true);

							});

                        }

                    });

                  

                }

                return false;

            });

			

			

			

			/*22-Feb-2019  Add Warning Message On Edit Review Link */

			jQuery("a.reviwe_edit").click(function(event) {

				

				event.preventDefault();

                var id = jQuery(this).attr("id");

	         if(confirm("Please be aware that deleting or changing customer testimonials could be considered as false, misleading or deceptive conduct and could contravene local laws.  Click OK to continue or Cancel to return.")){

             window.location.href="admin.php?page=reviews/edit&id="+id;

             }

})

			

			

	

		jQuery("#send").on("click", function(){

		jQuery(".popup, .popup-content").addClass("active");

		});

		jQuery("#send, .popup").on("click", function(){

		jQuery(".popup, .popup-content").removeClass("active");

		});

		

		

	

	/*11-apr-2019 */

	jQuery('#select_email ').click(function(){

		jQuery('#fname').removeAttr("required");

		jQuery('#fname-field').hide();

		jQuery('#lname-field').hide();

	});

	jQuery('#manual_email ').click(function(){

		jQuery('#fname').prop('required',true);

		jQuery('#fname-field').show();

		jQuery('#lname-field').show();

	});

 

});

function removeField(field){

	

	jQuery(field).closest('tr').remove();

	

	var counts=jQuery('.counttotals').val();

	var count= parseInt(counts);

	count = count - 1;

/* 	jQuery(field).closest('tr').next('tr').addClass('yello');

	console.log(jQuery(field).closest('tr').next('tr').length); */

	

	if( jQuery(field).closest('tr').next('tr').length == 0 ){

		

		jQuery('.counttotals').val(count);

	}

	

	jQuery('.errortr').css('display','none');

				

	jQuery('.add-field').attr('disabled',false);

	

	

	

}

jQuery('.notice-dismiss').click(function(){

jQuery('.activation').remove();

});

function my_action_javascript() {

	

	

jQuery( "#activation_form" ).submit(function( event ) {

 var postdata = jQuery('input[name="reviews-activationkey-settings[reviews-activation_key]"]').val();

		var data = {

			'action': 'my_action',

			'activation_key':postdata,

		}; 

		

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php

		jQuery.post(ajaxurl, data, function(response) {

		  

		 

			window.location.href="admin.php?page=reviews";

		

		});

		return false;

});

}

/*12-Apr-2019*/

	

jQuery('#savetemplate').click(function(){

var postdata = jQuery("#popup_email_textarea").val();

jQuery('#templatename-field').show();

var templatename=jQuery('#tname').val();

jQuery('#loader').show();

if(jQuery('#tname').val() == '' ){

jQuery('#loader').hide();

return false;

}else{

	

	var data = {

			'action': 'SaveEditerData',

			'data':postdata,

			'templatename':templatename,

		}; 

		//since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php

		jQuery.post(ajaxurl, data, function(response) {

		  

		 //jQuery('#templatename-field').hide();

			jQuery('#loader').hide();

		

		});

}

});

jQuery("select").each(function()

{

jQuery(this).change(function(){

	var temp= jQuery("#email_template").val();

	

jQuery('#loader').show();

	

	

var data = { 

'action': 'Editor',

"currenttemplate": temp

};

	

$.ajax({

  url: ajaxurl,

  type: 'POST',

  dataType: "html",

  data: data,

		  

success: function(response) {

	

// jQuery( document ).ajaxComplete(function() {	

// var activeEditor = tinyMCE.get('content');

// if(activeEditor!==null){

   // tinyMCE.activeEditor.setContent(response);

// } else {

       // jQuery("#popup_email_textarea").html(response);

// }

// });

// var activeEditor = tinyMCE.get('content');

// if(activeEditor!==null){

   // tinyMCE.activeEditor.setContent(response);

// } else {

     // jQuery("#popup_email_textarea").html(response);  

// }

  jQuery("#popup_email_textarea").html(response);

         jQuery("#savenewtemplate").hide();

         jQuery("#updatetemplate").show();

         jQuery("#deletetemplate").show();

		 jQuery("#tname").val(temp);

		//jQuery('#savetemplate').hide();

		jQuery('#loader').hide();

	

      }

	  

});

});

});

jQuery('#savenewtemplate').click(function(){

var postdata = jQuery("#popup_email_textarea").val();

jQuery('#templatename-field').show();

var templatename=jQuery('#tname').val();

jQuery('#loader').show();

if(jQuery('#tname').val() == '' ){

	

 jQuery("#tname").focus(function() {

      jQuery('.savenewtemplatenamemsg').hide('slow');       

      //return false;

    });

  

if( !jQuery('#tname').val() ) {

          jQuery('.savenewtemplatenamemsg').html(' *required field');

    }

  

jQuery('#loader').hide();

return false;

 

}else{

	

	var data = { 

			'action': 'SaveNewTemplate',

			'data':postdata,

			'templatename':templatename,

		}; 

		//since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php

		jQuery.post(ajaxurl, data, function(response) {

		 jQuery('#loader').hide(); 

	 jQuery('.container').prepend("<div class='notice notice-success is-dismissible activation'><p>Template Save Successfully !</p><button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button></div>");

		 

		 

		  setTimeout(function(){ 

		  

		  window.location.reload();

		  

		  }, 500);

		  

		  

		 

		});

}

});

jQuery('#updatetemplate').click(function(){

var postdata = jQuery("#popup_email_textarea").val();

var temp= jQuery("#email_template").val();

var newtempname=jQuery("#tname").val();

jQuery('#loader').show();

	

	var data = {

			'action': 'UpdateTemplate',

			'data':postdata,

			'templatename':temp,

			'newtemplatename':newtempname,

		}; 

		//since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php

		jQuery.post(ajaxurl, data, function(response) {

		  

		  jQuery('#loader').hide();

		  

		 jQuery('.container').prepend("<div class='notice notice-success is-dismissible activation'><p>Template Updated Successfully !</p><button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button></div>");

		 

		 

		  setTimeout(function(){ 

		  

		  window.location.reload();

		  

		  },500);

		

		

		});

});

jQuery('#deletetemplate').click(function(){

var postdata = jQuery("#popup_email_textarea").val();

var temp= jQuery("#email_template").val();

var newtempname=jQuery("#tname").val();

jQuery('#loader').show();

	

	var data = {

			'action': 'DeleteTemplate',

			'data':postdata,

			'templatename':temp,

			'newtemplatename':newtempname,

		}; 

		//since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php

		jQuery.post(ajaxurl, data, function(response) {

		  

		jQuery('#loader').hide();

		

	 jQuery('.container').prepend("<div class='notice notice-success is-dismissible activation'><p>Template Delete Successfully !</p><button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button></div>");

		 

		 

		  setTimeout(function(){ 

		  

		  window.location.reload();

		  

		  }, 500);

		 

		});

});
