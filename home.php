<?php
//ini_set("display_errors","1");
//ini_set("display_startup_errors","1");
//ini_set('error_reporting', E_ALL);
session_start();
include_once("config.php");
include_once("includes/wall.php");
include_once("includes/mail.php");

echo $start;
echo "<title>Home</title>";
echo $after_title;

if(user::on())
{
	if(!user::get()) { header("Location: /".USER_ID);}

	if(isset($_GET['user']) && isset($_GET['follow'])){
		$db->connect();
		$db->action("SELECT * FROM followers WHERE who='".$_SESSION['user_id']."' AND whom='".$_GET['user']."'");
		if(pg_num_rows($db->result)==0){
		$db->action("INSERT INTO followers (who,whom) VALUES (".USER_ID.",".THIS_USER.")");
		$db->action("UPDATE counts SET following=following+1 WHERE user_id=".USER_ID);
		$db->action("UPDATE counts SET followers=followers+1 WHERE user_id=".THIS_USER);
		}
		$db->close();
		header("Location: /{$_GET['user']}");
		exit();
	}
	if(user::get() && isset($_GET['unfollow'])){
		$db->connect();
		$db->action("DELETE FROM followers WHERE who='".USER_ID."' AND whom='".THIS_USER."'");
		$db->action("UPDATE counts SET following=following-1 WHERE user_id=".USER_ID);
		$db->action("UPDATE counts SET followers=followers-1 WHERE user_id=".THIS_USER);	
		$db->close();
		header("Location: /{$_GET['user']}");
		exit();
	}
}
echo "<script>
$(document).ready(function(){ 
function wall()
	{
		$.ajax({
			type: \"POST\",
			url: \"/actions.php\",
			data: \"ajax=wall\",
			cache: false,
			success: function(html) {
			$(\"#wall\").html(html);
			}
		});
	}
$('#send').submit(function(){
		if($('textarea#wallmessage').val()!='') 
		{
			$('input[type=submit]', this).attr('disabled', 'disabled');
			$.ajax({  
			type: \"POST\",  
			url: \"/actions/wall/send.php\",  
			data: \"ajax=wallsend&message=\"+$(\"#wallmessage\").val(),  
			success: function(html){
			$(\"#content\").html(html);
			wall();
			}  
			});
			this.reset();
			$('input[type=submit]', this).removeAttr('disabled');
                }
                return false;  
            });
$('#send_message').submit(function(){
				$('input[type=submit]', this).attr('disabled', 'disabled'); 
                $.ajax({  
                    type: \"POST\",  
                    url: \"/actions/messages/send_message.php\",  
                    data: \"&to={$_GET['user']}&subject=\"+$(\"#subject\").val()+\"&message=\"+$(\"#message\").val(),  
                    success: function(html){
						$(\"#message_to\").css(\"display\", \"none\");  
                    }  
                });
                $('input[type=submit]', this).removeAttr('disabled'); 
                return false;  
            });
            });</script>";
echo $after_scripts;

if(user::on())
{
	if(is_numeric(THIS_USER))
	{
		$db->connect();
		$db->action("SELECT * FROM users WHERE uid='".THIS_USER."'");
		if(pg_num_rows($db->result)==0) {
			$user_shows=FALSE;
		} else {
			$user_show=TRUE;
			
			while ($user = pg_fetch_array($db->result))
			{
				$name=$user['name'];
				$lastname=$user['lastname'];
				$online_time=$user['online_time'];
				$avatar=$user['avatar'];
				$about=$user['about'];
			}
		}
	} else {
	$user_show=FALSE;
	}
	
	if($user_show==TRUE)
	{
		echo "<div id=\"message_to\" style=\"position: absolute; margin:0 auto;display:none;\">";
		echo "<form id=\"send_message\">
		<a href=\"#\" style=\"float:right;\" onclick=\"$('#message_to').css('display', 'none');\">close</a>
		To: {$name} {$lastname}<br>
		Subject: <input type=\"text\" id=\"subject\"><br>
		Message: <textarea id=\"message\"></textarea><br>
		<input type=\"submit\" value=\"Send\">
		</form>";
		echo "</div>";
		echo "<div class=\"main_top\"><!--[if IE]><span id=\"ietop\"><span id=\"ietop\"><![endif]-->{$name} {$lastname}";
		if(user::page())
		{ 
			echo " ({$lang['that_is_you']})";	
		}
		if($online_time+35>time()) echo " online";
		echo "<!--[if IE]></span></span><![endif]--></div>";
		echo "<table class=\"main_table\"><tr><td valign=\"top\" width=\"200\">";
		echo "<img src=\"";
		if($avatar!="nothing") echo "./s/{$_GET['user']}/{$avatar}"; else echo "./images/nothing";
		echo ".jpg\"><br>"; 
		if(!user::page())
		{ 
			echo "<br><a href=\"#send\" onclick=\"$('#message_to').css('display', 'inline');\">{$lang['write_a_message']}</a>";
			$db->action("SELECT * FROM followers WHERE who='".USER_ID."' AND whom='".THIS_USER."'");
			echo "<br>";
			if(pg_num_rows($db->result)==0) {
				echo "<a href=\"{$_GET['user']}&follow\">{$lang['follow']}</a>";
				} else {
				echo $lang['following']." | <a href=\"{$_GET['user']}&unfollow\">{$lang['unfollow']}</a>";	
				}
		}
		$db->action("SELECT following FROM counts WHERE user_id=".THIS_USER);
		$following=pg_fetch_array($db->result);
		if($following['following']>0) 
		{
			echo "<br>".$lang['following_this_people'];
			echo " ".$following['following']. " :<br>";
			$db->action("SELECT whom FROM followers WHERE who='".THIS_USER."'");
			while($following_user=pg_fetch_array($db->result)) {
				echo "<a href=\"{$following_user['whom']}\">".get_avatar_small($following_user['whom'])."</a>";
			}
		}
		
		$db->action("SELECT followers FROM counts WHERE user_id=".THIS_USER);
		$followers=pg_fetch_array($db->result);
		if($followers['followers']>0) 
		{
			echo "<br>".$lang['followers'];
			echo " ".$followers['followers']. " :<br>";
			$db->action("SELECT who FROM followers WHERE whom='".THIS_USER."'");
			while($follower=pg_fetch_array($db->result)) 
			{
				echo "<a href=\"{$follower['who']}\">".get_avatar_small($follower['who'])."</a>";
			}
		}
		echo "</td>";
		
		echo "<td valign=\"top\" width=\"500\" style=\"border-left:1px solid;border-right:1px solid;\">";
		if($about!='')
		{
			echo "{$lang['about_me']}: {$about}<br>";
		}
		if(user::page())
		{
			echo "{$lang['whats_up']}:<br><form id=\"send\"><textarea id=\"wallmessage\" rows=\"2\" cols=\"35\"></textarea><br>
			<input type=\"submit\" value=\"{$lang['send']}\" id=\"submit\"></form>";
		}
		
		echo "<table id=\"wall\">";
		
		print_wall(THIS_USER);
		
		echo "</table>";
		echo "</td>";
		
		$db->action("SELECT * FROM albums WHERE user_id={$_GET['user']} AND delete=FALSE ORDER BY seq DESC LIMIT 5");
		echo "<td valign=\"top\" width=\"200\"><table>";
		if(pg_num_rows($db->result)==0)
		{
			echo "{$lang['no_albums']}";
		} else {
			echo "<a href=\"albums.php?user={$_GET['user']}\">{$lang['albums']}</a>";
			while($album=pg_fetch_array($db->result))
			{
			$name=$album['name'];
			$album_id=$album['album_id'];
			$count=$album['count'];
			$cover=$album['cover'];
			echo "<tr><td><a href=\"albums.php?user={$_GET['user']}&album={$album_id}\"><img src=\"./i/{$_GET['user']}/{$cover}.jpg\"></a></td><td><a href=\"albums.php?user={$_GET['user']}&album={$album_id}\">{$name}</a></td></tr>";
			}
		}
		echo "</table></td></tr></table>";
	} else {
	echo "USER DOES NOT EXIST";
	}
}
else
{
	echo "<form method=\"POST\" action=\"login.php\">
		{$lang['email']}: <input type=\"text\" name=\"email\">
		{$lang['password']}<input type=\"password\" name=\"pass\">
		<input type=\"submit\" value=\"{$lang['sign_in']}\">
		<br>Remember me <input type=\"checkbox\" name=\"remember\" value=\"yes\" checked>
		</form>";
}
$db->close();
echo $close;

?>
