<?php
	session_start();
	echo "
	<html>
	  <head>
		<meta name='viewport' content='width=device-width,initial-scale=1' />
	    <style>
			.c_table {
			}
			.c_left {
				width: 10em;
				border: 0px solid blue;
				float: left;
			}
			.c_right {
				width: 30em;
				text-align: left;
				border: 1px solid blue;
			}
		</style>
	  </head>
	  <body>
	";
	include("login.php");
	$mysqli = new mysqli($host, $user, $password, $database);
	if ( $mysqli->connect_errno )
	{
		printf("connect failed: %s\n", $mysqli->connect_error);
		return;
	}
	display_header( "Product detail:");
	debugecho( "userid=".$_SESSION["userid"]."<br>");
	display_header_buttons();
	display_detail($mysqli, $_GET["id"]);

	echo "
	  </body>
	</html>
	";
?>

<?php
	function debugecho($str)
	{
//		echo $str;
	}
	
	function display_header($text_h2)
	{
		echo "<h1 style='color:white; background-color: blue; padding: 10px'>Dutch Auction</h1>";
		echo "<br>";
		echo "<h2>$text_h2</h2>";
		echo "<br>";
	}

	function display_header_buttons()
	{
		echo '<div><a href="#" onclick="window.close();">Close this tab</a></div><div><br /></div>';
	}

	function display_detail($mysqli, $auc_id)
	{
		$query = "SELECT * FROM dauction_productlist WHERE id = $auc_id";
		debugecho("$query<br>");
		$table = $mysqli->query($query);
		$ret = ( 0 < ($table->num_rows) )? true: false;
		if ( $ret !== true )
		{
			return;
		}
		$row = $table->fetch_assoc();
		$columnNames = array_keys($record);
		echo "<div class='c_table'>";
		echo "<div class='c_left'>"."Product name:"."</div><div class='c_right'>".$row['auc_prod_name']."</div>";
		echo "<div class='c_left'>"."Auction ID:"."</div><div class='c_right'>".$row['id']."</div>";
		echo "<div class='c_left'>"."Auction by:"."</div><div class='c_right'>".$row['auc_by']."</div>";
		echo "<div class='c_left'>"."Auction Start:"."</div><div class='c_right'>".$row['auc_start']."</div>";
		echo "<div class='c_left'>"."Auction end:"."</div><div class='c_right'>&nbsp;".$row['auc_end']."</div>";
		echo "<div class='c_left'>"."Auction start Price:"."</div><div class='c_right'>".$row['auc_start_price']."</div>";
		echo "<div class='c_left'>"."Bid by:"."</div><div class='c_right'>".$row['bid_by']."</div>";
		echo "<div class='c_left'>"."Bid price"."</div><div class='c_right'>&nbsp;".$row['bid_price']."</div>";
		echo "<div class='c_left'>"."Product Information:"."</div><div class='c_right'>";
		if ( $row['prod_file_type'] != null ) 
		{
			echo "<img style='max-width:20em' src='test2_sub_content.php?id=".$row['id']."' max-width='40em'>";
		}
		else
		{
			echo "&nbsp;";
		}
		echo "</div>";
		echo "</div>";	// end of c_table
		$table->free();	
	}
?>