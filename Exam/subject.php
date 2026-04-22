<html>
    <head>
        <title>Online Quiz test</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link href="Style1.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
        <?php
        
        include 'user_Header.php';
        
        //echo '<p align="right"><a href="Logout.php"><font size="4" color="red">Sign Out</font></a></p>';
        include 'con_pg.php';
        echo "<table align=center>";
        echo "<tr><td align><font size=6 color=black><b><br>SELECT SUBJECT TO GIVE<br></b></font></td></tr>";
        $result= pg_query($con, "select * from subject") or die(pg_last_error($con));
        echo "<tr><td><table></td></tr>";
        while($row=pg_fetch_row($result))
        {
            echo "<tr><td><a href=quiz.php?sub_id=$row[0]><br><font size=5>$row[1]<br></font></a>";
        }
        echo "</table></table>";
       ?>
    </body>
</html>