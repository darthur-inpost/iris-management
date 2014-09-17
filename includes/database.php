<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2013 osCommerce

  Released under the GNU General Public License
*/
require_once('better_crypt.php');

  function tep_db_connect($server = DB_SERVER, $username = DB_SERVER_USERNAME, $password = DB_SERVER_PASSWORD, $database = DB_DATABASE, $link = 'db_link') {
    global $$link;

    if (USE_PCONNECT == 'true') {
      $server = 'p:' . $server;
    }

    $$link = mysqli_connect($server, $username, $password, $database);

    if ( !mysqli_connect_errno() ) {
      mysqli_set_charset($$link, 'utf8');
    } 

    return $$link;
  }

  function tep_db_close($link = 'db_link') {
    global $$link;

    return mysqli_close($$link);
  }

  function tep_db_error($query, $errno, $error) {
    global $logger;

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
      $logger->write('[' . $errno . '] ' . $error, 'ERROR');
    }

    die('<font color="#000000"><strong>' . $errno . ' - ' . $error . '<br /><br />' . $query . '<br /><br /><small><font color="#ff0000">[TEP STOP]</font></small><br /><br /></strong></font>');
  }

  function tep_db_query($query, $link = 'db_link') {
    global $$link, $logger;

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
      if (!is_object($logger)) $logger = new logger;
      $logger->write($query, 'QUERY');
    }

    $result = mysqli_query($$link, $query) or tep_db_error($query, mysqli_errno($$link), mysqli_error($$link));

    return $result;
  }

  function tep_db_perform($table, $data, $action = 'insert', $parameters = '', $link = 'db_link') {
    reset($data);
    if ($action == 'insert') {
      $query = 'insert into ' . $table . ' (';
      while (list($columns, ) = each($data)) {
        $query .= $columns . ', ';
      }
      $query = substr($query, 0, -2) . ') values (';
      reset($data);
      while (list(, $value) = each($data)) {
        switch ((string)$value) {
          case 'now()':
            $query .= 'now(), ';
            break;
          case 'null':
            $query .= 'null, ';
            break;
          default:
            $query .= '\'' . tep_db_input($value) . '\', ';
            break;
        }
      }
      $query = substr($query, 0, -2) . ')';
    } elseif ($action == 'update') {
      $query = 'update ' . $table . ' set ';
      while (list($columns, $value) = each($data)) {
        switch ((string)$value) {
          case 'now()':
            $query .= $columns . ' = now(), ';
            break;
          case 'null':
            $query .= $columns .= ' = null, ';
            break;
          default:
            $query .= $columns . ' = \'' . tep_db_input($value) . '\', ';
            break;
        }
      }
      $query = substr($query, 0, -2) . ' where ' . $parameters;
    }

    return tep_db_query($query, $link);
  }

  function tep_db_fetch_array($db_query) {
    return mysqli_fetch_array($db_query, MYSQLI_ASSOC);
  }

  function tep_db_result($result, $row, $field = '') {
    if ( $field === '' ) {
      $field = 0;
    }

    tep_db_data_seek($result, $row);
    $data = tep_db_fetch_array($result);

    return $data[$field];
  }

  function tep_db_num_rows($db_query) {
    return mysqli_num_rows($db_query);
  }

  function tep_db_data_seek($db_query, $row_number) {
    return mysqli_data_seek($db_query, $row_number);
  }

  function tep_db_insert_id($link = 'db_link') {
    global $$link;

    return mysqli_insert_id($$link);
  }

  function tep_db_free_result($db_query) {
    return mysqli_free_result($db_query);
  }

  function tep_db_fetch_fields($db_query) {
    return mysqli_fetch_field($db_query);
  }

  function tep_db_output($string) {
    return htmlspecialchars($string);
  }

  function tep_db_input($string, $link = 'db_link') {
    global $$link;

    return mysqli_real_escape_string($$link, $string);
  }

  function tep_db_prepare_input($string) {
    if (is_string($string)) {
      return trim(stripslashes($string));
    } elseif (is_array($string)) {
      reset($string);
      while (list($key, $value) = each($string)) {
        $string[$key] = tep_db_prepare_input($value);
      }
      return $string;
    } else {
      return $string;
    }
  }

  function tep_db_affected_rows($link = 'db_link') {
    global $$link;

    return mysqli_affected_rows($$link);
  }

  function tep_db_get_server_info($link = 'db_link') {
    global $$link;

    return mysqli_get_server_info($$link);
  }

  if ( !function_exists('mysqli_connect') ) {
    define('MYSQLI_ASSOC', MYSQL_ASSOC);

    function mysqli_connect($server, $username, $password, $database) {
      if ( substr($server, 0, 2) == 'p:' ) {
        $link = mysql_pconnect(substr($server, 2), $username, $password);
      } else {
        $link = mysql_connect($server, $username, $password);
      }

      if ( $link ) {
        mysql_select_db($database, $link);
      }

      return $link;
    }

    function mysqli_connect_errno($link = null) {
      if ( is_null($link) ) {
        return mysql_errno();
      }

      return mysql_errno($link);
    }

    function mysqli_connect_error($link = null) {
      if ( is_null($link) ) {
        return mysql_error();
      }

      return mysql_error($link);
    }

    function mysqli_set_charset($link, $charset) {
      if ( function_exists('mysql_set_charset') ) {
        return mysql_set_charset($charset, $link);
      }
    }

    function mysqli_close($link) {
      return mysql_close($link);
    }

    function mysqli_query($link, $query) {
      return mysql_query($query, $link);
    }

    function mysqli_errno($link = null) {
      if ( is_null($link) ) {
        return mysql_errno();
      }

      return mysql_errno($link);
    }

    function mysqli_error($link = null) {
      if ( is_null($link) ) {
        return mysql_error();
      }

      return mysql_error($link);
    }

    function mysqli_fetch_array($query, $type) {
      return mysql_fetch_array($query, $type);
    }

    function mysqli_num_rows($query) {
      return mysql_num_rows($query);
    }

    function mysqli_data_seek($query, $offset) {
      return mysql_data_seek($query, $offset);
    }

    function mysqli_insert_id($link) {
      return mysql_insert_id($link);
    }

    function mysqli_free_result($query) {
      return mysql_free_result($query);
    }

    function mysqli_fetch_field($query) {
      return mysql_fetch_field($query);
    }

    function mysqli_real_escape_string($link, $string) {
      if ( function_exists('mysql_real_escape_string') ) {
        return mysql_real_escape_string($string, $link);
      } elseif ( function_exists('mysql_escape_string') ) {
        return mysql_escape_string($string);
      }

      return addslashes($string);
    }

    function mysqli_affected_rows($link) {
      return mysql_affected_rows($link);
    }

    function mysqli_get_server_info($link) {
      return mysql_get_server_info($link);
    }
  }

//----------------------------------------------------------------------------
//---------------------------- Password and Reset of it ----------------------
//----------------------------------------------------------------------------

define(PW_SALT,'(+3%_');
 
function checkUNEmail($uname,$email)
{
	$error = array('status'=>false,'userID'=>0);
	if (isset($email) && trim($email) != '')
	{
		//email was entered
		if ($SQL = tep_db_query("SELECT id FROM users WHERE username = '" . trim($email) . "' LIMIT 1"))
		{
			$numRows = tep_db_num_rows($SQL);
			$ret = tep_db_fetch_array($SQL);

			$userID = $ret['ID'];
			if ($numRows >= 1)
				return array('status'=>true,'userID'=>$userID);
		}
		else
		{ return $error; }
	}
	elseif (isset($uname) && trim($uname) != '')
	{
		// username was entered
		if ($SQL = tep_db_query("SELECT id FROM users WHERE username = '" . trim($uname) . "' LIMIT 1"))
		{
			$numRows = tep_db_num_rows($SQL);
			$ret = tep_db_fetch_array($SQL);

			$userID = $ret['id'];
			if ($numRows >= 1)
				return array('status' => true,
					'userID' => $userID);
		}
		else
		{
			return $error;
		}
	}
	else
	{
		//nothing was entered;
		return $error;
	}
}

///
// getSecurityQuestion
//
function getSecurityQuestion($userID)
{
	$questions = array();
	$questions[0] = "What is your mother's maiden name?";
	$questions[1] = "What city were you born in?";
	$questions[2] = "What is your favorite color?";
	$questions[3] = "What year did you graduate from High School?";
	$questions[4] = "What was the name of your first boyfriend/girlfriend?";
	$questions[5] = "What is your favorite model of car?";

	if ($SQL = tep_db_query("SELECT secQ FROM users WHERE id = " . $userID . " LIMIT 1"))
	{
		$ret = tep_db_fetch_array($SQL);

		return $questions[$ret['secQ']];
	}
	else
	{
		return false;
	}
}

///
// checkSecAnswer
//
function checkSecAnswer($userID,$answer)
{
        $answer = strtolower($answer);

	if ($SQL = tep_db_query("SELECT `username` FROM `users` WHERE `id` = " .
	       $userID . " AND LOWER(`secA`) = '" . $answer . "' LIMIT 1"))
	{
		$numRows = tep_db_num_rows($SQL);

		if ($numRows >= 1)
		{
			return true;
		}
	}
	else
	{
		return false;
	}
}

///
// sendPasswordEmail
//
function sendPasswordEmail($userID)
{
	if ($SQL = tep_db_query("SELECT `username`,`password` FROM `users` WHERE `id` = " . $userID . " LIMIT 1"))
	{
		$ret = tep_db_fetch_array($SQL);
		$uname = $ret['username'];
		$pword = $ret['password'];

		$expFormat = mktime(date("H"), date("i"), date("s"), date("m")  , date("d")+3, date("Y"));
		$expDate = date("Y-m-d H:i:s",$expFormat);
		$key = md5($uname . '_' . $uname . rand(0,10000) .$expDate . PW_SALT);
		if ($SQL = tep_db_query("INSERT INTO `recoveryemails_enc` (`UserID`,`Key`,`expDate`) VALUES ($userID,'$key','$expDate')"))
		{
			$passwordLink = "<a href=\"http://inpost247.uk/b2a/fcredentials.php?a=recover&email=" . $key . "&u=" . urlencode(base64_encode($userID)) . "\">http://inpost247.uk/b2a/fcredentials.php?a=recover&email=" . $key . "&u=" . urlencode(base64_encode($userID)) . "</a>";
			$message = "Dear $uname,<br>";
			$message .= "Please visit the following link to reset your password:<br>";
			$message .= "-----------------------<br>";
			$message .= "$passwordLink<br>";
			$message .= "-----------------------<br>";
			$message .= "<p>Please be sure to copy the entire link into your browser. The link will expire after 3 days for security reasons.</p>";
			$message .= "<p>If you did not request this forgotten password email, no action is needed, your password will not be reset as long as the link above is not visited. However, you may want to log into your account and change your security password and answer, as someone may have guessed it.</p><br>";
			$message .= "Thanks,<br>";
			$message .= "-- Our site team";
			$headers .= "From: Our Site <integration@inpost.co.uk> \n";
			$headers .= "To-Sender: \n";
			$headers .= "X-Mailer: PHP\n"; // mailer
			$headers .= "Reply-To: integration@inpost.co.uk\n"; // Reply address
			$headers .= "Return-Path: integration@inpost.co.uk\n"; //Return Path for errors
			$headers .= "Content-Type: text/html; charset=iso-8859-1"; //Enc-type
			$subject = "Your Lost Password";
			@mail($uname, $subject, $message, $headers);
			return str_replace("\r\n","<br/ >", $message);
		}
	}
}

///
// checkEmailKey
//
function checkEmailKey($key,$userID)
{
	$curDate = date("Y-m-d H:i:s");
	if ($SQL = tep_db_query("SELECT `UserID` FROM `recoveryemails_enc` WHERE `Key` = '$key' AND `UserID` = $userID AND `expDate` >= '$curDate'"))
	{
		$numRows = tep_db_num_rows($SQL);

		$ret = tep_db_fetch_array($SQL);
		$userID = $ret['UserID'];

		if ($numRows > 0 && $userID != '')
		{
			return array('status' => true,
				'userID' => $userID);
		}
	}
	return false;
}

///
// updateUserPassword
//
function updateUserPassword($userID,$password,$key)
{
	if (checkEmailKey($key,$userID) === false)
		return false;

	//$password = md5(trim($password) . PW_SALT);
	$password = better_crypt($password);

	if ($SQL = tep_db_query("UPDATE `users` SET `password` = '" .
	       $password . "' WHERE `id` = $userID"))
	{
        	$SQL = tep_db_query("DELETE FROM `recoveryemails_enc` WHERE `Key` = '$key'");
	}
}

///
// getUserName
//
function getUserName($userID)
{
	if ($SQL = tep_db_query("SELECT `username` FROM `users` WHERE `id` = $userID"))
	{
		$ret = tep_db_fetch_array($SQL);
		$uname = $ret['username'];
	}
	return $uname;
}

//----------------------------------------------------------------------------
//----------------------------- Utility Functions ----------------------------
//----------------------------------------------------------------------------
///
// add_parcel
//
// @brief Add the parcel information into the table
// @param The array with the fields in it
//
function add_parcel($data, $count)
{
	if(!isset($parcel_table))
	{
		require('config.php');
	}

	$field_string = "perm_token, order_num, parcel_id, parcel_size, d_terminal, mobile, email, status, order_date, creation_date";

	$value_string = "'" . $data['token'] . "','" .
		$data['params']['description'] . "','" .
		$data['parcel_id'] . "','" .
		$data['params']['size'] . "','" .
		$data['params']['target_machine'] . "','" .
		$data['params']['receiver']['phone'] . "','".
		$data['params']['receiver']['email'] . "','" .
		"UNPAID','" .
		date("Y-m-d H:i:s") . "','" .
		date("Y-m-d H:i:s") . "'";

	if($count >= 10)
	{
		$field_string .= ", first_name, last_name, post_code, town, street";
		$value_string .= ",'" . $data['sender_address']['first_name'] .
			"','" . $data['sender_address']['last_name'] . "','" .
			$data['sender_address']['post_code'] . "','" .
			$data['sender_address']['town'] . "','" .
			$data['sender_address']['street'] . "'";
	}
	if($count >= 11)
	{
		$field_string .= ", company_name";
		$value_string .= ",'" . $data['sender_address']['company_name'] .
			"'";
	}
	if($count >= 12)
	{
		$field_string .= ", building_number";
		$value_string .= ",'" . $data['sender_address']['building_number'] .
			"'";
	}
	if($count >= 13)
	{
		$field_string .= ", flat_number";
		$value_string .= ",'" . $data['sender_address']['flat_number'] .
			"'";
	}

	$ret = tep_db_query("INSERT into $table_prefix$parcel_table ($field_string) VALUES ($value_string)");
}

///
// add_return_parcel
//
// @brief Add the parcel information into the table
// @param The array with the fields in it
//
function add_return_parcel($data, $count)
{
	if(!isset($return_table))
	{
		require('config.php');
	}

	$field_string = "api_key, parcel_size, expire_at, sender_phone, sender_email, first_name, last_name, post_code, town, street, building_number, province, phone, code, actual_expire";

	$value_string = "'" . $data['token'] . "','" .
		$data['params']['parcel_size'] . "','" .
		$data['params']['expire_at'] . "','" .
		$data['params']['sender_phone'] . "','" .
		$data['params']['sender_email'] . "','" .
		$data['params']['address']['first_name'] . "','".
		$data['params']['address']['last_name'] . "','" .
		$data['params']['address']['post_code'] . "','" .
		$data['params']['address']['town'] . "','" .
		$data['params']['address']['street'] . "','" .
		$data['params']['address']['building_number'] . "','" .
		$data['params']['address']['province'] . "','" .
		$data['params']['address']['phone'] . "','" .
		$data['code'] . "','" .
		$data['actual_expire'] . "'";

	if($count >= 13)
	{
		$field_string .= ", rma";
		$value_string .= ",'" . $data['params']['rma'] . "'";
	}
	if($count >= 14)
	{
		$field_string .= ", add_desc1";
		$value_string .= ",'" . $data['params']['additional_description_1'] .
			"'";
	}
	if($count >= 15)
	{
		$field_string .= ", add_desc2";
		$value_string .= ",'" . $data['params']['additional_description_2'] .
			"'";
	}
	if($count >= 16)
	{
		$field_string .= ", add_desc3";
		$value_string .= ",'" . $data['params']['additional_description_3'] .
			"'";
	}
	if($count >= 17)
	{
		$field_string .= ", company_name";
		$value_string .= ",'" . $data['params']['company_name'] .
			"'";
	}
	if($count >= 18)
	{
		$field_string .= ", flat_number";
		$value_string .= ",'" . $data['params']['address']['flat_number'] . "'";
	}

	$ret = tep_db_query("INSERT into $table_prefix$return_table ($field_string) VALUES ($value_string)");
}

?>
