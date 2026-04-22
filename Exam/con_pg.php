<?php
$host="host=localhost";
$port="port=5432";
$dbname="dbname=Exam_DB";
$credential="user=tybcs password=msgcs";
$con=pg_connect("$host $port $dbname $credential") or die("Query failed to connect to database. Please ensure Exam_DB is created and credentials in con_pg.php are correct.");
if(!$dbname)
{
  echo "error unable to open ";	
}
else
{
	//echo "open Successfully!!";
}
?>