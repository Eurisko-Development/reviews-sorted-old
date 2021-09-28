<?php
$fields =[
	'authorfname' => ' First Name', 
	'authorlname' => ' Last Name', 
	'state' => ' State',
	'phone' => 'Phone', 
	'email' => 'Email', 
	'rating' => 'Rating', 
	'recommend' => 'Would you recommend ' . get_bloginfo('name') . ' to your family and friends?', 'content' => 'Comment'
];
?>
<?php
$selectOption = get_option('reviews-email-template-settings');
$selectOptions = array_filter($selectOption);
if (!empty($selectOptions))
{
if (!empty($selectOption['email-heading-content']))
{
$EmailHeadingContent = $selectOption['email-heading-content'];
$EmailHeadingContentColor = $selectOption['email-heading-color'];
}
else
{
$EmailHeadingContent = 'Thank you for choosing';
}
if (!empty($selectOption['email-heading-color']))
{
$EmailHeadingContentColor = $selectOption['email-heading-color'];
}
else
{
$EmailHeadingContentColor = '#383838';
}
if (!empty($selectOption['email-footer-content']))
{
$EmailFooterContent = $selectOption['email-footer-content'];
}
else
{
$EmailFooterContent = 'Thanks';
}
if (!empty($selectOption['email-footer-color']))
{
$EmailFooterContentColor = $selectOption['email-footer-color'];
}
else
{
$EmailFooterContentColor = '';
}
if (!empty($selectOption['email-table-row-color']))
{
$EmailTableRowBackgroundColor = $selectOption['email-table-row-color'];
}
else
{
$EmailTableRowBackgroundColor = '#fff5ec';
}
if (!empty($selectOption['email-table-content-color']))
{
$EmailTableContentColor = $selectOption['email-table-content-color'];
}
else
{
$EmailTableContentColor = '';
}
if (!empty($selectOption['email-table-bgcolor']))
{
$EmailTableBGColor = $selectOption['email-table-bgcolor'];
}
else
{
$EmailTableBGColor = '#383838';
}
if (!empty($selectOption['email-table-label-color']))
{
$EmailTableLabelColor = $selectOption['email-table-label-color'];
}
else
{
$EmailTableLabelColor = '';
}
if (!empty($selectOption['email-container-bgcolor']))
{
$EmailContainerColor = $selectOption['email-container-bgcolor'];
$EmailContainerColor1 = $selectOption['email-container-bgcolor'];
}
else
{
$EmailContainerColor = '';
$EmailContainerColor1 = 'transparent';
} ?>
<table cellspacing="0" cellpadding="0" margin="0" border="0" style="width: 100%; ">
  <tr>
    <td align="center">
      <table cellspacing="0"  cellpadding="0" margin="0" border="0" style="width: 600px; background-color:<?php
          echo $EmailTableBGColor; ?>; border-bottom: 2px solid #ec1d36; padding-top:10px; padding-bottom:8px;">	
        <tr>
          <td align="center">		
            <img src="<?php
                      echo get_site_icon_url(); ?>" height="50">	
          </td>
        </tr>	
      </table> 	
      <table cellspacing="0"  cellpadding="0" margin="0" border="0" style="width: 600px;  height:1px">	
        <tr>
          <td align="center">	
          </td>
        </tr>	
      </table>		
      <h1 style="font-size:24px;font-family: Helvetica, Arial, Sans-Serif;	color:<?php
                 echo $EmailHeadingContentColor; ?>; padding:25px; margin:0px; width:550px;  background-color:<?php
                 echo $EmailContainerColor1; ?>; ">
        <?php
echo $EmailHeadingContent; ?> 
        <?php
echo bloginfo(); ?> 
      </h1>		
      <table cellspacing="0" cellpadding="0" style="width: 600px; margin: 0px; border: 1px solid #eaeaea; background-color:<?php
                                                    echo $EmailContainerColor; ?>">	
        <?php
foreach($fields as $field => $label): ?>	
        <tr>		
          <th style="font-weight: bold; font-family: Helvetica, Arial, Sans-Serif; background-color:<?php
                     echo $EmailTableRowBackgroundColor; ?>; padding: 10px; text-align: left;color:<?php
                     echo $EmailTableLabelColor; ?>">			
            <?php echo $label ?>		
          </th>	
        </tr>	
        <tr>		
          <td style="padding: 10px 10px 10px 20px; text-align:left; font-family: Helvetica, Arial, Sans-Serif;color:<?php
                     echo $EmailTableContentColor; ?>">			
            <?php
if (stristr($field, '.') !== false)
{
list($field, $key) = explode('.', $field);
$value = $review->$field;
$value = $value[$key];
}
else
{
$value = $review->$field;
}
if ($field == 'rating')
{
$value.= $value == 1 ? ' Star' : ' Stars';
}
if ($field == 'content')
{
$value = stripslashes(nl2br($value));
}
echo $value; ?>		
          </td>	
        </tr>	
        <?php
endforeach; ?>	
      </table>
      <p style="font-size:16px; font-weight:bold; color:<?php
                echo $EmailFooterContentColor; ?>; background-color:<?php
                echo $EmailContainerColor1; ?>; width:550px; margin-bottom: 0;margin-top: 0; padding:25px;">
        <?php
echo $EmailFooterContent; ?>, &nbsp;
        <?php
echo bloginfo(); ?>
      </p>
    </td>
  </tr>
</table> 
<?php
}
else
{ ?>
<table cellspacing="0" cellpadding="0" margin="0" border="0" style="width: 100%; ">
  <tr>
    <td align="center">	
      <table cellspacing="0"  cellpadding="0" margin="0" border="0" style="width: 600px; background-color:#383838; border-bottom: 2px solid #ec1d36; padding-top:10px; padding-bottom:8px;">	
        <tr>
          <td align="center">		
            <img src="<?php
                      echo get_site_icon_url(); ?>" height="50">	
          </td>
        </tr>	
      </table>	
      <table cellspacing="0"  cellpadding="0" margin="0" border="0" style="width: 600px; background-color:#fdef35; height:1px">	
        <tr>
          <td align="center">	
          </td>
        </tr>	
      </table>	
      <h1 style="font-size:24px;font-family: Helvetica, Arial, Sans-Serif; color:#383838; padding:25px;">Thank you for choosing 
        <?php
echo bloginfo(); ?> 
      </h1>	
      <table cellspacing="0" cellpadding="0" style="width: 600px; margin: 10px 0 40px; border: 1px solid #eaeaea;">	
        <?php
foreach($fields as $field => $label): ?>	
        <tr>		
          <th style="font-weight: bold; font-family: Helvetica, Arial, Sans-Serif; background-color: #fff5ec; padding: 10px; text-align: left;">			
            <?php echo $label ?>		
          </th>	
        </tr>	
        <tr>		
          <td style="padding: 10px 10px 10px 20px; text-align: left; font-family: Helvetica, Arial, Sans-Serif;">			
            <?php
if (stristr($field, '.') !== false)
{
list($field, $key) = explode('.', $field);
$value = $review->$field;
$value = $value[$key];
}
else
{
$value = $review->$field;
}
if ($field == 'rating')
{
$value.= $value == 1 ? ' Star' : ' Stars';
}
if ($field == 'content')
{
$value = stripslashes(nl2br($value));
}
echo $value; ?>		
          </td>	
        </tr>	
        <?php
endforeach; ?>	
      </table>
      <p style="font-size:16px; font-weight:bold; ">Thanks, 
        <?php
echo bloginfo(); ?>
      </p>
    </td>
  </tr>
</table> 
<?php
} ?>
