<?php

//require config files
require_once('config.php');
require_once('includes/database.php');
require_once('RestApi.php');

$parcels = $_POST['parcel_c'];

session_cache_expire( $cache_expire );
session_start();

// Set the timezone
// Europe/London
date_default_timezone_set('Europe/London');

//retrieve values for session variables
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

	$con = tep_db_connect();
 
	if (!$con)
	{
		die('Could not connect: ' . mysqli_error());
	}

//var_dump($parcels);
$count = count($parcels);

$to_print_array = array();
$message        = array();

// We now have the situation that both Paid and Unpaid lines will come through.
// So we must pay for some.
foreach($parcels as $parcel)
{
	$query = tep_db_query("SELECT * from $table_prefix$parcel_table where parcel_id='$parcel'");

	$ret = tep_db_fetch_array($query);

	if($ret['status'] == 'UNPAID')
	{
		// Pay for the parcel.
		$params = array(
			'url'        => $base_url . 'parcels/' .
						$ret['parcel_id'] .
						'/pay',
			'token'      => $api_key,
			'methodType' => 'POST',
			'params'     => array()
		);

		$rest = new RestApi($params);

		$info  = $rest->getInfo();
		$reply = $rest->getResponse();

		if($info['http_code'] != 204)
		{
			$message[] = "Parcel Payment failed for parcel $parcel<br>Error Code: " .
				$info["http_code"] . " " .
				$reply;

			continue;
		}

		tep_db_query("UPDATE $table_prefix$parcel_table set status='PAID' WHERE parcel_id='$parcel'");

		$to_print_array[] = $parcel;
	}
	elseif($ret['status'] == 'PAID')
	{
		$to_print_array[] = $parcel;
	}
}

//echo $count;
if(count($to_print_array) == 0)
{
	// Simply return. We have no labels to print
	header('Location: c_orders.php');
	return;
}

$parcels_st = implode(";",$to_print_array);
//echo $parcels_st;

	// Get the user's label format
	$ret = tep_db_query("SELECT label_format from " . $table_prefix .
		"users where username='$username'");

	$result = tep_db_fetch_array($ret);

	$label_format = $result['label_format'];

        $dispatch_date = date('Y-m-d H:i:s');

	foreach($parcels as $pid)
	{
		$query = tep_db_query("UPDATE imported_orders SET status='DISPATCHED', dispatch_date='$dispatch_date' WHERE parcel_id='$pid' AND perm_token='$api_key'");
	}

//retrieve stickers

$restApi = new RestApi(array(
    'url'        => $base_url . 'stickers/' . $parcels_st,
    'token'      => $api_key,
    'methodType' => 'GET',
    'params'     => array(
        'format' => $label_format,
        'type'   => 'normal'
    )
));

date_default_timezone_set('Europe/London');
$timestamp = date('dmy_His', time());
if($label_format == 'Pdf')
{
	$file_name = "InPost_Labels_" . $timestamp . ".pdf";
}
else
{
	$file_name = "InPost_Labels_" . $timestamp . ".epl";
}

$pdf    = $restApi->getResponse();
$binary = base64_decode($pdf);

header('Content-type: application/pdf');
header("Content-Disposition:attachment; filename=$file_name");

echo $binary;
?>
