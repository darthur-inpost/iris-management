<?php

require_once('config.php');
require_once('includes/database.php');
require_once('RestApi.php');
require_once('better_crypt.php');

session_start();

// Sanitize data
function test_input($data)
{
	$data = trim($data);
	//$data = stripslashes($data);
	//$data = htmlspecialchars($data);
	return $data;
}

///
// checkRedundantVal
//
// @brief check if a field already exists in DB
//
// @param value to be checked.
//
function checkRedundantVal($val2check)
{
	$con = tep_db_connect();
	if (!$con)
	{
            die('Could not connect: ' . mysqli_error());
        }
	$result = tep_db_query( "SELECT * FROM users WHERE username='$val2check' LIMIT 1");
	$ret = tep_db_fetch_array($result);

	if(count($ret) > 0)
	{
		return true;
	}
	else
	{
		return false;
	}
}

if(isset($_POST['form']))
{
	// The username and password will never be used to get XSS access so
	// making sure they are valid should be enough.
	$ret = preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/",
		$_POST['email']);

	if($ret != true)
	{
		$_SESSION['error_message'] = 'Email is not a valid email address.<br>Please use a different email address.';
		session_write_close();
		header("Location: register.php");
		return;
	}

	// Check that the API Key contains the right values
	$ret = preg_match("/^([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}$/", $_POST['api_key']);

	if($ret != true)
	{
		$_SESSION['error_message'] = "The API Key must be the one provided by InPost.";
		session_write_close();
		header("Location: register.php");
		return;
	}

	// Check that the password contains enough
	$ret = preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}$/", $_POST['password']);

	if($ret != true)
	{
		$_SESSION['error_message'] = "The password must contain:<br>one lower case letter, one upper case letter,<br>one digit, one special character,<br>be 8-20 in length, and have no spaces.";
		session_write_close();
		header("Location: register.php");
		return;
	}

	$username      = test_input($_POST["email"]);
	$api_key       = test_input($_POST["api_key"]);
	$answer        = test_input($_POST["answer"]);

	$label_format  = $_POST['label_format'];
	$question      = $_POST['question'];

	$password      = $_POST['password'];
	$password_hash = better_crypt($password);

	// Check if the API Key is valid
       
	$restApi = new RestApi(array(
		'url'        => $base_url . 'machines',
		'token'      => $api_key,
		'methodType' => 'GET',
		'params'     => array())
	);
       
	$info_arr = ($restApi->getInfo());
	//echo $info_arr["http_code"];

	if ($info_arr["http_code"] != 200)
	{
		$_SESSION['error_message'] = 'Your API Key does not seem to be valid<br>Error Code: ' .
			$info_arr["http_code"];
		header("Location: register.php");
		return;
	}
       
       	// check if the username or API already exists in the DB
       
	if (checkRedundantVal($username) || checkRedundantVal($api_key))
	{
		header("Location: check_user_false.php");
	}
	else
	{
		// Connect to the DB
		$con = tep_db_connect();
		if (!$con)
		{
			die('Could not connect: ' . mysqli_error());
		}
    
		// Insert a new row
		$query = "INSERT INTO users(username, password, api_key, last_login, ip_address, label_format, secQ, secA) VALUES ('$username', '$password_hash', '$api_key', '', '', '$label_format',$question, '$answer')";

		tep_db_query($query);

		tep_db_close();

		header("Location: check_key_true.php");
	}
}

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
	}, "The password must contain:<br>one lower case letter, one upper case letter,<br>one digit one special letter,<br>be 8-20 in length, and have no spaces.");

	// Check if the API Key matches the regular expression.
	jQuery.validator.addMethod("check_apikey", function(value)
	{
// ^([0-9a-fA-F]){8}$
		var PE = /^([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}$/;

		if(!PE.test(value))
		{
			return false;
		}
		return true;
	}, "The API Key must be the one provided by InPost.");


jQuery(document).ready(function() {
	// Validation is based on class and type fields.
	jQuery("#registerForm").validate({
		errorLabelContainer: jQuery("#registerForm td.error"),
		rules: {
			password: "check_password",
			api_key:  "check_apikey",
			cpassword: {
				equalTo: "#password"
			}
		},
		messages: {
			email: "Enter an email address",
			api_key : {
				required: "Provide an API Key",
				minlength: jQuery.validator.format("Enter at least {0} characters")
			},
			password: {
				required: "Provide a password",
				minlength: jQuery.validator.format("Enter at least {0} characters")
			},
			question: {
				required: "Please select a Question"
			},
			answer: {
				required: "Please enter an Answer",
				minlength: jQuery.validator.format("Enter at least {0} characters")
			},
			cpassword: {
				required: "Repeat your password",
				minlength: jQuery.validator.format("Enter at least {0} characters"),
				equalTo: "Enter the same password as above"
			},
			label_format: {
				required: "Please select a Label Format"
			}
		}

	});
});
</script>
 
<script type="text/javascript">
function checkPass(){
	var pass1 = document.getElementById('password');
	var pass2 = document.getElementById('cpassword');

	var message = document.getElementById('confirmMessage');

	var goodColor = "#66cc66";
	var badColor = "#ff6666";

	if(pass1.value == pass2.value)
	{
		pass2.style.backgroundColor = goodColor;
		message.style.color = goodColor;
		message.innerHTML = "Passwords Match!";
		document.getElementById("submit").disabled = false;
	}
	else
	{
		pass2.style.backgroundColor = badColor;
		message.style.color = badColor;
		message.innerHTML = "Passwords Do Not Match!";
		document.getElementById("submit").disabled = true; 
	}
}   

</script>
 
</head>
 
<body>
<div class="wrapper">
<div id="header"><img src="images/logo.png"/></div>

<div class="main_menu">
<h3 style="text-align:center;">InPost 24/7 - User Portal</h3>
</div>

<noscript>For the best user experience enable Javascript.</noscript>

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

    <form id="registerForm" method="POST">
    <table border="0" cellspacing="5" align="center">
	    <tr>
		<td><span style="color: darkgrey;">Email Address</span></td>
		<td>
		<input type="email" size="25" name="email" id="email" required type="email">
		</td><td></td>
	    </tr>
	    <tr>
		<td><span style="color: darkgrey;">InPost API Key</span></td>
		<td>
		<input type="text" autocomplete="off" size="25" name="api_key" id="api_key" required minlength="36" maxlength="36">
		</td>
		<td><img src='http://inpost247.uk/shopify_app/images/qmark.jpg' style="cursor:pointer;" onClick="alert('To obtain an API Key, please email sales@inpost.co.uk')"></td>
	    </tr>
	    <tr>
		<td><span style="color: darkgrey;">Label Format</span></td>
		<td><select name="label_format" id="label_format" required>
			<option value=""   >Please Select...</option>
			<option value="Pdf">PDF</option>
			<option value="Epl2">EPL</option>
		   </select>
		</td>
		<td><img src='http://inpost247.uk/shopify_app/images/qmark.jpg' style="cursor:pointer;" onClick="alert('The label format determines the kind of labels that are produced.\nPDF labels are for a laser printer.\nEPL type labels are for barcode printers.')"></td>
	    </tr>
		<tr>
		<td><span style="color: darkgrey;">Security Question Type</span></td>
		<td><select name="question" id="question" required>
			<option value="">Please Select...</option>
			<option value="0">What is your mother's maiden name?</option>
			<option value="1">What city were you born in?</option>
			<option value="2">What is your favorite color?</option>
			<option value="3">What year did you graduate from High School?</option>
			<option value="4">What was the name of your first boyfriend/girlfriend?</option>
			<option value="5">What is your favorite model of car?</option>
		   </select>
		</td>
		<td><img src='http://inpost247.uk/shopify_app/images/qmark.jpg' style="cursor:pointer;" onClick="alert('This is the secutiry question that will be needed if you ever have to reset your password.');"></td>
		</tr>
		<tr>
		<td><span style="color: darkgrey;">Question Answer</span></td>
		<td>
		<input type="text" minlength="3" maxlength="50" size="25" required name="answer" id="answer" >
		</td>
		</tr>
	    <tr>
		<td>
		<span style="color: darkgrey">Set a Password</span></td> <td><input type="password" size="25" name="password" id="password" required minlength="8" maxlength="20">
		</td>
		<td></td>
	    </tr>
	    <tr>
		<td><span style="color: darkgrey">Confirm Password</span></td>
		<td>
		<input type="password" onkeyup="checkPass(); return false;" size="25" name="cpassword" id="cpassword" minlength="8" maxlength="20" required>
		</td>
		<td></td>
	    </tr>
		<tr>
		<td colspan="3" class="error">
		</td>
		</tr>
	    <tr>
		<td colspan="3" style="text-align:center;"><button name="submit" id="submit">Submit</button> 
<button name="clear" id="clear" onclick="this.form.reset(); return false;" >Clear Fields</button></td>
	    </tr>
	    <tr>
		<td></td>
		<td><span id="confirmMessage" class="confirmMessage" ></span></td>
		<td></td>
	    </tr>
    </table>
	<?php // This field must be present for form processing to take place. ?>
	<input type="hidden" name="form" value="ok"/>
    </form>
</div>
 
</div>
<div class="footer">
&copy; <?php echo date('Y'); ?> InPost UK Ltd  - All rights reserved.
</div> <!-- end of footer -->
</body> 
 
</html>
