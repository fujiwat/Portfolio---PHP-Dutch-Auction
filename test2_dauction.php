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
//	echo "userid=".$_SESSION["userid"]." page=".$page." _SESSION['userid_check']=".$_SESSION['userid_check']."<br>";
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
			page_seller_list($mysqli, $_SESSION["userid"]);
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
//				echo "page=dafault<br>";
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
	function check_page_status()
	{
		if ( isset($_SESSION['userid_check']) !== true )
		{
			$_SESSION['userid_check'] = 1;
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
//		echo "move_page to ".$page_name."<br>";
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
		echo "<h1>Duch Auction</h1>";
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
//			echo "your userid(".$_POST["userid"].") and password is saved to the session data area<br>";		
		}
		else
		{
//			echo "session is already started.  your userid=(".$_SESSION["userid"]."<br>";
		}
//		echo "userid=".$_SESSION["userid"]." page=".$page."<br>";
	}
	
	function check_userid_password($mysqli)
	{
		$query = "SELECT * FROM dauction_usertable WHERE userid = '".$_POST["userid"]."' AND password = '".$_POST["password"]."'";
		$result = $mysqli->query($query);
		$ret = ( 0 < ($result->num_rows) )? true: false;
		$result->free();
		if ( $ret === true )
		{
//			echo "login succeeded."."<br>";
			my_session_start();
			$_SESSION["userid"] = $_POST["userid"];
			$_SESSION["password"] = $_POST["password"];
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

?>

<?php
	function page_seller_new($mysqli)
	{
//		echo "page_seller_new()<br>";
		if ( $_SESSION['userid_check'] === 1 )
		{
			if ( check_userid_password($mysqli) === false )
			{
				return;
			}
		}
		display_header("Enter your product");
		echo "
		<form action= '' method='POST' enctype='multipart/form-data'>
			Product name:  &nbsp;&nbsp;&nbsp;&nbsp;			<input type='text' name='auc_prod_name'><br>
			Starting price: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	<input type='text' name='auc_start_price'> EUR.<br>
			Minimum price: &nbsp;&nbsp;						<input type='text' name='auc_end_price'> EUR.<br>
			Price interval: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	<input type='text' name='auc_interval_price'> EUR.<br>
			Time interval: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	<input type='text' name='auc_interval_seconds'> seconds (if less than 5 then assume 5 seconds)<br>
			Photo of product: 								<input type='file' name='auc_prod_file'>
			<br><br>
			<input type='submit' name = seller_new_submit  value='submit'>
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
		$fileData = file_get_contents($_FILES["auc_prod_file"]["tmp_name"]); // アップロードされたファイルの内容を読み込む
		$fileName = $_FILES["auc_prod_file"]["name"]; // アップロードされたファイルの名前
		$fileType = $_FILES["auc_prod_file"]["type"]; // アップロードされたファイルの種類
		$nulldata = null;
		$bidBy = "--not yet--";
		$bidPrice = intval($_POST["auc_start_price"]);
		
		$query = "INSERT into dauction_productlist (auc_prod_name, auc_prod_file, prod_file_type, auc_by, auc_start, auc_end, auc_start_price, auc_end_price, auc_interval_price, auc_interval_seconds, bid_by, bid_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $mysqli->prepare($query);
//		printf("1=%s 2=gazou 3=%s 4=%s\n", "sbssssiiiisi", $_POST["auc_prod_name"], $fileType);
//		printf("5=%s 6=%s 7=%s\n", $_SESSION["userid"], date("Y-m-d H:i:s"), $nulldata);
//		printf("8=%d 9=%d 10=%d 11=%d\n", intval($_POST["auc_start_price"]), intval($_POST["auc_end_price"]), intval($_POST["auc_interval_price"]), intval($_POST["auc_interval_seconds"]) );
//		printf("12=%s 13=%d\n", "-not yet-", 0);
			
		$stmt->bind_param("sbssssiiiisi", $_POST["auc_prod_name"], $fileData, $fileType, 
			$_SESSION["userid"],  date("Y-m-d H:i:s"), $nulldata, 
			intval($_POST["auc_start_price"]), intval($_POST["auc_end_price"]), intval($_POST["auc_interval_price"]), intval($_POST["auc_interval_seconds"]), 
			$bidBy, $bidPrice
		);
//		printf("bindparm finished<br>");
		$stmt->execute();
//		printf("execute() finished<br>");
		if ( 0 < $stmt->affected_rows )
		{
//			echo "your product is successfully registered"."<br>";
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
		display_header_buttons_seller();
		display_list($mysqli, $auc_by, "%", false);
	}

	function display_list($mysqli, $auc_by, $bid_by, $bid)
	{
		echo "<br> <br>";
		$query = "UPDATE dauction_productlist SET bid_price = bid_price - auc_interval_price WHERE auc_end IS NULL AND (auc_end_price+auc_interval_price) <= bid_price ";
		$table = $mysqli->query($query);
		if ( $bid === true )
		{
			$query = "SELECT * FROM dauction_productlist WHERE auc_by like '".$auc_by."' AND bid_by like '".$bid_by."' AND auc_end IS NULL";
		}
		else 
		{
			$query = "SELECT * FROM dauction_productlist WHERE auc_by like '".$auc_by."' AND bid_by like '".$bid_by."'";
		}
//		echo "$query<br>";
		$table = $mysqli->query($query);
		$ret = ( 0 < ($table->num_rows) )? true: false;
		if ( $ret !== true )
		{
			return;
		}

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
		echo "    </tr>";
		echo "  </thead>";
		
		
		// 2.2. display contents
		echo "  <tbody>";
		do
		{
			echo "<tr>";
			$i = 0;
			foreach( $record as $element)
			{
				if ( $bid === true &&  $i==0 )
				{
					echo "  <th><input type='submit' name = bid".$element." value='Bid' /></th>";
				}			
				if ( $i++ !== 2 )
				{
					echo "<td>".$element."</td>";
				}
			}
			echo "</tr>";				
		} while ( $record = mysqli_fetch_assoc($table) );

		echo "  </tbody>";
		echo "</table>";

		$table->free();	
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
		display_header_buttons_bidder();
		echo "<form action= '' method='POST'>";
		display_list($mysqli, "%", "%", true);
		echo "</form>";
		
	}

	function page_bid($mysqli, $bidId)
	{
		display_header_buttons_bidder();
		$currentTime = date("Y-m-d H:i:s");
		$userid = $_SESSION["userid"];
		$query = "UPDATE dauction_productlist SET auc_end = '$currentTime', bid_by = '$userid' WHERE id = $bidId";
//		echo $query."<br>";
		$result = $mysqli->query($query);
		display_list($mysqli, "%", $userid, false);
	}

?>



<?php
	function page_bidder_list($mysqli)
	{
//		echo "page_bidder_list()<br>";
		if ( $_SESSION['userid_check'] === 1 )
		{
			if ( check_userid_password($mysqli) === false )
			{
				return;
			}
		}
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
			User ID:  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<input type='text' name='userid'>
			<br>
			Password: &nbsp;&nbsp;&nbsp;
			<input type='password' name='password'>
			<br><br>
			Login as:<br><br>
			(If you are seller):    &nbsp;&nbsp;&nbsp;<input type='submit' name = seller_new  value='Seller - new'>
			&nbsp;
			<input type='submit' name = seller_list value='Seller - list'>
			<br><br>
			(If you are bidder):    &nbsp;&nbsp;&nbsp;<input type='submit' name = bidder_new  value='Bidder - new'>
			&nbsp;
			<input type='submit' name = bidder_list value='Bidder - list'>
			<br>
		</form>
		";
	}
?>


