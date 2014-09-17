<?php
require_once('config.php');
require_once('includes/database.php');

session_cache_expire( $cache_expire );
session_start();

if (isset($_SESSION['password']) && isset($_GET['order_num']))
{
	$password  = $_SESSION['password'];
	$username  = $_SESSION['username'];
	$api_key   = $_SESSION['api_key'];
	$order_num = $_GET['order_num'];  

    $session_life = time() - $_SESSION['start'];

    if($session_life > $inactive)
    {
        header("Location: logout.php");
    }
    $_SESSION['start'] = time();

    if(($order_num == NULL) || ($order_num == ''))
    {
        header("Location: notfound.php");
    }
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

if(isset($_GET['id']) && $_GET['id'] == 'true')
{
	$dispatched_parcels = tep_db_query("SELECT * FROM $table_prefix$parcel_table WHERE perm_token='$api_key' AND id='$order_num' ");
}
else
{
	$dispatched_parcels = tep_db_query("SELECT * FROM $table_prefix$parcel_table WHERE perm_token='$api_key' AND order_num='$order_num' ");
}

$row = tep_db_fetch_array($dispatched_parcels); 
//echo print_r($row);

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
<?php include_once('includes/menu.php'); ?>

         <table width="58%" style=' text-align:justify; margin-left: auto; margin-right:auto; margin-top: 40px; '>
             <?php
             
                 $c_datetime  = $row['creation_date'];
                 $c_timestamp = strtotime($c_datetime);
                 $c_date      = date('Y-m-d', $c_timestamp);
                 
                 $ip_ref      = $row[2];
                 $cl_ref      = substr($ip_ref, 0, 8);

                  echo "<thead> <tr><td colspan='2' style='text-align:center; background: #87907D;'><span style='color: white'><h3>Parcel Details</h3></span></td></tr></thead>";
                  echo "<tbody style='padding:3px,3px,3px,3px; font-size: 12px; padding:2px; color: #669;'>";
      
                  echo "<tr style='background-color: #e8edff'><td width='25%' >Order Number</td><td width='75%' >". $row['order_num']. "</td></tr>";
                  
                  if($row['parcel_id']=="")
		  {
			  echo "<tr style='background-color: #e8edff'><td>InPost Reference #</td><td>NOT APPLICABLE</td></tr>";    
		  }
                  else
		  {
			  echo "<tr style='background-color: #e8edff'><td>InPost Reference #</td><td>". $row['parcel_id']. "</td></tr>";
		  }
                  
                  
                  
                  if($row['parcel_size'] == "-")
		  {
			  echo "<tr style='background-color: #e8edff'><td>Parcel Size</td><td>-</td></tr>";    
		  }
                  else
		  {
			  echo "<tr style='background-color: #e8edff'><td>Parcel Size</td><td>". $row['parcel_size']. "</td></tr>";
		  }
                  
                  echo "<tr style='background-color: #e8edff'><td>Customer's Email</td><td>". $row['email']. "</td></tr>";
                  echo "<tr style='background-color: #e8edff'><td>Customer's Mobile</td><td>+447". $row['mobile']. "</td></tr>";
                  echo "<tr style='background-color: #e8edff'><td>Status</td><td>". $row['status']. "</td></tr>";
               
                  if($row['order_date']=="0000-00-00 00:00:00")
		  {
			  echo "<tr style='background-color: #e8edff'><td>Parcel Created</td><td>NOT CREATED</td></tr>";
		  }
                  else
		  {
			  echo "<tr style='background-color: #e8edff'><td>Parcel Created</td><td>". $row['creation_date']. "</td></tr>";
		  }
                  
                  if($row['dispatch_date']=="0000-00-00 00:00:00")
		  {
			  echo "<tr style='background-color: #e8edff'><td>Parcel Dispatched </td><td>NOT DISPATCHED</td></tr>";
		  }
                  else
		  {
			  echo "<tr style='background-color: #e8edff'><td>Parcel Dispatched </td><td>". $row['dispatch_date']. "</td></tr>";
		  }

                  echo "<tr style='background-color: #e8edff'><td>Destination Terminal</td><td>". $row['d_terminal']. "</td></tr>";
                  echo "<tr style='background-color: #e8edff'><td>Destination Town/City</td><td>". $row['d_city']. "</td></tr>";
                  echo "<tr style='background-color: #e8edff'><td>Return Code</td><td>". $row['rl_code']. "</td></tr>";

                  echo "<tr style='background-color: #e8edff'><td style='text-align:center;' colspan='2'>Sender Details</td></tr>";
                  echo "<tr style='background-color: #e8edff'><td>Company Name</td><td>". $row['company_name']. "</td></tr>";
                  echo "<tr style='background-color: #e8edff'><td>First Name</td><td>". $row['first_name']. "</td></tr>";
                  echo "<tr style='background-color: #e8edff'><td>Last Name</td><td>". $row['last_name']. "</td></tr>";
		  echo "<tr style='background-color: #e8edff'><td>Address</td><td>" .
			  $row['building_number'] . ', ' .
			  $row['street'] . ', ' .
			  $row['town'] . ', ' .
			  $row['post_code'] .
			  "</td></tr>";
                  
                  if($row['dispatch_date']=="0000-00-00 00:00:00")
		  {
			  echo "<tr style='background-color: #e8edff'><td>Tracking Link</td><td>NOT AVAILABLE</td></tr>";
		  }
                  else
		  {
			  echo "<tr style='background-color: #e8edff'><td>Tracking Link</td><td><a href='http://www.city-link.co.uk/receiving-a-parcel/tracking/clan/" .$cl_ref ."/".$c_date ."' target='_blank'> http://www.city-link.co.uk/receiving-a-parcel/tracking/clan/".$cl_ref ."/".$c_date. "</a></td></tr>";
		  }
             ?>

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
