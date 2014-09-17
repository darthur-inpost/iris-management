<?php

require_once('config.php');
require_once('includes/database.php');

session_cache_expire( $cache_expire );
session_start();

if (isset($_SESSION['password']))
{
	$password     = $_SESSION['password'];
	$username     = $_SESSION['username'];
	$api_key      = $_SESSION['api_key'];
	$session_life = time() - $_SESSION['start'];
    
	if($session_life > $inactive)
	{
		header("Location: logout.php");
	}
	$_SESSION['start'] = time();
}
else
{
	header("Location: 404_error.php");    
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="author" content="InPost UK Ltd">
<meta name="dcterms.rightsHolder" content="InPost UK Ltd">
<meta name="dcterms.dateCopyrighted" content="2014">
<title>InPost 24/7 - User Portal</title>
<link rel="stylesheet" type="text/css" href="css/main_ss.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">
<style type="text/css">
	.calendar {
		font-family: 'Trebuchet MS', Tahoma, Verdana, Arial, sans-serif;
		font-size: 0.9em;
		background-color: #EEE;
		color: #333;
		border: 1px solid #DDD;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		padding: 0.2em;
		width: 14em;
	}
			
	.calendar .months {
		background-color: #F6AF3A;
		border: 1px solid #E78F08;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		color: #FFF;
		padding: 0.2em;
		text-align: center;
	}
			
	.calendar .prev-month,
	.calendar .next-month {
		padding: 0;
	}
	
	.calendar .prev-month {
		float: left;
	}
	
	.calendar .next-month {
		float: right;
	}
			
	.calendar .current-month {
		margin: 0 auto;
	}
			
	.calendar .months .prev-month,
	.calendar .months .next-month {
		color: #FFF;
		text-decoration: none;
		padding: 0 0.4em;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		cursor: pointer;
	}
	
	.calendar .months .prev-month:hover,
	.calendar .months .next-month:hover {
		background-color: #FDF5CE;
		color: #C77405;
	}
			
	.calendar table {
		border-collapse: collapse;
		padding: 0;
		font-size: 0.8em;
		width: 100%;
	}
			
	.calendar th {
		text-align: center;
	}
			
	.calendar td {
		text-align: right;
		padding: 1px;
		width: 14.3%;
	}
			
	.calendar td span {
		display: block;
		color: #1C94C4;
		background-color: #F6F6F6;
		border: 1px solid #CCC;
		text-decoration: none;
		padding: 0.2em;
		cursor: pointer;
	}
			
	.calendar td span:hover {
		color: #C77405;
		background-color: #FDF5CE;
		border: 1px solid #FBCB09;
	}
	
	.calendar td.today span {
		background-color: #FFF0A5;
		border: 1px solid #FED22F;
		color: #363636;
	}
</style>

<script type="text/javascript" src="js/view.js"></script>
<script type="text/javascript" src="js/jquery-1.11.1.js"></script>
<script type="text/javascript" src="js/jquery.validate.min.js"></script>

<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
<script>
$(function() {
$( "#expiry_date" ).datepicker({minDate:0, maxDate: "+6M", dateFormat: 'yy-mm-dd'});
});
</script>

<script type="text/javascript">
        
        function ClearForm(){
            document.form_818104.reset();
        }

	function my_validate()
	{
            if ((document.getElementById("fname").value=="" )|| 
                    (document.getElementById("lname").value=="") || 
                    (document.getElementById("street").value=="")||
                    (document.getElementById("building").value=="")||
                    (document.getElementById("postcode").value=="")||
                    (document.getElementById("town").value=="")||
                    (document.getElementById("county").value=="")||
		    (document.getElementById("r_mobile").value==""))
	    {
                    alert("Please provide full address information!");
                    return false;
            }  
       
             if (document.getElementById("mobile").value==""){
                alert("Please Enter Your Mobile Number!");
                return false;
            }  
            
             if (document.getElementById("size").value==""){
                alert("Please Select a Parcel Size");
                return false;
            } 

            if (document.getElementById("email").value==""){
                alert("Please Enter Your Email Address!");
                return false;
            }    

            var phone = document.getElementById("mobile");
            var r_phone = document.getElementById("r_mobile");
            var RE = /^[\d\.\-]+$/;
            if ((phone.value.length!=10) || (r_phone.value.length!=10) )
            {
                alert("Invalid phone number: Only last 10 digits required!");
                return false; 
            }
            if((!RE.test(phone.value)) || (!RE.test(r_phone.value)))
            {
                alert("Invalid phone number: Only last 10 digits required!");
                return false;
            }   

            var email=document.getElementById("email");
            var CE = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            
            if(!CE.test(email.value)){
                alert("You have entered an invalid email address!");
                    return false;
            } 
         
            var dateString = document.form_818104.expiry_date.value;
var         myDate = new Date(dateString);
var         today = new Date();
            
	if (document.form_818104.expiry_date.value=="")
	{
		//something is wrong
		alert('Please enter a valid expiry date!')
		return false;
	}
	else if (myDate < today)
	{ 
		//something else is wrong
		alert('Invalid Expiry Date: Should be a future date!')
		return false;
	}
}

jQuery(document).ready(function() {
	// Validation is based on class and type fields.
	jQuery("#form_818104").validate({
		//errorLabelContainer: jQuery("#form_818104 td.error"),
		//rules: {
			//password: "check_password"
		//}
		messages: {
			rma_code: "Enter an RMA code",
			expiry_date: {
				required: "Enter an Expiry Date"
			},
			size: "Please select a Parcel Size",
			email: "Please enter a valid email address",
			mobile: "Please enter a Mobile number",
			add_desc1: "Please enter description",
			add_desc2: "Please enter description",
			add_desc3: "Please enter description",
			fname: "Please enter First Name",
			lname: "Please enter Last Name",
			cname: "Please enter Company Name",
			building: "Please enter Building Number",
			street: "Please enter Street Name",
			town: "Please enter Town Name",
			postcode: "Please enter Postcode",
			county: "Please enter County Name",
			r_mobile: "Please enter Client Mobile Number"
		},
		// the errorPlacement has to take the table layout into account
		errorPlacement: function(error, element)
		{
			if (element.is(":radio"))
				error.appendTo(element.parent().next().next());
			else if (element.is(":checkbox"))
				error.appendTo(element.next());
			else
				error.appendTo(element.parent().next());
		},
	});
});
</script>

</head>
<body id="main_body" onload="ClearForm()" >
<div class="wrapper">

<?php include_once('includes/menu.php'); ?>

	<div style="align:center;">
<?php
	// Output an error messages we get.
	if(isset($_SESSION['error_message']))
	{
		echo '<p style="color:red;">' . $_SESSION['error_message'] .
			'</p>';
	}
	unset($_SESSION['error_message']);
?>

	<form id="form_818104" name="form_818104" class="appnitro"  method="post" action="activate.php">

	<table style="margin-left:auto; margin-right:auto;">
	<tr>
		<td>
		<label class="description" for="rma_code">Assign a RMA Code (optional) </label>
		</td>
		<td>
			<input id="rma_code" name="rma_code" id="rma_code" class="element text medium" type="text" maxlength="255" value="" placeholder="Return Merchandise Authorisation Code" />
		<img src='http://inpost247.uk/shopify_app/images/qmark.jpg' style="cursor:pointer;" onClick="alert('If you supply a value it MUST be unique.\nIf it is not then the parcel creation will fail.')">
		</td>
	</tr>
	<tr>
		<td>
		<label class="description" for="expiry_date">Enter an Expiry Date</label>
		</td>
		<td>
                    <input class="element text medium" name="expiry_date" id="expiry_date" placeholder="Within six months" required="required" />
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td>
		<label class="description" for="size">Select Parcel Size</label>
		</td>
		<td>
		<select class="element select medium" id="size" name="size" required="required" >
			<option value="" selected="selected">Select Parcel Size</option>
			<option value="A" >A</option>
			<option value="B" >B</option>
			<option value="C" >C</option>
		</select>
		<img src='http://inpost247.uk/shopify_app/images/qmark.jpg' style="cursor:pointer;" onClick="alert('A (H x W x D):8cm x 38cm x 64cm. Our medium size (B) is 19cm h x 38cm w x 64cm d and our largest size (C) is 38cm h x 38cm w x 64cm')">
		</td>	
		<td class="status"></td>
	</tr>
	<tr>
                <td>
		<label class="description" for="email">Sender's Email Address </label>
		</td>
		<td>
			<input id="email" name="email" class="element email text medium" type="text" maxlength="255" value="" placeholder="Sender's Email Address" required="required" /> 
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td>
		<label class="description" for="mobile">Sender's Mobile  Number +44 (0) </label>
		</td>
		<td>
			<input id="mobile" name="mobile" class="element number medium" type="text" minlength="10" maxlength="10" value="" placeholder="Sender's Mobile Number" required="required" /> 
		<img src='http://inpost247.uk/shopify_app/images/qmark.jpg' style="cursor:pointer;" onClick="alert('Sender\'s Mobile  Number (last 10 digits, e.g. 7848194998)')">
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td>
		<label class="description" for="add_desc1">Description 1 (optional)</label>
		</td>
		<td>
			<input id="add_desc1" name="add_desc1" class="element text medium" type="text" maxlength="100" value="" placeholder="Extra description 1" /> 
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td>
		<label class="description" for="add_desc2">Description 2 (optional)</label>
		</td>
		<td>
			<input id="add_desc2" name="add_desc2" class="element text medium" type="text" maxlength="100" value="" placeholder="Extra description 2" /> 
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td>
		<label class="description" for="add_desc3">Description 3 (optional)</label>
		</td>
		<td>
			<input id="add_desc3" name="add_desc3" class="element text medium" type="text" maxlength="100" value="" placeholder="Extra description 3" /> 
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td colspan="2" style="text-align:center;">
		<label style="font-weight:bold;">Recipient's Address </label>
		</td>
	</tr>
	<tr>
		<td>
		     <label class="description" for="fname">First Name </label>
		</td>
		<td>
			<input id="fname" name="fname" class="element text medium" type="text" maxlength="255" value="" required="required" />
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td>
                     <label class="description" for="lname">Last Name </label>
		</td>
		<td>
			<input id="lname" name="lname" class="element text medium" type="text" maxlength="255" value="" required="required" />
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td>
                     <label class="description" for="cname">Company Name (optional)</label>
		</td>
		<td>
			<input id="cname" name="cname" class="element text medium" type="text" maxlength="255" value="" /> 
		<img src='http://inpost247.uk/shopify_app/images/qmark.jpg' style="cursor:pointer;" onClick="alert('If you provide a Company name the labels created will NOT show the clients name.\nLeave blank if you want the First / Last name to show.')">
		</td> 
		<td class="status"></td>
	</tr>
	<tr>
		<td>
                <label class="description" for="building">Building Number</label>
		</td>
		<td>
			<input id="building" name="building" class="element text medium" type="text" maxlength="255" value="" required="required" /> 
		</td> 
		<td class="status"></td>
	</tr>
	<tr>
		<td>
                     <label class="description" for="street">Street Name</label>
		</td>
		<td>
			<input id="street" name="street" class="element text medium" type="text" maxlength="255" value="" required="required" /> 
		</td> 
		<td class="status"></td>
	</tr>
	<tr>
		<td>
                <label class="description" for="town">Town</label>
		</td>
		<td>
			<input id="town" name="town" class="element text medium" type="text" maxlength="255" value="" required="required" /> 
		</td> 
		<td class="status"></td>
	</tr>
	<tr>
		<td>
                <label class="description" for="postcode">PostCode</label>
		</td>
		<td>
			<input id="postcode" name="postcode" class="element text medium" type="text" maxlength="255" value="" required="required" /> 
		</td> 
		<td class="status"></td>
	</tr>
	<tr>
		<td>
                <label class="description" for="county">County</label>
		</td>
		<td>
			<input id="county" name="county" class="element text medium" type="text" maxlength="255" value="" required="required" />
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td>
                <label class="description" for="r_mobile">Mobile +44 (0)</label>
		</td>
		<td>
			<input id="r_mobile" name="r_mobile" class="element text medium" type="text" minlength="10" maxlength="10" value="" required="required" /> 
		</td>
		<td class="status"></td>
	</tr>
	<tr>
		<td colspan="3" class="error"></td>
	</tr>
	<tr>
		<td>
		    <input type="hidden" name="form_id" value="818104" />
		</td>
		<td>
			<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
		</td>
	</tr>
	</table>
	</div>

	</form>	
	</div>
<br>
<br>
	<div class="footer">
	&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.
	</div>
</body>
</html>
