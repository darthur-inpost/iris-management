<?php

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

$sql = "SELECT COUNT(order_num) FROM $table_prefix$parcel_table WHERE (status='PAID' OR status='UNPAID') AND perm_token='$api_key'";

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
	alert("Please select atleast one parcel for label generation!");
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

        <form id='pp_form' name='pp_form' action='print_label.php' onsubmit='return validate();' method='post'>
              

        <table align="center" width="95%" id="box-table-a" border="0" cellspacing="0" cellpadding="0">
                
        <thead>
         <tr><td colspan="11" style="text-align:center; background: #87907D;"><span style="color: white"><h3>Created Parcels</h3></span></td></tr>    
    	   <tr>
               <th scope="col" colspan="3" style='color: #990000; font-weight: bold; font-style: italic;'><?php echo $total_records; ?> Undispatched Parcels</th>
               <th scope="col" colspan="3" style='color: #990000; font-style: italic;'></th>
                
               <th scope="col" colspan="3" style='color: #990000; font-weight: bold; font-style: italic;'><?php if ($total_pages>0) echo "Displaying Page ". $page ." of " . $total_pages;   ?> </th>
               <th scope="col" colspan="1" style='color: #990000; font-weight: bold; font-style: italic;'>
<?php
	$next_page = $page + 1;
	$prev_page = $page - 1;

	if ($total_pages > 1)
	{
		switch($page)
		{
			case 1:
				echo "<span style='color: grey;'> << </span>"; 
				echo "<span style='color: grey;'> &nbsp;< </span>";
				echo "<a href='c_orders.php?page=".$next_page."&ppresults=".$ppresults ."'> &nbsp;&nbsp;> </a> ";
				echo "<a href='c_orders.php?page=".$total_pages."&ppresults=".$ppresults ."'> &nbsp;>> </a> ";
				break;
			case $total_pages:
				echo "<a href='c_orders.php?page=1'> << </a> ";
				echo "<a href='c_orders.php?page=".$prev_page."&ppresults=".$ppresults ."'> &nbsp;< </a> ";
				echo "<span style='color: grey;'> &nbsp;&nbsp;> </span>"; 
				echo "<span style='color: grey;'> &nbsp;>> </span>";    
				break;
			default:
				echo "<a href='c_orders.php?page=1'> << </a> ";
				echo "<a href='c_orders.php?page=".$prev_page."&ppresults=".$ppresults ."'> &nbsp;< </a> ";
				echo "<a href='c_orders.php?page=".$next_page."&ppresults=".$ppresults ."'> &nbsp;&nbsp;> </a> ";
				echo "<a href='c_orders.php?page=".$total_pages."&ppresults=".$ppresults ."'> &nbsp;>> </a> ";   
				break;
		}
	}
	else
	{
		echo "<span style='color: grey;'> << </span>"; 
		echo "<span style='color: grey;'> &nbsp;< </span>";
		echo "<span style='color: grey;'> &nbsp;&nbsp;> </span>"; 
		echo "<span style='color: grey;'> &nbsp;>> </span>";
	} 
?>    
               </th>

           </tr>
         <tr>
            <th scope="col"><div style="text-align: center">Order No.</div></th>
            <th scope="col"><div style="text-align: center">Recipient's Email</div></th> 
            <th scope="col"><div style="text-align: center">InPost Ref.</div></th> 
            <th scope="col"><div style="text-align: center">Status</div></th>
            <th scope="col"><div style="text-align: center">Order Date</div></th>
            <th scope="col"><div style="text-align: center">Creation Date</div></th>
            <th scope="col"><div style="text-align: center">Destination City</div></th>
            <th scope="col"><div style="text-align: center">Destination Terminal</div></th>
            <th scope="col"><div style="text-align: center">Return Code</div></th> 
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
	$all_parcels = tep_db_query("SELECT * FROM $table_prefix$parcel_table  WHERE perm_token='$api_key' AND (status='PAID' OR status='UNPAID') ORDER BY creation_date DESC LIMIT $start_from, $ppresults");

	while($row = tep_db_fetch_array($all_parcels))
	{
		if (strpos($row['d_city'], ",") != false)
		{
			$location       = $row['d_city'];
			$location_array = explode(',',$location);
			$location_d     = $location_array[1];
		}

		if (strpos($row['d_city'], ",") == false)
		{
			$location_d=$row['d_city'];
		}

		echo "<tr><td><div style='text-align: center'>". $row['order_num'] ."</div></td>";
		echo "<td><div style='text-align: center'>" . $row['email']. "</div></td>";
		echo "<td><div style='text-align: center'>" . $row['parcel_id']. "</div></td>";
		echo "<td><div style='text-align: center'>" . $row['status']. "</div></td>";
		echo "<td><div style='text-align: center'>" . $row['order_date']. "</div></td>";
		echo "<td><div style='text-align: center'>" . $row['creation_date']. "</div></td>";
		echo "<td><div style='text-align: center'>" . $location_d. "</div></td>";
		echo "<td><div style='text-align: center'>" . $row['d_terminal']. "</div></td>";
		echo "<td><div style='text-align: center'>" . $row['rl_code']. "</div></td>";
		if($row['parcel_id'] == '0000000000000000')
		{
			echo "<td><div style='text-align: center'> <input type='checkbox' id='parcel_c[]' name='parcel_c[]' value='" . $row['id'] . "'></div></td></tr>\n" ;
		}
		else
		{
			echo "<td><div style='text-align: center'> <input type='checkbox' id='parcel_c[]' name='parcel_c[]' value='" . $row['parcel_id'] . "'></div></td></tr>\n" ;
		}
	}

        echo"<tr style='border-top: 4px solid white;'><td colspan='9' style='background: #87907D;' ></td><td style='background: #87907D;'><input type='submit' value='Print Labels' /></td></tr>";
        
        echo "\n</table>";
        echo "\n</form>";
         
?>
            <div class="push"></div>
            </div>
            <div class="footer">
	    <p>&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</p>
            </div>
        </body>
    </html>
