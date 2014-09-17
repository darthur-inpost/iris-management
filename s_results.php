<?php
require 'config.php';
require 'includes/database.php';

session_cache_expire( $cache_expiry );
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

///
// _filter_data_returns
//
function _filter_data_returns()
{
	// We have to get config as the values seem to be lost inside the
	// function.
	require 'config.php';

	// Build our query
	if(strlen($_POST['rr_code']) > 2 && strlen($_POST['postcode']) > 2)
	{
		$where = "code='" . $_POST['rr_code'] . "' AND ";
		$where .= "postcode LIKE '" . $_POST['postcode'] . "'";
	}
	elseif(strlen($_POST['rr_code']) > 2)
	{
		$where = "code='" . $_POST['rr_code'] . "'";
	}
	else
	{
		$where = "postcode LIKE '" . $_POST['postcode'] . "'";
	}

	$sql = "SELECT * from " . $table_prefix . $return_table . " WHERE $where";
	$ret = tep_db_query($sql);

	return($ret);
}

///
// _filter_data_delivery
//
function _filter_data_delivery()
{
	// We have to get config as the values seem to be lost inside the
	// function.
	require 'config.php';

	// Check if we have any data setup.
	if(strlen($_POST['r_email']) == 0 && strlen($_POST['c_number']) == 0 &&
		strlen($_POST['c_ref']) == 0)
	{
		$_SESSION['error_message'] = 'Please fill in a filter criteria.';
		header("Location: search.php");
		return;
	}

	// Build our query
	if(strlen($_POST['r_email']) > 2 && strlen($_POST['c_number']) > 2 &&
		strlen($_POST['c_ref']) > 2)
	{
		$where = "email='" . $_POST['r_email'] . "' AND ";
		$where .= "parcel_id='" . $_POST['c_number'] . "' AND ";
		$where .= "order_num='" . $_POST['c_ref'] . "'";
	}
	elseif(strlen($_POST['r_email']) > 2 &&
		strlen($_POST['c_number']) > 2 &&
		strlen($_POST['c_ref']) == 0)
	{
		$where = "email='" . $_POST['r_email'] . "' AND ";
		$where .= "parcel_id='" . $_POST['c_number'] . "'";
	}
	elseif(strlen($_POST['r_email']) > 2 &&
		strlen($_POST['c_number']) == 0 &&
		strlen($_POST['c_ref']) > 2)
	{
		$where = "email='" . $_POST['r_email'] . "' AND ";
		$where .= "order_num='" . $_POST['c_ref'] . "'";
	}
	elseif(strlen($_POST['r_email']) == 0 &&
		strlen($_POST['c_number']) > 2 &&
		strlen($_POST['c_ref']) > 2)
	{
		$where = "parcel_id='" . $_POST['c_number'] . "' AND ";
		$where .= "order_num='" . $_POST['c_ref'] . "'";
	}
	elseif(strlen($_POST['r_email']) == 0 &&
		strlen($_POST['c_number']) == 0 &&
		strlen($_POST['c_ref']) > 2)
	{
		$where = "order_num='" . $_POST['c_ref'] . "'";
	}
	elseif(strlen($_POST['r_email']) == 0 &&
		strlen($_POST['c_number']) > 2 &&
		strlen($_POST['c_ref']) == 0)
	{
		$where = "parcel_id='" . $_POST['c_number'] . "'";
	}
	elseif(strlen($_POST['r_email']) > 2 &&
		strlen($_POST['c_number']) == 0 &&
		strlen($_POST['c_ref']) == 0)
	{
		$where = "email='" . $_POST['r_email'] . "'";
	}

	$sql = "SELECT * from " . $table_prefix . $parcel_table . " WHERE $where";
	$ret = tep_db_query($sql);

	return($ret);
}

$con = tep_db_connect();

if (!$con)
{
	die('Could not connect: ' . mysqli_error());
}

if($_POST['form_type'] == 'delivery')
{
	$query = _filter_data_delivery();
}
else
{
	$query = _filter_data_returns();
}

if (tep_db_num_rows($query) == 0)
{
    $ind = 0;
    header("Location: notfound.php");
}
else
{
	if($_POST['form_type'] == 'delivery')
	{
		$row      = tep_db_fetch_array($query);
		$order_id = $row['id'];
		header("Location: parcel_details.php?order_num=$order_id&id=true");
		return;
	}

	// Get the data ready for the Return Parcel
	$row = tep_db_fetch_array($query);
}
 ?>
<!DOCTYPE HTML>
<html>
        <head profile="http://www.w3.org/2005/10/profile">
        <link rel="icon" type="image/png" href="images/favicon.ico" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>InPost 24/7 Parcel Lockers</title>
        <link rel="stylesheet" type="text/css" href="css/main_ss.css">
   
</head>
<body>
            <div class="wrapper">
<?php include_once('includes/menu.php'); ?>

         <table width="58%" style=' text-align:justify; margin-left: auto; margin-right:auto; margin-top: 40px; '>
           <?php
             
		$c_datetime  = $row['created_date'];
		$c_timestamp = strtotime($c_datetime);
		$c_date      = date('Y-m-d', $c_timestamp);

		$ip_ref      = $row[2];
		$cl_ref      = substr($ip_ref, 0, 8);

		echo "<thead> <tr><td colspan='2' style='text-align:center; background: #87907D;'><span style='color: white'><h3>Return Parcel Details</h3></span></td></tr></thead>";
		echo "<tbody style='padding:3px,3px,3px,3px; font-size: 12px; padding:2px; color: #669;'>";

		if($row['rma'] == "")
		{
			echo "<tr style='background-color: #e8edff'><td width='25%' >RMA</td><td width='75%' style='font-style:italic;' >Empty</td></tr>";
		}
		else
		{
			echo "<tr style='background-color: #e8edff'><td width='25%' >RMA</td><td width='75%' >". $row['rma']. "</td></tr>";
		}
                  
		if($row['code'] == "")
		{
			  echo "<tr style='background-color: #e8edff'><td>InPost Reference #</td><td style='font-style:italic;'>NOT APPLICABLE</td></tr>";    
		}
		else
		{
			  echo "<tr style='background-color: #e8edff'><td>InPost Reference #</td><td>". $row['code']. "</td></tr>";
		}

		if($row['parcel_size'] == "-")
		{
			  echo "<tr style='background-color: #e8edff'><td>Parcel Size</td><td>-</td></tr>";    
		  }
                  else
		  {
			  echo "<tr style='background-color: #e8edff'><td>Parcel Size</td><td>". $row['parcel_size']. "</td></tr>";
		  }
                  
                  echo "<tr style='background-color: #e8edff'><td>Sender's Email</td><td>". $row['sender_email']. "</td></tr>";
                  echo "<tr style='background-color: #e8edff'><td>Sender's Mobile</td><td>+44(0) ". $row['sender_phone']. "</td></tr>";

		echo "<tr style='background-color: #e8edff'><td>Additional Desc 1</td><td>". $row['add_desc1']. "</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>Additional Desc 2</td><td>". $row['add_desc2']. "</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>Additional Desc 3</td><td>". $row['add_desc3']. "</td></tr>";

		if($row['created_date']=="0000-00-00 00:00:00")
		{
			  echo "<tr style='background-color: #e8edff'><td>Parcel Created</td><td>NOT CREATED</td></tr>";
		}
		else
		{
			  echo "<tr style='background-color: #e8edff'><td>Parcel Created</td><td>". $row['created_date']. "</td></tr>";
		}

		echo "<tr style='background-color: #e8edff'><td>Company Name</td><td>". $row['company_name']. "</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>Recipient Name</td><td>".
			$row['first_name'] . ' ' . $row['last_name'] .
			"</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>Building Number</td><td>". $row['building_number']. "</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>Street</td><td>". $row['street']. "</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>Recipient Town/City</td><td>". $row['town']. "</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>County</td><td>". $row['province']. "</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>Recipient Postcode</td><td>". $row['post_code']. "</td></tr>";
		echo "<tr style='background-color: #e8edff'><td>Recipient Mobile</td><td>+44(0) ". $row['phone']. "</td></tr>";
                  
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
