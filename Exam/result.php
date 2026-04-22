<?php
session_start();
include 'con_pg.php';
include 'user_Header.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Online Quiz  - Result </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style" rel="stylesheet" type="text/css">
</head>
<body>
<?php

//echo '<p align="right"><a href="signout.php"><font size="4" color="red">Signout</font></a></p>';

extract($_SESSION);
$login = $login ?? '';
$rs=pg_query($con, "select t.test_name,t.total_que,r.test_date,r.score from test t, result r where t.test_id=r.test_id and r.login='$login'") or die(pg_last_error($con));

echo "<p align=center><font size=10 color=#841B2D><b> RESULT</b></font></p>";
if(pg_num_rows($rs)<1)
{
	echo "<br><br><p align=center><font color=#8A3324 size=10> YOU HAVE NOT GIVEN ANY EXAM</font></p>";
	exit;
}
echo "<table border=10 align=center ><tr><td align=center width=300><font size=7>TEST NAME <td align=center width=100> Total<br> Question <td align=center width=100> Score</font>";
while($row=pg_fetch_row($rs))
{
echo "<tr><td align=center width=300><font size=5>$row[0] <td align=center width=100> $row[1] <td align=center width=100> $row[3]</font>";
}
echo "</table>";
?>
</body>
</html>