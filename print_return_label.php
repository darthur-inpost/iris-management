<?php
require_once('config.php');
require_once('includes/database.php');
require_once('RestApi.php');

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

// Set the variables to NULL to allow for checking later.
$return_code = null;
$parcels     = null;

if(isset($_POST['return_code']))
{
	$return_code = $_POST['return_code'];
}
if(isset($_POST['parcel_c']))
{
	$parcels = $_POST['parcel_c'];
}

	$params = array(
                'token'      => $api_key,
                'methodType' => 'GET',
		'params'     => array(
			'format' => 'Pdf',
			'type'   => 'normal'
		)
	);

	if($return_code != null)
	{
		$params['url'] = $base_url . 'reverselogistics/' .
			$return_code . '/label.json';
	}
	else
	{
		if(count($parcels) > 1)
		{
			$ret = print_many_labels($params, $parcels, $username);
			update_parcel_status($ret);
			header("Location: r_orders.php");
			return;
		}
		else
		{
			$params['url'] = $base_url . 'reverselogistics/' .
				$parcels[0] . '/label.json';
		}
	}

	$restApi2 = new RestApi($params);

	$timestamp = date('dmy_His', time());
	$file_name = "InPost_Return_Label" . $timestamp . ".pdf";

	$pdf  = $restApi2->getResponse();
	$info = $restApi2->getInfo();
	//echo '<pre>Response = ';
	//echo print_r($pdf);
	//echo '</pre>';
	//echo '<pre>Info = ';
	//echo print_r($info);
	//echo '</pre>';

	if($info['http_code'] != 200)
	{
		$_SESSION['error_message'] = 'Failed to create your labels ' .
			$info["http_code"] . ' ' . $pdf;
		header("Location: r_orders.php");
		return;
	}

	update_parcel_status($parcels);

	$binary = base64_decode($pdf);

	header('Content-type: application/pdf');
	header("Content-Disposition:attachment; filename=$file_name");

	$tmpfname = "tmp" . session_id() . "fred.pdf";

$handle = fopen($tmpfname, "wb");
fwrite($handle, $binary);
fclose($handle);

// do here something
	header("location: $tmpfname");

//unlink($tmpfname);
//echo $binary;

	header("Refresh:0; url=http://inpost247.uk/b2a/r_orders.php");
	return;
///
// print_many_labels
//
function print_many_labels(&$params, &$parcels, $username)
{
	require("config.php");

	$return = array();

	$zipname = "test_" . $username . '_' . session_id() . ".zip";

	$zip = new ZipArchive;
	$res = $zip->open($zipname, ZipArchive::CREATE);

	if ($res === TRUE)
	{
		$timestamp = date('dmy_His', time());

		foreach($parcels as $key => $parcel)
		{
			$params['url'] = $base_url . 'reverselogistics/' .
				$parcel . '/label.json';

			//echo json_encode($params);

			$restApi2 = new RestApi($params);

			$file_name = "InPost_Return_Label" . $timestamp .
				$key . ".pdf";

			$pdf  = $restApi2->getResponse();
			$info = $restApi2->getInfo();

			if($info['http_code'] != 200)
			{
				$_SESSION['error_message'] = 'Failed to create your labels ' .
				$info["http_code"];
				continue;
			}

			$binary = base64_decode($pdf);

			// Add to the list of parcels that need their status
			// updated.
			$return[] = $parcel;

			// Add the PDF to the main ZIP file.
    			$zip->addFromString($file_name, $binary);
		}

    		$zip->close();
	}
	else
	{
		$_SESSION['error_message'] = 'Failed to create ZIP file for your labels.';
		header("Location: r_orders.php");
		return $return;
	}

	return $return;
}

///
// update_parcel_status
//
// @param array of parcel codes to be updated.
//
function update_parcel_status($parcels)
{
	require('config.php');

	if(count($parcels) > 0)
	{
		tep_db_connect();
	}
	else
	{
		return;
	}

	$the_date = date('Y-m-d H:i:s');

	foreach($parcels as $parcel)
	{
		tep_db_query("UPDATE $table_prefix$return_table SET label_printed=1, dispatch_date='$the_date' WHERE code='$parcel'");
	}
}
?>
<html>
<head>
<script type="text/javascript">
function Redirect()
{
	window.location="r_orders.php";
}

</script>
</head>
<body>
<script type="text/javascript">
setTimeout('Redirect()', 3000);
</script>
</body>
</html>
