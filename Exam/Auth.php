<?php
session_start();
if(!isset($_SESSION['u_name']))
{
	header("Location:subject.php");
	exit();
}
?>