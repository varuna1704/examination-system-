<?php
session_start();
error_reporting(1);
$submit = $_POST['submit'] ?? '';
$ans = $_POST['ans'] ?? null;
include 'con_pg.php';
include 'user_Header.php';
extract($_GET);
extract($_POST);
extract($_SESSION);

$tid = $_SESSION['tid'] ?? 0;
$query = "select * from question where test_id=$tid";
$result = pg_query($con, $query) or die(pg_last_error($con));

if(isset($subid) && isset($testid))
{
    $_SESSION['sid']=$subid;
    $_SESSION['tid']=$testid;
    header("location:quiz.php");
    exit;
}

?>
<!DOCTYPE html>
<html>
    <head>
    <title>Online quiz</title>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8"><!-- comment -->
    <link rel="stylesheet" typpe="text/css" href="style.css"/>
    </head>
        <body>
        <?php
        
        if(!isset($_SESSION['qn']))
        {
            $_SESSION['qn']=0;
            pg_query($con, "delete from useranswer where sess_id='".session_id()."'") or die(pg_last_error($con));
            $_SESSION['true_ans']=0;
        }
        else
        {
            if($submit=='Next Question' && isset($ans))
            {
                $row = pg_fetch_row($result, $_SESSION['qn']);
                pg_query($con, "insert into useranswer(sess_id,test_id,que_desc,ans1,ans2,ans3,ans4,true_ans,your_ans) values ('".session_id()."',$tid,'$row[2]','$row[3]','$row[4]','$row[5]','$row[6]','$row[7]','$ans')") or die(pg_last_error($con));
                if($ans==$row[7])
                {
                    $_SESSION['true_ans']=$_SESSION['true_ans']+1;
                }
                $_SESSION['qn']=$_SESSION['qn']+1;
            }
            else if($submit=='Get Result' && isset($ans))
            {
                $row = pg_fetch_row($result, $_SESSION['qn']);
                pg_query($con, "INSERT INTO useranswer(sess_id,test_id,que_desc,ans1,ans2,ans3,ans4,true_ans,your_ans) values('".session_id()."',$tid,'$row[2]','$row[3]','$row[4]','$row[5]','$row[6]','$row[7]','$ans')") or die(pg_last_error($con));
                if($ans==$row[7])
                {
                    $_SESSION['true_ans']=$_SESSION['true_ans']+1;
                }
                echo "<font size=8><p align=center><b>Result</b></p></font>";
                $_SESSION['qn']=$_SESSION['qn']+1;
                echo "<table align=center><tr class=tot><td>Total Question</td><td>".$_SESSION['qn']."</td></tr>";
                echo "<tr class=tot><td>True answer</td><td>".$_SESSION['true_ans']."</td></tr>";
                $w=$_SESSION['qn'] - $_SESSION['true_ans'];
                echo "<tr class=tot><td>Wrong answer</td><td>".$w."</td></tr>";
                echo "</table>";
                $login = $_SESSION['u_name'] ?? 'guest';
                pg_query($con, "insert into result(login,test_id,test_date,score) values('$login',$tid,'".date("Y-m-d")."',".$_SESSION['true_ans'].")");
                echo "<h1 align=center><a href=review.php>Review Question</a></h1>";
                unset($_SESSION['qn']);
                exit;
            }
        }
        
        if($_SESSION['qn'] > pg_num_rows($result)-1)
        {
            unset($_SESSION['qn']);
            echo "<h1>Some Error occurred</h1>";
            echo "Please <a href=index.php>Start Again</a>";
            exit;
        }
        $row = pg_fetch_row($result, $_SESSION['qn']);
        echo "<form method=post action=quiz.php>";
        echo "<table width=100%><tr><td align=center>&nbsp;</td>";
        $n=$_SESSION['qn']+1;
        echo "<tr><td align><font size=6>Que ".$n.": ".($row[2] ?? '')."</font></td></tr>";
        echo "<tr><td align><font size=5><input type=radio name=ans value=1> ".($row[3] ?? '')."</font></td></tr>";
        echo "<tr><td align><font size=5><input type=radio name=ans value=2> ".($row[4] ?? '')."</font></td></tr>";
        echo "<tr><td align><font size=5><input type=radio name=ans value=3> ".($row[5] ?? '')."</font></td></tr>";
        echo "<tr><td align> <font size=5><input type=radio name=ans value=4> ".($row[6] ?? '')."</font></td></tr>";
        if($_SESSION['qn'] < pg_num_rows($result)-1)
        {
            echo "<tr><td align=left><input type=submit name=submit value='Next Question'></td></tr></form>";
        }
        else
        {
            echo "<tr><td align=left><input type=submit name=submit value='Get Result'></td></tr></form>";
        }
        
        echo "</table>";
        ?>
    </body>
</html>