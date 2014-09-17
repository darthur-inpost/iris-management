<?php
//require config files
require_once('config.php');
require_once('includes/database.php');
require_once('RestApi.php');

$parcel_size = $_POST['parcel_size'];

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
    
    if($session_life > $inactive){
        header("Location: logout.php");
    }
    $_SESSION['start'] = time();
}
else
{
	header("Location: 404_error.php");
}

	// The email address will never be used to get XSS access so
	// making sure they are valid should be enough.
	$ret = preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/",
		$_POST['simple_email']);

	if($ret != true)
	{
		$_SESSION['error_message'] = 'Email address is not a valid email address. Please enter a valid one.';
		session_write_close();
		header("Location: n_parcel.php");
		return;
	}

	// The mobile number must only have numbers in it.
	$ret = preg_match("/^[0-9]{9}/",
		$_POST['simple_mobile']);

	if($ret != true)
	{
		$_SESSION['error_message'] = 'Mobile number should only contain a number without spaces and be 9 digits long.';
		session_write_close();
		header("Location: n_parcel.php");
		return;
	}

	// The size can only be A, B or C.
	$ret = preg_match("/^[ABC]{1}/",
		$_POST['simple_size']);

	if($ret != true)
	{
		$_SESSION['error_message'] = 'The size can only be A, B or C.';
		session_write_close();
		header("Location: n_parcel.php");
		return;
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
        
<script type="text/javascript">

function Redirect()
{
        window.location="c_orders.php";
}
</script>

</head>
<body>
  
<div class="wrapper">
<?php
// Save having to edit code in multiple places.
include 'includes/menu.php';
?>
       <table align="center" width="100%" id="box-table-a" border="0" cellspacing="0" cellpadding="0">

        <thead>
         <tr><td colspan="4" style="text-align:center; background: #87907D;"><span style="color: white"><h3>Parcel Creation Request Status</h3></span></td></tr>      
    	<tr>
            <th scope="col"><div style="text-align: center">Order No.</div></th>
            <th scope="col"><div style="text-align: center">Size</div></th>
            <th scope="col"><div style="text-align: center">Status</div></th>
            <th scope="col"><div style="text-align: center">Error </div></th>
        </tr>
    </thead>
        
<?php
        $temp_parcel_id = mt_rand(100000, 999999);

	// Get all of the POST data.
	$email  = trim($_POST['simple_email']);
	$mobile = trim($_POST['simple_mobile']);
	$locker = htmlspecialchars(trim(strip_tags($_POST['name'])));
	$size   = htmlspecialchars(trim(strip_tags($_POST['simple_size'])));
	$refe   = htmlspecialchars(trim(strip_tags($_POST['simple_reference'])));
	$town   = htmlspecialchars(trim(strip_tags($_POST['box_machine_town'])));

	// Insert the details of the parcel into the table.
	$the_date = date('Y-m-d H:i:s');
	$ret = tep_db_query("INSERT into " . $table_prefix . $parcel_table .
		" (perm_token, order_num, parcel_size, d_terminal, mobile, email, status, order_date, creation_date)" .
		" values ('$api_key', '$refe', '$size', '$locker', '$mobile', '$email', 'UNPAID', '$the_date', '$the_date')");

	// Get the ID of the latest inserted row.
	$new_id = tep_db_insert_id();

        // create the parcel        
	$params = array(
		'url'        => $base_url . 'parcels',
		'token'      => $api_key,
		'methodType' => 'POST',
		'params'     => array(
			'description' => $refe,
			'receiver'    => array(
				'phone' => $mobile,
				'email' => $email
                	),
		'size'   => $size,
		'tmp_id' => $temp_parcel_id, //distinct serial number assigned by the system
		'target_machine' => $locker,
		)
	);

	$restApi1 = new RestApi($params);

	//echo '<pre>';
        //print_r($restApi1->getInfo());
	//echo '</pre><br>';
	//echo '<pre>';
        //print_r($restApi1->getResponse());
	//echo '</pre>';

        //returned header info
        $info_arr1 = $restApi1->getInfo();

        //if parcel creation successful
	if ($info_arr1["http_code"] == 201)
	{
		$response = json_decode($restApi1->getResponse());
		$parcel_id = $response->id;
            
		//pay for the parcel
		$restApi2 = new RestApi(
			array(
			'url'        => $base_url . 'parcels/' . $parcel_id .
					'/pay',
			'token'      => $api_key,
			'methodType' => 'POST',
			'params'     => array()
			)
		);

		//returned header info
		$info_arr2 = $restApi2->getInfo();

		// if payment successful
		if ($info_arr2["http_code"] == 204)
		{
			//update payment status in db
			$creation_date = date('Y-m-d H:i:s');

			$query = tep_db_query("UPDATE " . $table_prefix . 
				$parcel_table .
				" SET status='PAID', rl_code='$rl_code', parcel_id='$parcel_id', creation_date='$creation_date' WHERE id=$new_id");

                	echo "<td><div style='text-align: center'>CREATED & PAID</div></td>";
                	echo "<td><div style='text-align: center'>NONE</div></td>";
			//header("Location: u_orders.php"); 
		}
		else
		{
			//if not, display the error code, error description   
			$error_cr = $info_arr2["http_code"];

			echo "<td><div style='text-align: center'>UNPAID</div></td>";
			echo "<td><div style='text-align: center'>".$error_cr ."</div></td>";
		}
		echo "</tr>";
	}
	elseif ($info_arr1["http_code"] != 200)
	{
                //echo "Parcel could not be created: <br/>";
                //echo $info_arr["http_code"] . " Error";
                $error_cr = $info_arr1["http_code"];

                echo "<td><div style='text-align: center'>NOT CREATED</div></td>";
                echo "<td><div style='text-align: center'>".$error_cr ."</div></tr></td>";
		die;

	}
	echo "<tr style='border-top: 4px solid white;'><td colspan='4' style='background: #87907D;' ></td></tr>";
	echo "</table>";
       
       ?>
        <script type="text/javascript">
            alert("You will be redirected to the 'Created Parcels' page in 3 seconds");
            setTimeout('Redirect()', 3000)
        </script> 

    <div class="push"></div>
  </div> 
 
  <div class="footer"> &copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</div>

   <!-- end of footer -->
  
    </body>
</html>
