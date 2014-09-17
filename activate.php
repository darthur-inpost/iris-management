<?php
require_once('config.php');
require_once('includes/database.php');
require_once('RestApi.php');

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
// Set the timezone
// Europe/London
date_default_timezone_set('Europe/London');

$rma_code    = $_POST['rma_code'];
$expiry_date = $_POST['expiry_date'];
$email       = $_POST['email'];
$add_desc1   = $_POST['add_desc1'];
$add_desc2   = $_POST['add_desc2'];
$add_desc3   = $_POST['add_desc3'];
$mobile      = $_POST['mobile'];
$fname       = $_POST['fname'];
$lname       = $_POST['lname'];
$cname       = $_POST['cname'];
$rmobile     = $_POST['r_mobile'];
$size        = $_POST['size'];
$street      = $_POST['street'];
$building    = $_POST['building'];
$county      = $_POST['county'];
$postcode    = $_POST['postcode'];
$town        = $_POST['town'];

if(strlen($mobile) != 10)
{
	$_SESSION['error_message'] = 'The sender mobile number must be ten (10) digits long.';
	session_write_close();
	header("Location: c_return.php");
	return;
}

// Save the data entered into the database
$con = tep_db_connect();
if(!$con)
{
	die('Failed to connect to the database ' . mysqli_error());
}

// Check to see if the RMA has already been used by the customer
if($rma_code != '')
{
	$ret = tep_db_query("SELECT * from $table_prefix$return_table WHERE api_key='$api_key' and rma='$rma_code'");

	$result = tep_db_fetch_array($ret);

	if(count($result) > 0)
	{
		// The RMA is already there.
		tep_db_close();
		$_SESSION['error_message'] = 'The RMA is already present. Please pick a different one.';
		session_write_close();
		header("Location: c_return.php");
		return;
	}
}

	$the_date = date('Y-m-d H:i:s');
	$ret = tep_db_query("INSERT into $table_prefix$return_table (api_key, rma, parcel_size, expire_at, sender_phone, sender_email, add_desc1, add_desc2, add_desc3, first_name, last_name, company_name, post_code, town, street, building_number, flat_number, province, phone, created_date) VALUES ('$api_key', '$rma_code', '$size', '$expiry_date', '$mobile', '$email', '$add_desc1', '$add_desc2', '$add_desc3', '$fname', '$lname', '$cname', '$postcode', '$town', '$street', '$building', '', '$county', '$rmobile', '$the_date')");

	$insert_id = tep_db_insert_id();
?>

<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://www.w3.org/2005/10/profile">
<link rel="icon" type="image/png" href="images/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>InPost 24/7 - User Portal</title>
<link rel="stylesheet" type="text/css" href="css/main_ss.css">
</head>
 
<body>

<div class="wrapper">
<?php include_once('includes/menu.php'); ?>

<div align="center" style="position: relative; top:75px; margin-left:auto; margin-right:auto;">

<?php

//Generate an active return code
	$params = array(
            'url'        => $base_url . 'reverselogistics.json',
            'token'      => $api_key,
            'methodType' => 'POST',
            'params'     => array(
                'rma'          => $rma_code,
                'parcel_size'  => $size,
                'expire_at'    => $expiry_date,
                'sender_phone' => $mobile,
                'sender_email' => $email,
                'with_label'   => 'TRUE',
                'additional_description_1' => $add_desc1,
                'additional_description_2' => $add_desc2,
                'additional_description_3' => $add_desc3,
                'address'      => array(
                    'first_name'        => $fname,
                    'last_name'         => $lname,
                    'company_name'      => $cname,
                    'post_code'         => $postcode,
                    'town'              => $town,
                    'street'            => $street,
                    'building_number'   => $building,
                    'flat_number'       => '',
                    'province'          => $county,
                    'phone'             => $rmobile,
                 )
		)
	);

	//echo 'sent paramsters ' . json_encode($params) . '<br>';

	$restApi = new RestApi($params);

	$request = $restApi->getInfo();
	//echo 'request ' . print_r($request) . '<br>';

	$response = json_decode(($restApi->getResponse()), true);
	$info_arr = $restApi->getInfo();

	//echo '<pre>';
	//echo '<br>response ' . print_r($response);
	//echo '</pre>';
	//echo '<pre>';
	//echo '<br>info_arr ' . print_r($info_arr);
	//echo '</pre>';
	//echo 'getErrno ' . $restApi->getErrno() . '<br>';
	//echo 'getError ' . $restApi->getError() . '<br>';

	if($info_arr["http_code"] == 200)
	{
		$ex_date  = $response['expire_at'];  
		$exp_date = substr($ex_date, 0,10); //extract expiry date
  
		//a new return code's been created?
		if (array_key_exists("is_active", $response))
		{
			$return_code = $response['code'];
              
			if($response['is_active'] == '1')
			{
				// Update with the response.
				$ret = tep_db_query("UPDATE $table_prefix$return_table SET code='$return_code', actual_expire='$exp_date' WHERE id=$insert_id");

				echo "<p style='color: #990000'>Active Return Code:".$return_code ."</p><br><br>";
				echo "<p style='color: #990000'>Expires on:".$exp_date."</p><br><br>";
				echo "<form id='form2' name='pp_form' action='print_return_label.php' method='post'>";
				echo "<input type='hidden' name='return_code' id='return_code' value='" .$return_code. "'/>";
				echo "<input type='hidden' name='api_key' id='api_key' value='" .$api_key. "'/>";
				echo "<input type='submit' value='Print Return Label' /></form>";
			}
		}
          
		if (array_key_exists("DuplicateRma", $response))
		{
			echo "<p style='color: #990000'>".$response['DuplicateRma'] ."</p>";
		}
	}
	else
	{
		echo "<p style='color: #990000'>An Error Occured!</p>";
		echo "<p>HTTP Response Code: " . $info_arr["http_code"]. "</p>";
		echo '<p>Response: ' . json_encode($response) . '</p>';

		if($info_arr["http_code"] == "404")
		{
			echo "<p style='color: #990000'> Invalid Return Code!</p>";
		}
		//print_r($info_arr);
	}
?>
    
</div>
 
</div>
<div class="footer">

&copy; <?php echo date('Y'); ?> InPost UK Ltd  - All rights reserved.
</div> <!-- end of footer -->
</body>
</html>

