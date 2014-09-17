<?php
require_once('config.php');
require_once('includes/database.php');
require_once('RestApi.php');

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

// Set the timezone
// Europe/London
date_default_timezone_set('Europe/London');

// Get a list of the machines for use on the form.
$params['url']        = $base_url . 'machines';
$params['token']      = $api_key;
$params['methodType'] = 'GET';

$rest_api = new RestApi($params);

$info  = $rest_api->getInfo();
$reply = $rest_api->getResponse();

$parcelTargetMachinesId     = array();
$parcelTargetMachinesDetail = array();
$defaultSelect              = 'Select Machine..';

if($info['http_code'] == 200)
{
	$machines = json_decode($reply);

	if(is_array(@$machines) && !empty($machines))
	{
		foreach($machines as $key => $machine)
		{
			$parcelTargetMachinesId[$machine->id] = $machine->id.', '.@$machine->address->city.', '.@$machine->address->street;
			$parcelTargetMachinesDetail[$machine->id] = array(
                'id' => $machine->id,
                'address' => array(
                    'building_number' => @$machine->address->building_number,
                    'flat_number' => @$machine->address->flat_number,
                    'post_code' => @$machine->address->post_code,
                    'province' => @$machine->address->province,
                    'street' => @$machine->address->street,
                    'city' => @$machine->address->city
                )
		);
        	}
	}
}
else
{
	echo 'Failed to get the machines.<br>';
	echo 'Error code : ' . $info['http_code'] . '<br>';
}

//echo "Machines = " . $reply . '<br>';
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
<script type="text/javascript" src="https://geowidget.inpost.co.uk/dropdown.php?field_to_update=name&field_to_update2=address&user_function=user_function"></script>
<script type="text/javascript" src="js/jquery-1.11.1.js"></script>
<script type="text/javascript" src="js/jquery.validate.min.js"></script>

<script type="text/javascript">

///
// user_function
//
function user_function(value)
{
        var address = value.split(';');
        document.getElementById('box_machine_town').value=address[1];
        document.getElementById('address').value=address[2]+address[3];
}

</script>
<script>
jQuery(document).ready(function() {
	// Validation is based on class and type fields.
	jQuery("#ppForm").validate({
		errorLabelContainer: jQuery("#ppForm td.error"),
		//rules: {
			//password: "check_password"
		//}
		messages: {
			simple_email: "Enter an email address",
			simple_mobile: "Enter a Mobile number",
			name: "Please select a Locker",
			simple_size: "Please select a Parcel Size",
			simple_reference: "Please fill in a Reference"
		}
	});
});

</script>
</head>
<body>
<div class="wrapper">

<?php
// Save having to edit code in multiple places.
include 'includes/menu.php';
?>
<?php
	// Output an error messages we get.
	if(isset($_SESSION['error_message']))
	{
		echo '<p style="color:red;">' . $_SESSION['error_message'] .
			'</p>';
	}
	unset($_SESSION['error_message']);
?>
<noscript>To use the map you MUST enable Javascript</noscript>

    <form id="ppForm" name="ppForm" action="parcel_pay.php" method="post">

    <div style="text-align:center;">
        <table align="center" width="40%" style="border:0; cellspacing:0; cellpadding:0; margin-left:auto; margin-right:auto;">
                
        <thead>
	<tr><td colspan="2" style="text-align:center; background: #87907D;"><span style="color: white"><h3>New Parcel</h3></span>
</td>
	</tr>    
    </thead>
    <tbody>
    <tr style='border-top: 4px solid white;'>
	<td colspan='2' style='color:red;' >Please fill in ALL fields</td>
    </tr>
        
    <tr style="background-color: #e8edff">
        <td><label for="simple_email">E-mail</label></td>
	<td>
	<input type="text" id="simple_email" name="simple_email" required="required" class="email" type="email" minlength="7" maxlength="50" />
	</td>
    </tr>
    <tr style="background-color: #e8edff">
        <td><label for="simple_mobile">Mobile</label></td>
	<td>
	<span class="add-on">+44(0)7</span>
            <input type="text" id="simple_mobile" name="simple_mobile" required="required" class="cust-width required number" maxlength="9" minlength="8" />
        </td>
    </tr>
    <tr style="background-color: #e8edff">
        <td><label for="simple_terminal">InPost Terminal</label></td>
	<td>
           <select id="name" name="name" required="required"    class="chosen-select">
                <option value='' <?php if(@$inpostparcelsData['parcel_target_machine_id'] == ''){ echo "selected=selected";} ?>><?php echo $defaultSelect;?></option>
                <?php foreach($parcelTargetMachinesId as $key => $parcelTargetMachineId): ?>
                    <option value='<?php echo $key ?>' <?php if($inpostparcelsData['parcel_target_machine_id'] == $parcelTargetMachineId){ echo "selected=selected";} ?>><?php echo $parcelTargetMachineId;?></option>
                <?php endforeach; ?>
</select>
       <a href="#" class="btn btn-pm yellow" onclick="openMap()">Show map</a>
            <input type="hidden" id="name" name="name" disabled="disabled" />
            <input type="hidden" id="box_machine_town" name="box_machine_town" disabled="disabled" />
            <input type="hidden" id="address" name="address" disabled="disabled" />
	</td>
    </tr>
    <tr style="background-color: #e8edff">
        <td><label for="simple_size">Size</label></td>
	<td>
	       <label for="simple_size0" title="8 x 38 x 64 cm"><input type="radio" id="simple_size0" name="simple_size" required="required" value="A" />
                            Small
			</label>
	       <label for="simple_size1" title="19 x 38 x 64 cm"><input type="radio" id="simple_size1" name="simple_size" required="required" value="B" />
                            Medium
			</label>
	       <label for="simple_size2" title="41 x 38 x 64 cm"><input type="radio" id="simple_size2" name="simple_size" required="required" value="C" />
                            Large
			</label>
        </td>
    </tr>
    <tr style="background-color: #e8edff">
        <td><label for="simple_reference">Reference number</label></td>
	<td>
            <input type="text" id="simple_reference" name="simple_reference" class="" required="required" minlength="5" maxlength="50" placeholder="e.g. Order # 17642" />
        </td>
    </tr>
    <tr>
	<td colspan="2" class="error"><td>
    </tr>
    <tr>
	<td colspan="2">
            <!--<button name="submit" id="submit">Submit</button>-->
            <input type="submit" value="Submit" name="submit" id="submit">
        </td>
    </tr>
    </tbody>

    </table>
    </div>
</form>
    
            <div class="push"></div>
            </div>
            <div class="footer">
	    <p>&copy; <?php echo date('Y'); ?> InPost UK Ltd - All rights reserved.</p>
            </div>

</body>
</html>
