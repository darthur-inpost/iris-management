<?php
require_once('config.php');
require_once('includes/recaptchalib.php');
require_once('includes/database.php');
require_once('includes/get_client_ip.php');

session_start();

# the response from reCAPTCHA
$resp = null;
# the error code from reCAPTCHA, if any
$error = null;

if(isset($_POST['form']))
{
	// The username and password will never be used to get XSS access so
	// making sure they are valid should be enough.
	$ret = preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/",
		$_POST['username']);

	if($ret != true)
	{
		$_SESSION['error_message'] = 'User name is not a valid email address. Please use a different username.';
		session_write_close();
		header("Location: index.php");
		return;
	}

	// Check that the password contains enough
	$ret = preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}$/", $_POST['password']);

	if($ret != true)
	{
		$_SESSION['error_message'] = "The password must contain:<br>one lower case letter, one upper case letter, one digit,<br>be 8-20 in length, and have no spaces.";
		session_write_close();
		header("Location: index.php");
		return;
	}


	// connect to database
	tep_db_connect();

	// check if the user name is present.
	$result = tep_db_query("select * from users where username='" .
		$_POST['username'] .
		"'");
	$ret = tep_db_fetch_array($result);

	// Check that the user name entered is correct.
	if(strcmp($ret['username'], $_POST['username']) != 0)
	{
		tep_db_close();
		$_SESSION['error_message'] = 'User name not found. Please try again.';
		session_write_close();
		header("Location: index.php");
		return;
	}

	// Check that the password entered is correct.
	if(crypt($_POST['password'], $ret['password']) != $ret['password'])
	{
		tep_db_close();
		$_SESSION['error_message'] = 'Password does not match. Please try again.';
		session_write_close();
		header("Location: index.php");
		return;
	}

	# was there a reCAPTCHA response?
	if ($_POST["recaptcha_response_field"])
	{
        	$resp = recaptcha_check_answer ($privatekey,
                                        $_SERVER["REMOTE_ADDR"],
                                        $_POST["recaptcha_challenge_field"],
                                        $_POST["recaptcha_response_field"]);

		if ($resp->is_valid)
		{
			// This is handled below.
		}
		else
		{
                	# set the error code so that we can display it
			tep_db_close();
			$_SESSION['error_message'] = $resp->error;
			session_write_close();
			header("Location: index.php");
			return;
        	}
	}
	else
	{
		tep_db_close();
		$_SESSION['error_message'] = 'You must enter the value in the reCaptcha field.';
		session_write_close();
		header("Location: index.php");
		return;
	}

	// Function to get the client ip address   
	$ip_address = get_client_ip();    
	date_default_timezone_set('Europe/London');
	$last_login = date('Y-m-d H:i:s', time());   

	$_SESSION['last_login'] = $last_login;  
	$_SESSION['username']   = $_POST['username'];
	$_SESSION['password']   = $_POST['password'];
	$_SESSION['start']      = time();
	$_SESSION['ip_address'] = $ip_address;
	session_write_close();
	header("Location: summary.php");
}
?>

