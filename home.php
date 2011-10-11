<?php
session_start();
include_once("config.php");

function user($from_id) {
$sql_from="SELECT * FROM users WHERE uid=".$from_id;
$result_user=pg_query($sql_from) or die(pg_last_error());
while ($from_user = pg_fetch_array($result_user)) { return $from_user['name']." ".$from_user['lastname']; }
}
if(isset($_SESSION['user_id']))
{
	if(!isset($_GET['user'])) { header("Location: /".$_SESSION['user_id']);}

	if(isset($_GET['user']) && isset($_GET['follow'])){
		$db->connect();
		$db->action("SELECT * FROM followers WHERE who='".$_SESSION['user_id']."' AND whom='".$_GET['user']."'");
		if(pg_num_rows($db->result)==0){
		$db->action("INSERT INTO followers (who,whom) VALUES (".$_SESSION['user_id'].",".$_GET['user'].")");
		$db->action("UPDATE counts SET following=following+1 WHERE user_id=".$_SESSION['user_id']);
		$db->action("UPDATE counts SET followers=followers+1 WHERE user_id=".$_GET['user']);
		}
		$db->close();
		header("Location: /{$_GET['user']}");
		exit();
	}
	if(isset($_GET['user']) && isset($_GET['unfollow'])){
		$db->connect();
		$db->action("DELETE FROM followers WHERE who='".$_SESSION['user_id']."' AND whom='".$_GET['user']."'");
		$db->action("UPDATE counts SET following=following-1 WHERE user_id=".$_SESSION['user_id']);
		$db->action("UPDATE counts SET followers=followers-1 WHERE user_id=".$_GET['user']);	
		$db->close();
		header("Location: /{$_GET['user']}");
		exit();
	}
}
echo $start;
echo "<title>Home</title>";
echo $after_title;
echo "<script>var js_title='Home';
$(document).ready(function(){ 
function wall()
	{
		$.ajax({
			url: \"actions/wall/wall.php\",
			cache: false,
			success: function(html) {
			$(\"#wall\").html(html);
			}
		});
	}
$('#send').submit(function(){
		if($('textarea#message').val()!='') 
		{
			$('input[type=submit]', this).attr('disabled', 'disabled');
			$.ajax({  
			type: \"POST\",  
			url: \"/actions/wall/send.php\",  
			data: \"message=\"+$(\"#message\").val(),  
			success: function(html){
			$(\"#content\").html(html);
			wall();
			}  
			});
			this.reset();
			$('input[type=submit]', this).removeAttr('disabled');
                }
                return false;  
            }); });</script>";
echo $after_scripts;
if(isset($_COOKIE['remember']) && $_COOKIE['remember']=="yes")
{
	if(!isset($_SESSION['user_id']))
	{
		$_SESSION['user_id']=$user_id;
	}
}
if(isset($_POST['email']) && isset($_POST['pass']))
{
	$email=$_POST['email'];
	$pass=md5($_POST['pass']);
	$db->connect();
	$db->action("SELECT * FROM users WHERE email='".$email."'");
	while ($sign = pg_fetch_array($db->result))
	{
		$passtwo=$sign['pass'];
		$user_id=$sign['uid'];
	}
	if($pass==$passtwo)
	{
		$_SESSION['user_id']=$user_id;
		if($_POST['remember']=="yes")
		{
			setcookie("remember", "yes", time()+3600*24*30, "/");
		}
		header("Location: home.php");
		exit();
	}
}

if(isset($_GET['act'])=="logout")
{
	unset($_COOKIE['remember']);
	unset($_SESSION['user_id']);
	header("Location: ./");
}

if(isset($_SESSION['user_id']))
{
	if(isset($_GET['user']))
	{
		$db->connect();
		$db->action("SELECT * FROM users WHERE uid='".$_GET['user']."'");
		if(pg_num_rows($db->result)==0) {
			header("Loaction: home.php");
		} else {
			while ($user = pg_fetch_array($db->result))
			{
			$name=$user['name'];
			$lastname=$user['lastname'];
			$online_time=$user['online_time'];
			$avatar=$user['avatar'];
			}
		}
		echo "<div style=\"top:0px; width:250px;\">";
		if($online_time+35>time()) echo "online <br>";
		if($avatar!="nothing") echo "<img src=\"./s/{$_GET['user']}/{$avatar}.jpg\"><br>";
		echo $name." ".$lastname;
	}
		if($_GET['user']==$_SESSION['user_id'])
		{ 
			echo " ({$lang['that_is_you']})";	
		} else
		{ 
			echo "<br><a href=\"mail.php?act=write&to={$_GET['user']}\">{$lang['write_a_message']}</a>";
			$db->action("SELECT * FROM followers WHERE who='".$_SESSION['user_id']."' AND whom='".$_GET['user']."'");
			echo "<br>";
			if(pg_num_rows($db->result)==0) {
				echo "<a href=\"{$_GET['user']}&follow\">{$lang['follow']}</a>";
				} else {
				echo $lang['following']." | <a href=\"{$_GET['user']}&unfollow\">{$lang['unfollow']}</a>";	
				}
		}
		$db->action("SELECT following FROM counts WHERE user_id=".$_GET['user']);
		$following=pg_fetch_array($db->result);
		if($following['following']>0) 
		{
			echo "<br>".$lang['following_this_people'];
			echo " ".$following['following']. " :";
			$db->action("SELECT * FROM followers WHERE who='".$_GET['user']."'");
			while($following_user=pg_fetch_array($db->result)) {
			$user=user($following_user['whom']);
			echo "<a href=\"{$following_user['whom']}\">$user</a>\n";
			}
		}
		
		$db->action("SELECT followers FROM counts WHERE user_id=".$_GET['user']);
		$followers=pg_fetch_array($db->result);
		if($followers['followers']>0) 
		{
			echo "<br>".$lang['followers'];
			echo " ".$followers['followers']. " :";
			$db->action("SELECT * FROM followers WHERE whom='".$_GET['user']."'");
			while($follower=pg_fetch_array($db->result)) 
			{
				$user=user($follower['who']);
				echo "<a href=\"{$follower['who']}\">$user</a>\n";
			}
		}
		echo "</div><div style=\"position: absolute; top:0px; left:250px;\">";
		if($_GET['user']==$_SESSION['user_id'])
		{
			echo "What's up:<br><form id=\"send\"><textarea id=\"message\" rows=\"2\" cols=\"50\"></textarea><br>
			<input type=\"submit\" value=\"Send\" id=\"submit\"></form>";
		}
		$db->action("SELECT * FROM wall WHERE user_id={$_GET['user']} ORDER BY uid DESC");
		echo "<div id=\"wall\">";
		if(pg_num_rows($db->result)!=0)
		{
			while($wall=pg_fetch_array($db->result))
			{
				echo "{$wall['message']}<br>";
			}
		}
		echo "</div>";
		echo "</div>";
}
else
{
	echo "<form method=\"POST\">
		{$lang['email']}: <input type=\"text\" name=\"email\">
		{$lang['password']}<input type=\"password\" name=\"pass\">
		<input type=\"submit\" value=\"{$lang['sign_in']}\">
		<br>Remember me <input type=\"checkbox\" name=\"remember\" value=\"yes\" checked>
		</form>";
}
$db->close();
echo $close;

?>
