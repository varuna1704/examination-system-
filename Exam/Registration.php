<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Registration</title>
<link type="text/css" rel="stylesheet" href="style.css"/> 
</head>
<body>
<?php
require('con_pg.php');
include('user_Header.php');
if(isset($_REQUEST['f_name']))
{
	$f_name=stripslashes($_REQUEST['f_name']);
	$f_name=pg_escape_string($con,$f_name);
        $m_name=stripslashes($_REQUEST['m_name']);
	$m_name=pg_escape_string($con,$m_name);
        $l_name=stripslashes($_REQUEST['l_name']);
	$l_name=pg_escape_string($con,$l_name);
        $u_name=stripslashes($_REQUEST['u_name']);
	$u_name=pg_escape_string($con,$u_name);
        $u_email=stripslashes($_REQUEST['u_email']);
	$u_email=pg_escape_string($con,$u_email);
        $u_pass=stripslashes($_REQUEST['u_pass']);
	$u_pass=pg_escape_string($con,$u_pass);
        $u_age=stripslashes($_REQUEST['u_age']);
	$u_age=pg_escape_string($con,$u_age); 
        $u_mob=stripslashes($_REQUEST['u_mob']);
	$u_mob=pg_escape_string($con,$u_mob);        
        $u_adr=stripslashes($_REQUEST['u_adr']);
	$u_adr=pg_escape_string($con,$u_adr);
        $query="insert into users(f_name,m_name,l_name,u_name,u_email,u_pass,u_age,u_mob,u_adr)VALUES('$f_name','$m_name','$l_name','$u_name','$u_email','$u_pass','$u_age','$u_mob','$u_adr')";
	$rs=pg_query($query);
	if($rs)
	{
		echo "<div class='form'>
                <h3>You Are Registered Successfully</h3><br/>
               Click here to <a href='index.php'>Login</a></div>";
	}
}
else
{
?>
<div class="img"></div><div class="ig"></div>
<div class="regs"><h2>Registration</h2></div>
<div class="fill"><p>Please fill in your credentials to Register</p></div>
<div class="regs_msg">
<form name="registration" action=" " method="post">
&nbsp&nbsp First Name : <input type="text" name="f_name" placeholder="Enter First Name" required/><br><br>
Middle Name : <input type="text" name="m_name" placeholder="Enter Middle Name" required/><br><br>
&nbsp&nbsp&nbsp Last Name : <input type="text" name="l_name" placeholder="Enter Last Name" required/><br><br>
&nbsp&nbsp&nbspUser Name : <input type="text" name="u_name" placeholder="Enter User Name" required/><br><br>
&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Email-id : <input type="text" name="u_email" placeholder="Enter Email id" required/><br><br>
&nbsp&nbsp&nbsp&nbsp Password : <input type="password" name="u_pass" placeholder="Enter Password" required/><br><br>
&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
Age : <input type="text" name="u_age" placeholder="Enter age" required/><br><br>
&nbsp&nbsp Mobile No. : <input type="text" name="u_mob" placeholder="Enter mobile no" required/><br><br>
&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Address : <input type="text" name="u_adr" placeholder="Enter address" required/><br><br>
<input type="submit" name="submit" value="Register"/>&nbsp<a href="index.php">Cancel</a></div>
<div class="ft">@copyright</div>

<?php } ?>
   
</form>
</body>
</html>