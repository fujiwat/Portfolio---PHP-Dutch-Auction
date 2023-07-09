<?php
	// midterm test2
	// dutch auction
	my_session_start();			// need to start at first point
	include("login.php");
	$mysqli = new mysqli($host, $user, $password, $database);
	if ( $mysqli->connect_errno )
	{
		printf("connect failed: %s\n", $mysqli->connect_error);
		return;
	}
	echo "
	<html>
	  <head>
	    <style>
			.chart {
				display: flex;
				flex-direction: row-reverse;
			}
			.bar {
				width: 0;
				transition: width 1s;
				background-color: blue;
			}
		</style>
	  </head>
	  <body>
	";
	$page = check_page_status();
	debugecho ("userid=".$_SESSION["userid"]." page=".$page." _SESSION['userid_check']=".$_SESSION['userid_check']."<br>");
	switch ( $page )
	{
		case "seller_new":
			$_SESSION['userid_check'] = 1;
			page_seller_new($mysqli);
			break;
		case "seller_new2":
			$_SESSION['userid_check'] = 0;
			page_seller_new($mysqli);
			break;
		case "seller_new_submit":
			$_SESSION['userid_check'] = 0;
			page_seller_new_submit($mysqli);
			page_seller_list($mysqli, $_SESSION["userid"]);
			move_page("seller_list2");
			break;
		case "seller_list":
			$_SESSION['userid_check'] = 1;
			page_seller_list($mysqli, $_POST["userid"]);
			break;
		case "seller_list2":
			$_SESSION['userid_check'] = 0;
			page_seller_list($mysqli, $_SESSION["userid"]);
			break;
		case "bidder_new":
			$_SESSION['userid_check'] = 1;
			page_bidder_new($mysqli);
			break;
		case "bidder_new2":
			$_SESSION['userid_check'] = 0;
			page_bidder_new($mysqli);
			break;
		case "bidder_list":
			$_SESSION['userid_check'] = 1;
			page_bidder_list($mysqli);
			break;
		case "bidder_list2":
			$_SESSION['userid_check'] = 0;
			page_bidder_list($mysqli);
			break;
		case "login":
			page_login($mysqli, "");
			break;
		default:
			if ( substr($page, 0, 3) === "bid" )
			{
				$bidId = substr($page, 3);
				page_bid($mysqli, $bidId);
			}
			else
			{
//				debugecho ("page=dafault<br>");
				page_login($mysqli, "");
			}
			break;
	}
	echo "
		</body>
	<html>
	";
	page_close();
?>

<?php
	// miscellaneous
	function debugecho($str)
	{
//		echo $str;
	}
	
	function check_page_status()
	{
		if ( isset($_SESSION['userid_check']) !== true )
		{
			$_SESSION['userid_check'] = 1;
		}
		if ( isset($_POST["userid"]) )
		{
			$_SESSION["userid"] = $_POST["userid"];
		}
		$_SESSION["page"] = "init";
		setPost2SessionValue("seller_new");
		setPost2SessionValue("seller_new2");
		setPost2SessionValue("seller_new_submit");
		setPost2SessionValue("seller_list");
		setPost2SessionValue("seller_list2");
		setPost2SessionValue("bidder_new");
		setPost2SessionValue("bidder_new2");
		setPost2SessionValue("bidder_list");
		setPost2SessionValue("bidder_list2");
		for ( $i =0; $i < 100; $i++ )
		{
			setPost2SessionValue("bid$i");
		}
		return $_SESSION["page"];
	}
	
	function move_page($page_name)
	{
//		debugecho("move_page to ".$page_name."<br>");
		page_close();
		$_POST[$page_name] = 1;			// set the new page
		$_SESSION['userid_check'] =	0;
	}
	
	function setPost2SessionValue($str)
	{
		if ( isset($_POST[$str]) )
		{
			$_SESSION["page"] = $str;
		}
	}
	
	function display_header($text_h2)
	{
		echo "<h1 style='color:white; background-color: blue; padding: 10px'>Dutch Auction</h1>";
		echo "<br>";
		echo "<h2>$text_h2</h2>";
		echo "<br>";
	}
	
	function page_close()
	{
		unset( $_POST["seller_new"]  );
		unset( $_POST["seller_new2"]  );
		unset( $_POST["seller_new_submit"] );
		unset( $_POST["seller_list"] );
		unset( $_POST["seller_list2"] );
		unset( $_POST["bidder_new"]  );
		unset( $_POST["bidder_new2"]  );
		unset( $_POST["bidder_list"] );		
		unset( $_POST["bidder_list2"] );
		for ( $i =0; $i < 100; $i++ )
		{
			unset( $_POST["bid$i"] );
		}
	}
	
	function my_session_start()
	{
		if (session_status() == PHP_SESSION_NONE)
		{
			$lifetime=600;		// 10 minutes
			session_start();
			setcookie(session_name(),session_id(),time()+$lifetime);
//			debugecho("your userid(".$_POST["userid"].") and password is saved to the session data area<br>");
		}
		else
		{
//			debugecho("session is already started.  your userid=(".$_SESSION["userid"]."<br>");
		}
//		debugecho("userid=".$_SESSION["userid"]." page=".$page."<br>");
	}
	
	function check_userid_password($mysqli)
	{
		$query = "SELECT * FROM dauction_usertable WHERE userid = '".$_POST["userid"]."' AND password = '".$_POST["password"]."'";
		$result = $mysqli->query($query);
		$ret = ( 0 < ($result->num_rows) )? true: false;
		if ( $ret )
		{
			$currentTimestamp = time();
			$row = $result->fetch_assoc();
			$dbTimestamp = strtotime($row['s_start']);
			$timeDifference = $currentTimestamp - $dbTimestamp;			// time difference in second
			debugecho("timediff=$timeDifference s_id=".$row['s_id']." session_id=".session_id()."<br>");
			if ( $timeDifference <= 5*60 && $row['s_id'] !== session_id() )
			{	// you are comming from other PC.
				echo "<br><br><div style='color:red'>Dual login is not allowed.  Please use one PC, or wait 5 minutes (after your final operation) to change PC."."<br></div>";
				$result->free();
				return false;
			}
		}
		$result->free();
		if ( $ret === true )
		{
//			debugecho("login succeeded."."<br>");
			$_SESSION["userid"] = $_POST["userid"];
			$_SESSION["password"] = $_POST["password"];
			update_login($mysqli);
		}
		else
		{
			$message = "<div style='color:red'>login error.<br>
			Sorry, userid or password is not correct.<br>
			try again.<br><br></div>";
			page_login($mysqli, $message);
		}
		return $ret;	
	}

	function update_login($mysqli)
	{
		$s_id = session_id();
		$current_time = date("Y-m-d H:i:s");
		$query = "UPDATE dauction_usertable SET s_start = '$current_time', s_id = '$s_id' WHERE userid = '".$_SESSION["userid"]."' AND password = '".$_SESSION["password"]."'";
		debugecho($query."<br>");
		$result = $mysqli->query($query);
	}

	function display_header_buttons_seller()
	{
		echo "
		<form action= '' method='POST'>
			<input type='submit' name = seller_new2  value='Seller - new'>
			&nbsp;
			<input type='submit' name = seller_list2 value='Seller - list'>
			<br>
		</form>
		";
	}

	function display_header_buttons_bidder()
	{
		echo "
		<form action= '' method='POST'>
			<input type='submit' name = bidder_new2  value='Bidder - new'>
			&nbsp;
			<input type='submit' name = bidder_list2 value='Bidder - list'>
			<br>
		</form>
		";
	}
	function update_after_seconds($auc_start, $auc_start_price, $auc_end_price, $auc_interval_seconds, $auc_interval_price)
	{
		$currentTimestamp = time();
		$auc_start = strtotime($auc_start);
		$timeDifference = $currentTimestamp - $auc_start;			// time difference in second
		return $timeDifference % $auc_interval_seconds;
	}


	function calculate_current_price($auc_start, $auc_start_price, $auc_end_price, $auc_interval_seconds, $auc_interval_price)
	{
		$currentTimestamp = time();
		$auc_start = strtotime($auc_start);
		$timeDifference = $currentTimestamp - $auc_start;			// time difference in second
		$decrease_price =  intval( $timeDifference / $auc_interval_seconds ) * $auc_interval_price;
		$max_decrease_price = $auc_start_price - $auc_end_price;
		if ( $max_decrease_price < $decrease_price )
		{
			$decrease_price = $max_decrease_price;
		}
		$current_price = $auc_start_price - $decrease_price;
		return round($current_price);
	}

?>

<?php
	function page_seller_new($mysqli)
	{
//		debugecho("page_seller_new()<br>");
		if ( $_SESSION['userid_check'] === 1 )
		{
			if ( check_userid_password($mysqli) === false )
			{
				return;
			}
		}
		display_header("<div style='color:blue'>Enter your Item</div>");
		display_header_buttons_seller();
		echo "
		<form action= '' method='POST' enctype='multipart/form-data'>
			<label style='display: inline-block; width:150px;'>Item name:</label>	<input style='display: inline-block;' type='text' name='auc_prod_name' /><br>
			<label style='display: inline-block; width:150px;'>Starting price:</label>	<input style='display: inline-block;' type='text' name='auc_start_price' /> EUR<br>
			<label style='display: inline-block; width:150px;'>Minimum price:</label>	<input style='display: inline-block;' type='text' name='auc_end_price' /> EUR<br>
			<label style='display: inline-block; width:150px;'>Price interval:</label>	<input style='display: inline-block;' type='text' name='auc_interval_price' /> EUR  (If 0 then assume 1)<br>
			<label style='display: inline-block; width:150px;'>Time interval:</label>	<input style='display: inline-block;' type='text' name='auc_interval_seconds' /> If 0 then assume 1<br>
			<label style='display: inline-block; width:150px;'>Photo of item:</label>	<input style='display: inline-block;' type='file' name='auc_prod_file'>
			<br><br>
			<input type='submit' name = seller_new_submit  value='Register'>
		</form>
		";	
	}
	
	function page_seller_new_submit($mysqli)
	{
		if ( $_SESSION['userid_check'] === 1 )
		{
			if ( check_userid_password($mysqli) === false )
			{
				return;
			}
		}
		$auc_prod_name = $_POST["auc_prod_name"];
		$fileData = base64_encode(file_get_contents($_FILES["auc_prod_file"]["tmp_name"])); // アップロードされたファイルの内容を読み込む
		$fileName = $_FILES["auc_prod_file"]["name"]; // アップロードされたファイルの名前
		$fileType = $_FILES["auc_prod_file"]["type"]; // アップロードされたファイルの種類
		$auc_start = date("Y-m-d H:i:s");
		$nulldata = null;
		$bidBy = "--not yet--";
		$bidPrice = null;
		$auc_interval_price = max(1, intval($_POST["auc_interval_price"]));			// greater than 1
		$auc_interval_seconds = max(1, intval($_POST["auc_interval_seconds"]));		// greater than 1
		
		$query = "INSERT into dauction_productlist (auc_prod_name, auc_prod_file, prod_file_type, auc_by, auc_start, auc_end, auc_start_price, auc_end_price, auc_interval_price, auc_interval_seconds, bid_by, bid_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $mysqli->prepare($query);
//		debugecho(sprintf("1=%s 2=gazou 3=%s 4=%s\n", "sbssssiiiisi", $_POST["auc_prod_name"], $fileType);
//		debugecho(sprintf("5=%s 6=%s 7=%s\n", $_SESSION["userid"], date("Y-m-d H:i:s"), $nulldata);
//		debugecho(sprintf("8=%d 9=%d 10=%d 11=%d\n", intval($_POST["auc_start_price"]), intval($_POST["auc_end_price"]), intval($_POST["auc_interval_price"]), intval($_POST["auc_interval_seconds"]) );
//		debugecho(sprintf("12=%s 13=%d\n", "-not yet-", 0);
			
		$stmt->bind_param("ssssssiiiisi", $auc_prod_name, $fileData, $fileType, 
			$_SESSION["userid"], $auc_start, $nulldata, 
			intval($_POST["auc_start_price"]), intval($_POST["auc_end_price"]), $auc_interval_price, $auc_interval_seconds, 
			$bidBy, $bidPrice
		);
//		debugecho(sprintf("bindparm finished<br>");
		$stmt->execute();
//		debugecho(sprintf("execute() finished<br>");
		if ( 0 < $stmt->affected_rows )
		{
			debugecho("your product is successfully registered"."<br>");
		}
		else
		{
			echo "your product is not registered.   try again."."<br>";
		}
	}	

?>

<?php
	function page_seller_list($mysqli, $auc_by)
	{
		if ( $_SESSION['userid_check'] === 1 )
		{
			if ( check_userid_password($mysqli) === false )
			{
				return;
			}
		}
		display_header("<div style='color:blue'>Your selling list</div>");
		display_header_buttons_seller();
		display_list($mysqli, $auc_by, "%", false);
	}

	function display_list($mysqli, $auc_by, $bid_by, $bid)
	{
		echo "<br> <br>";
//		$query = "UPDATE dauction_productlist SET bid_price = bid_price - auc_interval_price WHERE auc_end IS NULL AND (auc_end_price+auc_interval_price) <= bid_price ";
//		$table = $mysqli->query($query);
		if ( $bid === true )
		{
			$query = "SELECT * FROM dauction_productlist WHERE auc_by like '".$auc_by."' AND bid_by like '".$bid_by."' AND auc_end IS NULL";
		}
		else 
		{
			$query = "SELECT * FROM dauction_productlist WHERE auc_by like '".$auc_by."' AND bid_by like '".$bid_by."'";
		}
//		debugecho("$query<br>");
		$table = $mysqli->query($query);
		$ret = ( 0 < ($table->num_rows) )? true: false;
		if ( $ret !== true )
		{
			return;
		}

		echo '<div style="text-align:center">Color:   <span style="color:red;">RED: Change soon(3sec)</span><span style="color:blue">&nbsp;&nbsp; BLUE: New price</span></div>';
		$record = mysqli_fetch_assoc($table);
		$columnNames = array_keys($record);
		echo "<table border='1'>";
		echo "  <thead>";
		echo "    <tr>";
		$i = 0;
		if ( $bid === true )
		{
			echo "  <th>bid</th>";
		}
		foreach( $columnNames as $element)
		{
			if ( $i++ !== 2 )
			{
				echo "  <th>".$element."</th>";
			}
		}
		echo "      <th>current_price</th>";
		echo "      <th>next_update</th>";
		echo "    </tr>";
		echo "  </thead>";
		
		
		// 2.2. display contents
		echo "  <tbody>";
		do
		{
			$current_price = calculate_current_price($record['auc_start'], $record["auc_start_price"], $record["auc_end_price"], $record["auc_interval_seconds"], $record["auc_interval_price"]);
			$update_after_seconds = update_after_seconds($record['auc_start'], $record["auc_start_price"], $record["auc_end_price"], $record["auc_interval_seconds"], $record["auc_interval_price"]);
			$update_before_seconds = $record['auc_interval_seconds'] - $update_after_seconds;
			if ( $bid )
			{
				if ( $current_price == $record["auc_end_price"] )
				{
					continue;
				}
			}

			echo "<tr>";
			$i = 0;
			foreach( $record as $element)
			{
				if ( $bid === true &&  $i==0 )
				{
					$id = $element;
					echo "  <td><input type='submit' name = bid".$record["id"]." value='Bid' /></td>";
				}
				if ( $i == 1 )
				{
					echo "<td><a target='dauction_detail' href=test2_sub_dauction_detail.php?id=".$record["id"].">".$element."</a></td>";
				}
				else if ( $i !== 2 )
				{
					echo "<td>".$element."</td>";
				}
				$i++;
			}
			if ( $record["bid_price"] === null )
			{
				if ( $current_price != $record["auc_end_price"] )
				{
					if ( $update_before_seconds < 3 )
					{
						echo '<td style="color:red; text-align:right">'.$current_price.'</td>';
						echo '<td style="color:red; text-align:right">'.$update_before_seconds.' Sec</td>';
					}
					else if ( $update_after_seconds < 5 )
					{
						echo '<td style="color:blue; text-align:right">'.$current_price.'</td>';
						echo '<td style="text-align:right">'.$update_before_seconds.' Sec</td>';
					}
					else
					{
						echo '<td style="color:black; text-align:right">'.$current_price.'</td>';
						echo '<td style="text-align:right">'.$update_before_seconds.' Sec</td>';
					}
				}
				else
				{
					echo '<td>finished</td>';
					echo '<td></td>';
				}
			}
			else
			{
				echo '<td></td>';
				echo '<td></td>';
			}

			echo "</tr>";				
		} while ( $record = mysqli_fetch_assoc($table) );

		echo "  </tbody>";
		echo "</table>";

		$table->free();	
//		header("Refresh: 2; url=test2_dauction_v2.php");							// auto refresh
	}
?>

<?php
	function page_bidder_new($mysqli)
	{
		if ( $_SESSION['userid_check'] === 1 )
		{
			if ( check_userid_password($mysqli) === false )
			{
				return;
			}
		}
		display_header("<div style='color:blue'>You can Bid now!</div>");
		display_header_buttons_bidder();
		echo "<form action= '' method='POST'>";
		display_list($mysqli, "%", "%", true);
		echo "</form>";
		
	}

	function page_bid($mysqli, $bidId)
	{
		display_header("<div style='color:blue'>Congrats!  You got the item!  This is the list of all your items.</div>");
		display_header_buttons_bidder();
		$currentTime = date("Y-m-d H:i:s");
		$userid = $_SESSION["userid"];
		$query = "SELECT * from dauction_productlist WHERE id = $bidId";
		$table = $mysqli->query($query);
		$row = mysqli_fetch_assoc($table);	
		$current_price = calculate_current_price($row["auc_start"], $row["auc_start_price"], $row["auc_end_price"], $row["auc_interval_seconds"], $row["auc_interval_price"]);	
		$query = "UPDATE dauction_productlist SET auc_end = '$currentTime', bid_by = '$userid', bid_price = $current_price WHERE id = $bidId";
//		debugecho($query."<br>");
		$result = $mysqli->query($query);
		display_list($mysqli, "%", $userid, false);
	}

?>

<?php
	function page_bidder_list($mysqli)
	{
//		debugecho("page_bidder_list()<br>");
		if ( $_SESSION['userid_check'] === 1 )
		{
			if ( check_userid_password($mysqli) === false )
			{
				return;
			}
		}
		display_header("<div style='color:blue'>This is your Bidded item.</div>");
		display_header_buttons_bidder();
		display_list($mysqli, "%", $_SESSION["userid"], false);
	}
?>

<?php
	function page_login($mysqli, $message)
	{
		display_header("Login");
		echo $message;
		echo "
		<form action= '' method='POST'>
			<label style='display: inline-block; width:12em'>User ID:</label>	<input type='text' name='userid'><br />
			<label style='display: inline-block; width:12em'>Password:</label>	<input type='password' name='password'><br />
			<br />
			<h3>Login as:</h3>
			<label style='display: inline-block; width:12em'> (If you are seller):</label>
			<input style='display: inline-block; width:8em' type='submit' name = seller_new  value='Seller - new'>
			<input style='display: inline-block; width:8em' type='submit' name = seller_list value='Seller - list'>
			<br>
			<label style='display: inline-block; width:12em'> (If you are bidder):</label>
			<input style='display: inline-block; width:8em' type='submit' name = bidder_new  value='Bidder - new'>
			<input style='display: inline-block; width:8em' type='submit' name = bidder_list value='Bidder - list'>
			<br>
		</form>
		";
	}
?>


