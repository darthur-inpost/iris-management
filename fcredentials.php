<?php
require_once('config.php');
require_once('includes/database.php');

session_cache_expire( $cache_expire );
session_start();

// Set the timezone
// Europe/London
date_default_timezone_set('Europe/London');

if (isset($_SESSION['password']))
{
	$password = $_SESSION['password'];
	$username = $_SESSION['username'];
	$api_key  = $_SESSION['api_key'];

	$session_life = time() - $_SESSION['start'];

	if($session_life > $inactive)
	{
		header("Location: logout.php");
	}
	$_SESSION['start'] = time();
}

$show = 'emailForm'; //which form step to show by default

if (isset($_POST['subStep']) &&
	!isset($_GET['a']) &&
	$_SESSION['lockout'] != true)
{
    switch($_POST['subStep'])
    {
        case 1:
		// connect to database
		tep_db_connect();

            //we just submitted an email or username for verification
            $result = checkUNEmail($_POST['uname'],$_POST['email']);
            if ($result['status'] == false )
            {
                $error = true;
                $show = 'userNotFound';
            } else {
                $error = false;
                $show = 'securityForm';
                $securityUser = $result['userID'];
            }
        break;
        case 2:
		// connect to database
		tep_db_connect();

            //we just submitted the security question for verification
            if ($_POST['userID'] != "" && $_POST['answer'] != "")
            {
                $result = checkSecAnswer($_POST['userID'],$_POST['answer']);
                if ($result == true)
                {
                    //answer was right
                    $error = false;
                    $show = 'successPage';
                    $passwordMessage = sendPasswordEmail($_POST['userID']);
                    $_SESSION['badCount'] = 0;
		}
		else
		{
                    //answer was wrong
                    $error = true;
                    $show = 'securityForm';
                    $securityUser = $_POST['userID'];
                    $_SESSION['badCount']++;
                }
            } else {
                $error = true;
                $show = 'securityForm';
            }
        break;
        case 3:
		// connect to database
		tep_db_connect();

		// we are submitting a new password (only for encrypted)
		if ($_POST['userID'] == '' || $_POST['key'] == '')
			header("location: login.php");
		// Check that the password contains enough
		$ret = preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}$/", $_POST['pw0']);

		if (strcmp($_POST['pw0'], $_POST['pw1']) != 0 ||
			trim($_POST['pw0']) == '' ||
			$ret != true)
		{
			$error = true;
			$show = 'recoverForm';
		}
		else
		{
			$error = false;
			$show = 'recoverSuccess';
			updateUserPassword($_POST['userID'],
				$_POST['pw0'],
				$_POST['key']);
		}
        break;
    }
}
elseif (isset($_GET['a']) &&
	$_GET['a'] == 'recover' &&
	$_GET['email'] != "")
{
	// connect to database
	tep_db_connect();
	$show = 'invalidKey';
	$result = checkEmailKey($_GET['email'],urldecode(base64_decode($_GET['u'])));
	if ($result == false)
	{
		$error = true;
		$show = 'invalidKey';
	}
	elseif ($result['status'] == true)
	{
		$error = false;
		$show = 'recoverForm';
		$securityUser = $result['userID'];
	}
}

if ($_SESSION['badCount'] >= 3)
{
	$show = 'speedLimit';
	$_SESSION['lockout'] = true;
	$_SESSION['lastTime'] = '' ? mktime() : $_SESSION['lastTime'];
}?>
<!DOCTYPE HTML>
<html>
<head profile="http://www.w3.org/2005/10/profile">
<link rel="icon" type="image/png" href="images/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="author" content="InPost UK Ltd">
<meta name="dcterms.rightsHolder" content="InPost UK Ltd">
<meta name="dcterms.dateCopyrighted" content="2014">
<title>InPost 24/7 - User Portal</title>
<link rel="stylesheet" type="text/css" href="css/main_ss.css">
<script type="text/javascript" src="js/jquery-1.11.1.js"></script>
<script type="text/javascript" src="js/jquery.validate.min.js"></script>

<script>
jQuery(document).ready(function() {
	// Validation is based on class and type fields.
	jQuery("#forgotForm").validate({
		errorLabelContainer: jQuery("#forgotForm div.error"),
		//rules: {
			//password: "check_password"
		//}
		messages: {
			simple_email: "Enter an email address",
			simple_mobile: "Enter a Mobile number",
			name: "Please select a Locker",
			simple_size: "Please select a Parcel Size",
			simple_reference: "Please fill in a Reference"
		}
	});
});

</script>
</head>
<body>
<div class="wrapper">

<?php
	// Output an error messages we get.
	if(isset($_SESSION['error_message']))
	{
		echo '<p style="color:red;">' . $_SESSION['error_message'] .
			'</p>';
	}
	unset($_SESSION['error_message']);
?>
<noscript>For the best user experience please enable Javascript</noscript>

   <?php switch($show) {
    case 'emailForm': ?>
    <h2>Password Recovery</h2>
    <p>You can use this form to recover your password if you have forgotten it. Because your password is securely encrypted in our database, it is impossible to actually recover your password, but we will email you a link that will enable you to reset it securely. Enter your username below to get started.</p>
	<?php if ($error == true) { ?>
<span class="error">You must enter a username to continue.</span>
	<?php } ?>
    <form id="forgotForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<div class="fieldGroup">
	<label for="uname">Username</label>
		<div class="field">
			<input type="text" name="uname" id="uname" value="" maxlength="20">
		</div>
	</div>
        <input type="hidden" name="subStep" value="1" />
	<div class="fieldGroup">
		<input type="submit" value="Submit" style="margin-left: 150px;" />
	</div>
        <div class="clear"></div>
    </form>
    <?php break; case 'securityForm': ?>
    <h2>Password Recovery</h2>
    <p>Please answer the security question below:</p>
	<?php if ($error == true) { ?>
	<span class="error">You must answer the security question correctly to receive your lost password.</span>
	<?php } ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<div class="fieldGroup">
		<label>Question</label>
		<div class="field"><?php echo getSecurityQuestion($securityUser); ?>
		</div>
	</div>
	<div class="fieldGroup">
		<label for="answer">Answer</label>
		<div class="field">
			<input type="text" name="answer" id="answer" value="" maxlength="255">
		</div>
	</div>
        <input type="hidden" name="subStep" value="2" />
        <input type="hidden" name="userID" value="<?php echo $securityUser; ?>" />
	<div class="fieldGroup">
		<input type="submit" value="Submit" style="margin-left: 150px;" />
	</div>
        <div class="clear"></div>
    </form>
 
     <?php break; case 'userNotFound': ?>
	<br>
	<h2>Password Recovery</h2>
<br>
	<p>The username or email you entered was not found in our database.<br />
<br />
<a href="?">Click here</a> to try again.</p>
<br>    <?php break; case 'successPage': ?><br>
<h2>Password Recovery</h2>
<br>
<p>An email has been sent to you with instructions on how to reset your password.
<br />
<br />
<a href="index.php">Return</a> to the login page. </p>
<br>
<div class="message">Please click on the link in the email you receive.</div>
<br>
<?php break;
case 'recoverForm': ?>
    <h2>Password Recovery</h2>
    <p>Welcome back, <?php echo getUserName($securityUser=='' ? $_POST['userID'] : $securityUser); ?>.</p>
    <p>In the fields below, enter your new password.</p>
<?php if ($error == true) { ?>
	<span class="error">The new passwords must match and must not be empty.</span>
<?php } ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<div class="fieldGroup">
		<label for="pw0">New Password</label>
		<div class="field">
		<input type="password" class="input" name="pw0" id="pw0" value="" maxlength="20">
		</div>
	</div>
	<div class="fieldGroup">
		<label for="pw1">Confirm Password</label>
		<div class="field">
		<input type="password" class="input" name="pw1" id="pw1" value="" maxlength="20">
		</div>
	</div>
        <input type="hidden" name="subStep" value="3" />
        <input type="hidden" name="userID" value="<?php echo $securityUser=='' ? $_POST['userID'] : $securityUser; ?>" />
        <input type="hidden" name="key" value="<?php echo $_GET['email']=='' ? $_POST['key'] : $_GET['email']; ?>" />
	<div class="fieldGroup">
		<input type="submit" value="Submit" style="margin-left: 150px;" />
	</div>
        <div class="clear"></div>
    </form>
    <?php break;
case 'invalidKey': ?>
    <h2>Invalid Key</h2>
    <p>The key that you entered was invalid. Either you did not copy the entire key from the email, you are trying to use the key after it has expired (3 days after request), or you have already used the key in which case it is deactivated.<br />
<br /><a href="index.php">Return</a> to the login page. </p>
    <?php break;
case 'recoverSuccess': ?>
    <h2>Password Reset</h2>
    <p>Congratulations! your password has been reset successfully.</p>
	<br />
	<br /><a href="index.php">Return</a> to the login page. </p>
    <?php break;
case 'speedLimit': ?>
    <h2>Warning</h2>
    <p>You have answered the security question wrong too many times. You will be locked out for 15 minutes, after which you can try again.</p><br />
	<br /><a href="index.php">Return</a> to the login page. </p>
    <?php break; }
    ob_flush();
?>
	<div class="error"></div>

            <div class="push"></div>
            </div>
            <div class="footer">
	    <p>&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</p>
            </div>

</body>
</html>
