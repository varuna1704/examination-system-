<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body>
<?php
require("con_pg.php");
include("user_Header.php");
session_start();
if(isset($_POST['u_name']))
{
	$u_name=stripslashes($_REQUEST['u_name']);
	$u_name=pg_escape_string($con,$u_name);  
	$u_pass=stripslashes($_REQUEST['u_pass']);
	$u_pass=pg_escape_string($con,$u_pass);
	$query="SELECT * FROM users WHERE u_name='$u_name' and u_pass='$u_pass'";
	$result=pg_query($con,$query) or die(pg_last_error());
	$rows=pg_num_rows($result);
	if($rows==1)
	{
		$_SESSION['u_name']=$u_name;
		header("Location: subject.php");
	}
	else
	{
		echo "<div class='form'>
		      <h3>Username/Password is incorrect</h3>
			  <br/>Click here to <a href='Registration.php'>Register</a></div>";
	}
}
else
{
?>
<div class="img"></div>
<div class="acc"><h2>Account Login</h2></div>
<!--<div class="fill"><p>Please fill in your credentials to login.</p></div>-->
<div class="msg">
<form action=" " method="post" name="login">
Username : <input type="text" name="u_name" placeholder="Username" required /><br>
Password : <input type="password" name="u_pass" placeholder="Password" required /><br>
<div class="ac">Already have an account <input type="submit" name="submit" value="Log In"></div>
</form></div>
<div class="user">
<p>Don't have a account ? <a href="Registration.php">Sign up now</a></p>
</div>

<div class="footer"></div>
<?php } ?>
</body>
</html>