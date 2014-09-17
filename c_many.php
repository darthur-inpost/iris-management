<?php
//
// This file allows the user to create multiple parcels.
//
require_once('config.php');
require_once('includes/database.php');
require_once('RestApi.php');

// Set the timezone
// Europe/London
date_default_timezone_set('Europe/London');

session_start();
session_cache_expire( $cache_expire );

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

if(isset($_POST['subStep']))
{
	//echo "Processing starting.<br>";
	//echo '<pre>';
	//echo print_r($_POST);
	//echo '</pre>';
	//echo "Processing file?<br>";
	//echo '<pre>';
	//echo print_r($_FILES);
	//echo '</pre>';

	// check the data sent back is valid.
	$ret = preg_match("/^.*(([^\.][\.][cC][sS][vV])|([^\.][\.][tT][xX][tT]))$/", $_FILES['fname']['name']);

	if($ret != true)
	{
		$_SESSION['error_message'] = "The file name can only be a TXT or CSV type file.";
		session_write_close();
		header("Location: c_many.php");
		return;
	}

	// Now try and get the file uploaded.
	$file = fopen($_FILES['fname']['tmp_name'], "rt");

	if($file == null)
	{
		$_SESSION['error_message'] = "The file would not open.";
		session_write_close();
		header("Location: c_many.php");
		return;
	}

	$row = 0;

	// Count the number of CSV lines in the file
	while(($line = fgetcsv($file)) !== false)
	{
		$row++;
	}
	if($row > 500)
	{
		// There are too many rows.
		$_SESSION['error_message'] = "The file is rejected as it has too many lines.<br>Maximum number of lines is 500.";
		session_write_close();
		header("Location: c_many.php");

		fclose($file);
		return;
	}

	$total_rows     = $row;
	$row            = 0;
	$processed_rows = 0;
	$error_rows     = 0;

	// Move the file pointer back to the begining
	rewind($file);

	// Open a database connection
	tep_db_connect();

	// Will be returned for user to ensure data is processed.
	$message = array();

	//--------------------------------------------------------------------
	// CSV structure 
	//--------------
	// min 5 fields  - description, email, mobile, size, target mnachine
	// next 5 fields - fname, lname, postcode, town, street
	// next 3 fields - company name, building number, flat number
	//--------------------------------------------------------------------

	// Try and read the data out.
	while(($line = fgetcsv($file)) !== false)
	{
		// Check to see if the count of columns is correct
		$num = count($line);

		if($num != 5 && $num != 10 && $num != 11 && $num != 12 && $num != 13)
		{
			// The count is wrong for this line, reject it.
			$message[] = "Line $row rejected with $num columns";
			$row++;
			continue;
		}

		// Build the data structure for the REST API call.
		$params = array(
			'url'            => $base_url . 'parcels',
			'token'          => $api_key,
			'methodType'     => 'POST',
			'params'         => array(
				'description'    => $line[0],
				'receiver'       => array(
					'email'  => $line[1],
					'phone'  => $line[2]
				),
				'size'           => $line[3],
				'tmp_id'         => RestApi::generate(4,15),
				'target_machine' => $line[4]
			)
		);

		if($num >= 10)
		{
			$params['sender_address'] = array(
				'first_name' => $line[5],
				'last_name'  => $line[6],
				'post_code'  => $line[7],
				'town'       => $line[8],
				'street'     => $line[9]
			);
		}
		if($num >= 11)
		{
			$params['sender_address']['company_name'] = $line[10];
		}
		if($num >= 12)
		{
			$params['sender_address']['building_number'] = $line[11];
		}
		if($num == 13)
		{
			$params['sender_address']['flat_number'] = $line[12];
		}

		//echo print_r($params) . '<br>';

		$ret = new RestApi($params);

		$info_arr = $ret->getInfo();

		if($info_arr['http_code'] != 201)
		{
			$err = $ret->getResponse();
			$message[] = "Parcel Create failed for line $row <br>Error Code: " .
				$info_arr["http_code"] . " " .
				$err;
			$error_rows++;
			$row++;
			continue;
		}

		$response = json_decode($ret->getResponse());
		$parcel_id = $response->id;

		// Add the parcel to the log table.
		$params['parcel_id'] = $parcel_id;

		add_parcel($params, $num);

		$processed_rows++;
		$row++;
	}

	fclose($file);

	// Set up the message to tell the user various statistics.
	$message[] = "-Completed- Processed $row records of $total_rows.<br>Errors: $error_rows. Processed Succesfully: $processed_rows.";

	$_SESSION['error_list'] = $message;
	session_write_close();
	header("Location: c_many.php");

}

$con = tep_db_connect();

if (!$con)
{
	die('Could not connect: ' . mysqli_error());
}

?>
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
        
<script type="text/javascript">
	// Check if the File Name matches the regular expression.
	jQuery.validator.addMethod("check_file_name", function(value)
	{
		var PE = /^.*(([^\.][\.][cC][sS][vV])|([^\.][\.][tT][xX][tT]))$/;
		if(!PE.test(value))
		{
			return false;
		}
		return true;
	}, "The file name must be either TXT or CSV.");


jQuery(document).ready(function() {
	// Validation is based on class and type fields.
	jQuery("#pp_uform").validate({
		errorLabelContainer: jQuery("#pp_form td.error"),
		rules: {
			fname: "check_file_name"
		},
		messages: {
			fname : {
				required: "You must select a file",
				minlength: jQuery.validator.format("Enter at least {0} characters")
			}
		}

	});
});
</script>
</head>
<body>
<div class="wrapper">
<?php
// Save having to edit code in multiple places.
include 'includes/menu.php';
?>
<div style="text-align:center;">
<?php
	// Output an error messages we get.
	if(isset($_SESSION['error_message']))
	{
		echo '<p style="color:red;">' . $_SESSION['error_message'] .
			'</p>';
	}
	unset($_SESSION['error_message']);
?>
<?php
	// Output an error messages we get.
	if(isset($_SESSION['error_list']))
	{
		echo '<p style="color:red;">';

		foreach($_SESSION['error_list'] as $value)
		{
			echo $value . '<br>';
		}
		echo '</p>';
	}
	unset($_SESSION['error_list']);
?>

<form id='pp_form' name='pp_form' action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post' enctype="multipart/form-data">

<table style="margin-left:auto; margin-right:auto;" width="80%" border="0" cellspacing="0" cellpadding="0">
                
	<thead>
	<tr>
		<td colspan="1" style="text-align:center;">
		<h3>Create Multiple, Address To Locker, Parcels</h3>
		Please note that only TXT and CSV files of 500 lines or less will be processed.
		</td>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
			<label for="fname">File Name: </label>
			<input type="file" name="fname" id="fname" minlength="5" required>
		</td>
	</tr>
	<tr>
		<td class="error">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="1">
			<!--<button name="submit" id="submit" type="submit">Submit</button>-->
			<input name="submit" id="submit" type="submit" value="Submit">
		</td>
	</tr>
	</tbody>
</table>

	<input type="hidden" name="subStep" value="1" />

</form>
</div>

            <div class="push"></div>
            </div>
            <div class="footer">
	    <p>&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</p>
            </div>
</body>
</html>
