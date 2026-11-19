<?php
	$sn = "localhost";
	$un = "root";
	$pass = "";
	$db = "agrimia_db";

	$conn = mysqli_connect($sn, $un, $pass, $db);

	if (!$conn)
	{
		die("Connection failed: " . mysqli_connect_error());
	}
?>