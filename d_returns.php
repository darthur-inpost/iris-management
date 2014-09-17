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

$sql = "SELECT COUNT(id) FROM $table_prefix$return_table WHERE label_printed=1 AND api_key='$api_key'";
$rs_result     = tep_db_query($sql);
$row           = tep_db_fetch_array($rs_result);
$total_records = $row['COUNT(id)'];
$total_pages   = ceil($total_records / $ppresults);
 
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

        <table align="center" width="95%" id="box-table-a" border="0" cellspacing="0" cellpadding="0">
                
        <thead>
         <tr><td colspan="9" style="text-align:center; background: #87907D;"><span style="color: white"><h3>Dispatched Parcels</h3></span></td></tr>    
    	   <tr>
               <th scope="col" colspan="2" style='color: #990000; font-weight: bold; font-style: italic;'><?php echo $total_records; ?> Dispatched Retruns Parcels</th>
               <th scope="col" colspan="3" style='color: #990000; font-style: italic;'></th>
                
               <th scope="col" colspan="2" style='color: #990000; font-weight: bold; font-style: italic;'><?php if ($total_pages>0) echo "Displaying Page ". $page ." of " . $total_pages;   ?> </th>
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
			echo "<a href='d_returns.php?page=".$next_page."&ppresults=".$ppresults ."'> &nbsp;&nbsp;> </a> ";
			echo "<a href='d_returns.php?page=".$total_pages."&ppresults=".$ppresults ."'> &nbsp;>> </a> ";
			break;
		case $total_pages:
			echo "<a href='d_returns.php?page=1'> << </a> ";
			echo "<a href='d_returns.php?page=".$prev_page."&ppresults=".$ppresults ."'> &nbsp;< </a> ";
			echo "<span style='color: grey;'> &nbsp;&nbsp;> </span>"; 
			echo "<span style='color: grey;'> &nbsp;>> </span>";    
			break;
		default:
			echo "<a href='d_returns.php?page=1'> << </a> ";
			echo "<a href='d_returns.php?page=".$prev_page."&ppresults=".$ppresults ."'> &nbsp;< </a> ";
			echo "<a href='d_returns.php?page=".$next_page."&ppresults=".$ppresults ."'> &nbsp;&nbsp;> </a> ";
			echo "<a href='d_returns.php?page=".$total_pages."&ppresults=".$ppresults ."'> &nbsp;>> </a> ";   
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
            <th scope="col"><div style="text-align: center">RMA</div></th>
            <th scope="col"><div style="text-align: center">Return Code</div></th>
            <th scope="col"><div style="text-align: center">Expiry Date</div></th>
            <th scope="col"><div style="text-align: center">Recipient<br>Name</div></th>
            <th scope="col"><div style="text-align: center">Phone</div></th>
            <th scope="col"><div style="text-align: center">Creation Date</div></th>
            <th scope="col"><div style="text-align: center">Dispatch Date</div></th>
            <th scope="col"><div style="text-align: center">Address</div></th>
        </tr>
    </thead>

<?php
	$con = tep_db_connect();
 
	if (!$con)
	{
		die('Could not connect: ' . mysqli_error());
        }
        
        $all_parcels = tep_db_query("SELECT * FROM $table_prefix$return_table WHERE api_key='$api_key' AND label_printed=1 ORDER BY dispatch_date DESC LIMIT $start_from, " . $ppresults);

	while($row = tep_db_fetch_array($all_parcels))
	{
		echo "<tr><td><div style='text-align: center'>". $row['rma'] . "</div></td>";
        echo "<td><div style='text-align: center'>" . $row['code']. "</div></td>";
        
        echo "<td><div style='text-align: center'>" . $row['expire_at']. "</div></td>";
      
	echo "<td><div style='text-align: center'>" . $row['first_name'] . ' ' .
		$row['last_name'] . "</div></td>";
        echo "<td><div style='text-align: center'>" . $row['phone']. "</div></td>";
        echo "<td><div style='text-align: center'>" . $row['created_date']. "</div></td>";
         echo "<td><div style='text-align: center'>" . $row['dispatch_date']. "</div></td>";
	echo "<td><div style='text-align: center'>" .
		$row['building_number'] . ', ' .
		$row['street'] . ', ' .
		$row['town'] . ', ' .
		$row['post_code'] .
		"</div></td>";

        }

        echo"<tr style='border-top: 4px solid white;'><td colspan='8' style='background: #87907D;' ></td></tr>";
        
        echo "</table>";
        echo "</form>";
         
?>
            <div class="push"></div>
            </div>
            <div class="footer">
	    <p>&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</p>
            </div>
</body>
</html>
