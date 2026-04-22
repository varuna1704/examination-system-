<?php
session_start();
$submit = $_POST['submit'] ?? '';
extract($_POST);
extract($_SESSION);
include 'con_pg.php';
if($submit=='Finish')
{
    pg_query($con, "delete from useranswer where sess_id='".session_id()."'") or die(pg_last_error($con));
    unset($_SESSION['qn']);
    header("location:index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Online quiz review</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"><!-- comment -->
        <link href="style.css" rel="stylesheet" type="text/css"/><!-- comment -->        
    </head>
    <body>
        <?php
        echo "<center>";
        include 'con_pg.php';
        echo "</center>";
        echo '<p align="right"><a href="Logout.php"><font size="4" color="red">Logout</font></a></p>';
        echo '<p align=center><font size=8 color=red>Review Test Question</font></p>';
        if(!isset($_SESSION['qn']))
        {
            $_SESSION['qn']=0;
        }
        else if($submit=='Next Question')
        {
            $_SESSION['qn']=$_SESSION['qn']+1;
        }
        $result=pg_query($con, "select * from useranswer where sess_id='". session_id()."'") or die(pg_last_error($con));
        pg_result_seek($result,$_SESSION['qn']);
        $row= pg_fetch_row($result);
        echo "<form method=post action=review.php>";
        echo "<table width=100%><tr><td width=30>";
        $n=$_SESSION['qn']+1;
        echo "<tr><td align=center><font size=6>Que ".$n.": ".($row[2] ?? 'N/A')."</font>";
        echo "<tr><td align=center class=".((isset($row[7]) && $row[7]==1)?'tans':'style8')."><font size=5>".($row[3] ?? '')."</font>";
        echo "<tr><td align=center class=".((isset($row[7]) && $row[7]==2)?'tans':'style8')."><font size=5>".($row[4] ?? '')."</font>";
        echo "<tr><td align=center class=".((isset($row[7]) && $row[7]==3)?'tans':'style8')."><font size=5>".($row[5] ?? '')."</font>";
        echo "<tr><td align=center class=".((isset($row[7]) && $row[7]==4)?'tans':'style8')."><font size=5>".($row[6] ?? '')."</font>";
        if($_SESSION['qn'] < pg_num_rows($result)-1)
        {
            echo "<tr><td align=center><input type=submit name=submit value='Next Question'></form></td></tr>";
        }
        else
        {
            echo "<tr><td align=center><input type=submit name=submit value='Finish'></form></td></tr>";
        }
        echo "</table>"; 
        ?>
    </body>
</html>