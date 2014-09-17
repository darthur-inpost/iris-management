<?php
// We will pass back error messages via a session variable.
session_start();
?>
<!DOCTYPE HTML>
<!--Created on 08-07-2012 - MHM -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://www.w3.org/2005/10/profile">
<link rel="icon" type="image/png" href="images/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="author" content="InPost UK Ltd">
<meta name="dcterms.rightsHolder" content="InPost UK Ltd">
<meta name="dcterms.dateCopyrighted" content="2014">
<title>InPost 24/7 - User Portal</title>
<link rel="stylesheet" type="text/css" href="css/main_ss.css">
<style>
.warning {
	color: red;
}
</style>
<script type="text/javascript" src="js/jquery-1.11.1.js"></script>
<script type="text/javascript" src="js/jquery.validate.min.js"></script>
<script type="text/javascript">
	// Check if the password matches the regular expression.
	jQuery.validator.addMethod("check_password", function(value)
	{
		var PE = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}$/;

		if(!PE.test(value))
		{
			return false;
		}
		return true;
	}, "The password must contain:<br>one lower case letter, one upper case letter, one digit,<br>be 8-20 in length, and have no spaces.");

jQuery(document).ready(function() {
	// Validation is based on class and type fields.
	jQuery("#loginForm").validate({
		errorLabelContainer: jQuery("#loginForm td.error"),
		rules: {
			password: "check_password"
		}

	});
});
</script>
</head>
 
<body>
<?php
require_once('includes/recaptchalib.php');

// Get a key from https://www.google.com/recaptcha/admin/create
$publickey = "6LcQ5fgSAAAAABuwAvlDMgx1CJbOtqTu3JxRv1_Q";
$privatekey = "6LcQ5fgSAAAAAL2YjP1ls9ZIIUlV_HOoPiE4ep7O";
?>

<div class="wrapper">
<div id="header"><img src="images/logo.png"/></div>
<div class="main_menu">
<h3><p style="text-align:center;">InPost 24/7 - User Portal</p></h3>
</div>

<div align="center" style="position: relative; top:75px; margin-left:auto; margin-right:auto;">
<?php
	// Output an error messages we get.
	if(isset($_SESSION['error_message']))
	{
		echo '<p style="color:red;">' . $_SESSION['error_message'] .
			'</p>';
	}
	unset($_SESSION['error_message']);
?>

<noscript>For the best user experience please enable Javascript.</noscript>

    <form method="POST" id="loginForm" action="check_user_login.php">
    <table border="0" cellspacing="5" align="center">
	    <tr>
	        <td><span style="color: darkgrey">Username</span></td>
		<td>
			<input id="username" name="username" size="25" placeholder="john@example.com" maxlength="32" required type="email">
		</td>
		<td></td>
	    </tr>
	    <tr>
		<td><span style="color: darkgrey">Password</span></td>
		<td>
		    <input type="password" size="25" name="password" id="password" autocomplete="off" maxlength="12" placeholder="Your Password" data-rule-required="true" minlength="8" data-msg-required="Please enter Password">
		</td>
		<td></td>
	    </tr>
	    <tr>
		<td><span style="color: darkgrey">Prove your a<br>human being</span></td>
		<td><?php echo recaptcha_get_html($publickey, $error); ?></td>
		<td></td>
	    </tr>
	<tr>
		<td colspan="3" class="error">
		</td>
	</tr>
	    <tr>
		<td colspan="3" style="text-align:center;"><button name="submit" id="submit">Submit</button></td>
	    </tr>
	    <tr>
		<td></td>
		<td colspan="2"><p class="prompt"> Forgot your password? <a href="fcredentials.php"><i>Click here to reset password</i></a></p></td>
	    </tr>
	    <tr>
		<td></td>
		<td colspan="2"><p class="prompt"> Not a registered user? <a href="register.php"><i>Click here to register</i></a></p></td>
	    </tr>
    </table>
	<?php // This field must be present for form processing to take place. ?>
	<input type="hidden" name="form" value="ok"/>
   </form>
</div>
 
 
 
 
</div>
<div class="footer">
&copy; <?php echo date('Y');?> InPost UK Ltd  - All rights reserved.
</div> <!-- end of footer -->
</body>
</html>
