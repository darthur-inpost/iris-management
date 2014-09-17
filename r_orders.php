<?php
//
// This file allows users to print labels for return orders
//
require_once('config.php');
require_once('includes/database.php');

if (isset($_GET["page"]) && isset($_GET["ppresults"]))
{
	$page      = $_GET["page"]; 
	$ppresults = $_GET["ppresults"];  
}
else
{
	$page      = 1; 
	$ppresults = PPRESULTS;  
}

$start_from = ($page-1) * $ppresults; 

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

$con = tep_db_connect();

if (!$con)
{
	die('Could not connect: ' . mysqli_error());
}

$sql = "SELECT COUNT(id) FROM $table_prefix$return_table WHERE label_printed=0 AND api_key='$api_key'";

$rs_result     = tep_db_query($sql);
$total_row     = tep_db_fetch_array($rs_result);
$total_records = $total_row['COUNT(order_num)'];
$total_pages   = ceil($total_records / $ppresults);

$row           = tep_db_fetch_array($rs_result);
 
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
	// Check if the password matches the regular expression.
	jQuery.validator.addMethod("check_password", function(value)
	{
		var PE = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}$/;

		if(!PE.test(value))
		{
			return false;
		}
		return true;
	}, "The password must contain:<br>one lower case letter, one upper case letter,<br>one digit one special letter,<br>be 8-20 in length, and have no spaces.");

jQuery(document).ready(function() {
	// Validation is based on class and type fields.
	jQuery("#returns_label").validate({
		errorLabelContainer: jQuery("#returns_label td.error"),
		rules: {
			password: "check_password",
			api_key:  "check_apikey",
			cpassword: {
				equalTo: "#password"
			}
		},
		messages: {
			email: "Enter an email address",
			api_key : {
				required: "Provide an API Key",
				minlength: jQuery.validator.format("Enter at least {0} characters")
			},
			label_format: {
				required: "Please select a Label Format"
			}
		}

	});

});

</script>

<script type="text/javascript">
        
function validate()
{
	var pc_array = document.getElementsByName('parcel_c[]');
	var len      = pc_array.length;
	var nval     = 0;

	for (var i = 0; i < len; i++)
	{
		if(pc_array[i].checked === false)
		{
			++nval;
		}
	}

	if(nval < len)
	{
		var user_conf = confirm((len-nval) + " new parcel labels will be generated. Do you want to proceed?");
		if (user_conf === true)
		{
			return true;
		}
		return false;
	}
	alert("Please select at least one parcel for label generation!");
	return false;
}
        
function apply2all(status)
{
	var ch_array = document.getElementsByName('parcel_c[]');
	var len      = ch_array.length;

	for(var i = 0; i < len; i++)
	{
		if(status === false &&  ch_array[i].checked === false)
		{
			ch_array[i].checked = true;
		}
		else
		{
			ch_array[i].checked = status;
		}
	}
}

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

<form id='returns_label' name='returns_label' action='print_return_label.php' onsubmit='return validate();' method='post'>

        <table align="center" width="95%" id="box-table-a" border="0" cellspacing="0" cellpadding="0">

        <thead>
	 <tr>
		<td colspan="8" style="text-align:center; background: #87907D;"><span style="color: white"><h3>Created Parcels</h3></span></td>
	</tr>
	<tr>
               <th scope="col" colspan="2" style='color: #990000; font-weight: bold; font-style: italic;'><?php echo $total_records; ?> Undispatched Parcels</th>
               <th scope="col" colspan="1" style='color: #990000; font-style: italic;'></th>
                
               <th scope="col" colspan="4" style='color: #990000; font-weight: bold; font-style: italic;'><?php if ($total_pages>0) echo "Displaying Page ". $page ." of " . $total_pages;   ?> </th>
               <th scope="col" colspan="1" style='color: #990000; font-weight: bold; font-style: italic;'>
<?php
	$next_page = $page + 1;
	$prev_page = $page - 1;

	if ($total_pages > 1)
	{
		switch($page)
		{
			case 1:
				echo "<span style='color: grey;'>&lt;&lt;</span>"; 
				echo "<span style='color: grey;'>&nbsp;&lt;</span>";
				echo "<a href='c_orders.php?page=".$next_page."&ppresults=".$ppresults ."'>&nbsp;&nbsp;&gt;</a> ";
				echo "<a href='c_orders.php?page=".$total_pages."&ppresults=".$ppresults ."'>&nbsp;&gt;&gt;</a> ";
				break;
			case $total_pages:
				echo "<a href='c_orders.php?page=1'>&lt;&lt;</a> ";
				echo "<a href='c_orders.php?page=".$prev_page."&ppresults=".$ppresults ."'>&nbsp;&lt;</a> ";
				echo "<span style='color: grey;'>&nbsp;&nbsp;&gt;</span>"; 
				echo "<span style='color: grey;'>&nbsp;&gt;&gt;</span>";    
				break;
			default:
				echo "<a href='c_orders.php?page=1'>&lt;&ltl</a> ";
				echo "<a href='c_orders.php?page=".$prev_page."&ppresults=".$ppresults ."'>&nbsp;&lt;</a> ";
				echo "<a href='c_orders.php?page=".$next_page."&ppresults=".$ppresults ."'>&nbsp;&nbsp;&gt; </a> ";
				echo "<a href='c_orders.php?page=".$total_pages."&ppresults=".$ppresults ."'>&nbsp;&gt;&gt; </a> ";   
				break;
		}
	}
	else
	{
		echo "<span style='color: grey;'>&lt;&lt;</span>"; 
		echo "<span style='color: grey;'>&nbsp;&lt;</span>";
		echo "<span style='color: grey;'>&nbsp;&nbsp;&gt;</span>"; 
		echo "<span style='color: grey;'>&nbsp;&gt;&gt;</span>";
	} 
?>    
               </th>

           </tr>
         <tr>
            <th scope="col"><div style="text-align: center">RMA</div></th>
            <th scope="col"><div style="text-align: center">Return Code</div></th> 
            <th scope="col"><div style="text-align: center">Expiry Date</div></th> 
            <th scope="col"><div style="text-align: center">Recipient<br>Name</div></th>
            <th scope="col"><div style="text-align: center">Phone</div></th>
            <th scope="col"><div style="text-align: center">Creation Date</div></th>
            <th scope="col"><div style="text-align: center">Address</div></th>
            <th scope="col">
                <div style="text-align: center">
                    Select<br>
                    <a href="javascript:void(0);" onclick="apply2all(true);">All | </a>
                    <a href="javascript:void(0);" onclick="apply2all(false);">Invert</a>
                </div>
            </th>
        </tr>
    </thead>

<?php
	$all_parcels = tep_db_query("SELECT * FROM $table_prefix$return_table  WHERE api_key='$api_key' AND label_printed=0 ORDER BY created_date DESC LIMIT $start_from, $ppresults");

	while($row = tep_db_fetch_array($all_parcels))
	{
		echo "<tr><td><div style='text-align: center'>". $row['rma'] ."</div></td>";
		echo "<td><div style='text-align: center'>" . $row['code']. "</div></td>";
		echo "<td><div style='text-align: center'>" . $row['expire_at']. "</div></td>";
		echo "<td><div style='text-align: center'>" .
			$row['first_name']. ' ' .
			$row['last_name'].
			"</div></td>";
		echo "<td><div style='text-align: center'>" . $row['phone']. "</div></td>";
		echo "<td><div style='text-align: center'>" . $row['created_date']. "</div></td>";
		echo "<td><div style='text-align: center'>" .
			$row['building_number'] . ", " .
			$row['street'] . ", " .
			$row['town'] . ", " .
			$row['post_code'] . ", " .
			"</div></td>";
		echo "<td><div style='text-align: center'> <input type='checkbox' id='parcel_c[]' name='parcel_c[]' value='" . $row['code'] . "'></div></td></tr>\n" ;
	}

	echo"<tr style='border-top: 4px solid white;'>
		<td colspan='7' style='background: #87907D;' ></td>
		<td style='background: #87907D;'><input type='submit' value='Print Labels' /></td>
		</tr>";

	// Check if the user's session variable already has a zip file already
	// ready to be downloaded.
	if(file_exists("test_" . $username . '_' . session_id() . ".zip") == true)
	{
		echo "<tr>
			<td colspan='8' style='font-size:15px;background: #87907D;'><a style='color:white;'href='test_" . $username . '_' . session_id() . ".zip' target='_blank'>**** Please Click here to Download Labels ****</a>
			</td>
			</tr>";
	}
         
?>
</table>
</form>
</div>

            <div class="push"></div>
            </div>
            <div class="footer">
	    <p>&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</p>
            </div>
        </body>
    </html>
