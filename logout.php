<?php
session_start();

require_once('config.php');
require_once('includes/database.php');

function destroy_session_and_data()
{
    $_SESSION = array();

    if (session_id() != "" || isset($_COOKIE[session_name()]))
        setcookie(session_name(), '', time() - 2592000, '/');
    session_destroy();
}

if (isset($_SESSION['username']))
{
	//echo "You have been logged out!";
	if (isset($_SESSION['last_login']))
	{
		$last_login = $_SESSION['last_login']; 
		$ip_address = $_SESSION['ip_address'];

		$username   = $_SESSION['username'];
		$con        = tep_db_connect();

		if (!$con)
		{
			global $$link;

			destroy_session_and_data(); 
			tep_db_error('Could not connect', mysqli_errno($$link), mysqli_error($$link));
		}

		$query = tep_db_query("UPDATE users SET last_login='$last_login' WHERE username='$username'");
		if (!$query)
		{
			destroy_session_and_data();   
			die('Error: ' . mysql_error());
		}
		$query = tep_db_query("UPDATE users SET ip_address= INET_ATON('$ip_address') WHERE username='$username'");

		if (!$query)
		{
			destroy_session_and_data();   
			die('Error: ' . mysql_error());
		}              
	}

	destroy_session_and_data();
}
?>

<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://www.w3.org/2005/10/profile">
<link rel="icon" type="image/png" href="images/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>InPost 24/7 Parcel Lockers</title>
<link rel="stylesheet" type="text/css" href="css/main_ss.css">
</head>
 
<body>
<div class="wrapper">
<div id="header"><img src="images/logo.png"/></div>

<div class="main_menu">
<h3><p style="text-align:center;">InPost 24/7</p></h3>
</div>

<div align="center" style="position: relative; top:75px; margin-left:auto; margin-right:auto;">
    
    <p style="color: #990000">
        You have been logged out!
  
    </p>
</div>
 
</div>
<div class="footer">

&copy; <?php echo date('Y'); ?> InPost UK Ltd  - All rights reserved.
</div> <!-- end of footer -->
</body>
 
 
 
</html>


 
