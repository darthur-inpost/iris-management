<?php

require_once('config.php');
require_once('includes/database.php');
require_once('RestApi.php');

session_cache_expire( $cache_expire );
session_start();

if (isset($_SESSION['password']))
{
	$username     = $_SESSION['username'];
	$password     = $_SESSION['password'];
	$current_ip_address = $_SESSION['ip_address'];

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
	return;
}

// Get the data we need to display for the user.
// connect to database
// NB the database helper automatically outputs using 'die' so we don't have to
// do this explicitly.
tep_db_connect();

/*
$unprocessed = tep_db_query("SELECT COUNT(order_num) FROM imported_orders WHERE status='UNPAID' AND perm_token='$token'");

$row = tep_db_fetch_array($unprocessed);
$total_unprocessed = $row[0]; 

$processed = tep_db_query("SELECT COUNT(order_num) FROM imported_orders WHERE status='PAID' AND perm_token='$token'");

$row = tep_db_fetch_array($processed);
$total_processed = $row[0];

$dispatched = tep_db_query("SELECT COUNT(order_num) FROM imported_orders WHERE status='DISPATCHED' AND perm_token='$token'");
                  
$row = tep_db_fetch_array($dispatched);
$total_dispatched = $row[0];
*/

$users = tep_db_query("SELECT * FROM users WHERE username='$username'");

while ($row = tep_db_fetch_array($users))
{
	$last_login      = $row['last_login'];
	$ip_address      = $row['ip_address'];
	$api_key         = $row['api_key'];
	$last_ip_address = long2ip($ip_address);

	$_SESSION['api_key'] = $api_key;
}

///
// count_replies
//
function count_replies($data)
{
	if(strlen($data) < 4)
	{
		return 0;
	}

	$temp = json_decode($data);

	return count($temp);
}

// Test how to get back some data.
// The previous day.
$params['url']        = 'http://api-uk.easypack24.net/parcels';
$params['token']      = $api_key;
$params['methodType'] = 'GET';
$params['params']['start_date'] = date('Y-m-d');
$params['params']['end_date']   = date('Y-m-d');

$rest_api = new RestApi($params);

$reply = $rest_api->getResponse();

$normal_previous_day = count_replies($reply);

// The last month.
$params['params']['start_date'] = date('Y-m-d', mktime(0, 0, 0, date('m') - 1,
					date('d'), date('Y') ));
$params['params']['end_date']   = date('Y-m-d');

$rest_api = new RestApi($params);

$reply = $rest_api->getResponse();

$normal_previous_month = count_replies($reply);

// The last year.
$params['params']['start_date'] = date('Y-m-d', mktime(0, 0, 0, date('m'),
					date('d'), date('Y') - 1 ));
$params['params']['end_date']   = date('Y-m-d');

$rest_api = new RestApi($params);

$reply = $rest_api->getResponse();

$normal_previous_year = count_replies($reply);

// Try and get the list of return parcels that the API key has been used to
// create.
// Start with the current day.
$params['url']        = 'http://api-uk.easypack24.net/reverselogistics.json';
$params['token']      = $api_key;
$params['methodType'] = 'GET';
$params['params']['date_before'] = date('Y-m-d');
$params['params']['date_after']  = date('Y-m-d');

$rest_api = new RestApi($params);

$reply = $rest_api->getResponse();

$returns_today = json_decode($reply);

// Get the count for the previous month
$params['params']['date_after'] = date('Y-m-d', mktime(0, 0, 0, date('m') - 1, 
		date('d'), date('Y') ));
$params['params']['date_before']  = date('Y-m-d');

$rest_api = new RestApi($params);

$reply = $rest_api->getResponse();

$returns_month = json_decode($reply);

// Get the count for the previous year
$params['params']['date_after'] = date('Y-m-d', mktime(0, 0, 0, date('m'), 
		date('d'), date('Y') - 1 ));
$params['params']['date_before']  = date('Y-m-d');

$rest_api = new RestApi($params);

$reply = $rest_api->getResponse();

$returns_year = json_decode($reply);

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
   
</head>
<body>
<div class="wrapper">
<?php
// Save having to edit code in multiple places.
include 'includes/menu.php';
?>

         <table width="40%" style=' margin-left: auto; margin-right:auto; margin-top: 80px;'>

	       <thead>
		<tr>
			<td colspan="2" style="text-align:center; background: #87907D; "><span style="color: white"><h3>Summary</h3></span>
			</td>
		</tr>
		</thead>
            <tbody style='font-size: 12px; padding:2px; color: #669;'>
               <tr style='background-color: #e8edff'>
                <td>Last Login</td>
                <td><?php echo $last_login; ?></td>
                </tr>
                
                 <tr style='background-color: #e8edff'>
                <td>IP Address for Last Login</td>
                <td><?php echo $last_ip_address; ?></td>
                </tr>
                
                <tr style='background-color: #e8edff'>
                <td>Current IP Address</td>
                <td><?php echo $current_ip_address; ?></td>
                </tr>
                
                <tr style='background-color: #e8edff'>
                <td width='300'>Parcels Today</td>
                <td><?php echo $normal_previous_day; ?></td>
                </tr>
                <tr style='background-color: #e8edff'>
                <td width='300'>Parcels Previous Month</td>
                <td><?php echo $normal_previous_month; ?></td>
                </tr>
                <tr style='background-color: #e8edff'>
                <td width='300'>Parcels Previous Year</td>
                <td><?php echo $normal_previous_year; ?></td>
                </tr>

                <tr style='background-color: #e8edff'>
                <td width='300'>Returns Today</td>
                <td><?php echo $returns_today->_meta->total_count; ?></td>
                </tr>
                
                <tr style='background-color: #e8edff'>
                <td>Returns Previous Month</td>
                <td><?php echo $returns_month->_meta->total_count; ?></td>
                </tr>
                <tr style='background-color: #e8edff'>
                <td>Returns Previous Year</td>
                <td><?php echo $returns_year->_meta->total_count; ?></td>
                </tr>
                
               <tr style='border-top: 4px solid white;'><td colspan='2' style='background: #87907D;' ></td></tr>
            </tbody>
         </table>

            <div class="push"></div>
            </div>
            <div class="footer">
	    <p>&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</p>
            </div>
</body>
</html>
