<form method="post" action="options.php" id="activation_form">
<?php settings_fields('reviews-activationkey') ?>
<?php $selectOptions=get_option('reviews-activationkey-settings'); ?>
<input type="hidden" class="activation_val" name="activation" value="<?php echo $selectOptions['activation'];?>">
<?php do_settings_sections('reviews-activationkey-settings') ?>
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" onclick="my_action_javascript()"></p>

</form>