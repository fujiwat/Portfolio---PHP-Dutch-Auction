<?php
	session_start();
	include("login.php");
	$mysqli = new mysqli($host, $user, $password, $database);

	$query = "SELECT prod_file_type, auc_prod_file FROM dauction_productlist WHERE id = ".$_GET['id'];
	$table = $mysqli->query($query);
	$ret = ( 0 < ($table->num_rows) )? true: false;
	if ( $ret !== true )
	{
		return;
	}
	$row = $table->fetch_assoc();
	$content = $row['auc_prod_file'];
	header('Content-type: ' . $row['prod_file_type']);
	echo base64_decode($content);
	$mysqli->close();
?>