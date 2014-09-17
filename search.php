<?php

require_once('config.php');
require_once('includes/database.php');

session_cache_expire( $cache_expire );
session_start();


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
else
{
	header("Location: 404_error.php");
}

?>
<!DOCTYPE HTML>
<html>
<head profile="http://www.w3.org/2005/10/profile">
<link rel="icon" type="image/png" href="images/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>InPost 24/7 Parcel Lockers</title>
<link rel="stylesheet" type="text/css" href="css/main_ss.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
<script>
$(function() {
$( "#tabs" ).tabs();
});
</script>
<script type="text/javascript">
function checkPass()
{
	var input = document.getElementById('search');
	if(input.value==="")
		document.getElementById("submit").disabled = true;
	else
		document.getElementById("submit").disabled = false;
}
</script>

</head>
<body>
<div class="wrapper">
<?php include_once('includes/menu.php'); ?>

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
<br>
<br>
<div id="tabs">
<ul>
<li><a href="#tabs-1">Delivery Parcels</a></li>
<li><a href="#tabs-2">Returns Parcels</a></li>
</ul>
<div id="tabs-1">
	<p>Please Enter Search Terms</p>
	<form method="POST" action="s_results.php">
	<table border="0" cellspacing="5" align="center">
	<tr>
		<td><label for="c_number">Consignment Number</label></td>
		<td><input type="text" autocomplete="off" name="c_number" id="c_number"></td>
	</tr>
	<tr>
		<td colspan="2" style="text-align:center;"><input type="submit" size="6" value="Search" id='submit'></td>
	</tr>

        </table>

	<input type="hidden" name="form_type" id="form_type" value="delivery" />
        </form>
</div>
<div id="tabs-2">
	<p>Please Enter Search Terms</p>
<form method="post" action="s_results.php">
	<table>
	<tr>
		<td><label for="rr_code">R.R. Code</label></td>
		<td><input type="text" autocomplete="off" name="rr_code" id="rr_code"></td>
	</tr>
	<tr>
		<td colspan="2" style="text-align:center;"><input type="submit" size="6" value="Search" id='returns_submit'></td>
	</tr>
	</table>
	<input type="hidden" name="form_type" id="form_type" value="returns" />
</form>
</div>
</div> <!-- Tabs -->

    </div>

	<div class="push"></div>
	</div>
	<div class="footer">
	<p>&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</p>
	</div>

</body>
</html>
