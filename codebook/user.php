<!-- ******** status_system.php ******** -->

<?php
include_once("../php_includes/check_login_status.php");
if($user_ok != true || $log_username == "") {
	exit();
}
?><?php
if (isset($_POST['action']) && $_POST['action'] == "status_post"){
	// Make sure post data is not empty
	if(strlen($_POST['data']) < 1){
		mysqli_close($db_conx);
	    echo "data_empty";
	    exit();
	}
	// Make sure type is either a or c
	if($_POST['type'] != "a" || $_POST['type'] != "c"){
		mysqli_close($db_conx);
	    echo "type_unknown";
	    exit();
	}
	// Clean all of the $_POST vars that will interact with the database
	$type = preg_replace('#[^a-z]#', '', $_POST['type']);
	$account_name = preg_replace('#[^a-z0-9]#i', '', $_POST['user']);
	$data = htmlentities($_POST['data']);
	$data = mysqli_real_escape_string($db_conx, $data);
	// Make sure account name exists (the profile being posted on)
	$sql = "SELECT COUNT(id) FROM users WHERE username='$account_name' AND activated='1' LIMIT 1";
	$query = mysqli_query($db_conx, $sql);
	$row = mysqli_fetch_row($query);
	if($row[0] < 1){
		mysqli_close($db_conx);
		echo "$account_no_exist";
		exit();
	}
	// Insert the status post into the database now
	$sql = "INSERT INTO status(account_name, author, type, data, postdate) 
			VALUES('$account_name','$log_username','$type','$data',now())";
	$query = mysqli_query($db_conx, $sql);
	$id = mysqli_insert_id($db_conx);
	mysqli_query($db_conx, "UPDATE status SET osid='$id' WHERE id='$id' LIMIT 1");
	// Count posts of type "a" for the person posting and evaluate the count
	$sql = "SELECT COUNT(id) FROM status WHERE author='$log_username' AND type='a'";
    $query = mysqli_query($db_conx, $sql); 
	$row = mysqli_fetch_row($query);
	if ($row[0] > 9) { // If they have 10 or more posts of type a
		// Delete their oldest post if you want a system that auto flushes the oldest
		// (you can auto flush for post types c and b if you wish to also)
		$sql = "SELECT id FROM status WHERE author='$log_username' AND type='a' ORDER BY id ASC LIMIT 1";
    	$query = mysqli_query($db_conx, $sql); 
		$row = mysqli_fetch_row($query);
		$oldest = $row[0];
		mysqli_query($db_conx, "DELETE FROM status WHERE osid='$oldest'");
	}
	// Insert notifications to all friends of the post author
	$friends = array();
	$query = mysqli_query($db_conx, "SELECT user1 FROM friends WHERE user2='$log_username' AND accepted='1'");
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) { array_push($friends, $row["user1"]); }
	$query = mysqli_query($db_conx, "SELECT user2 FROM friends WHERE user1='$log_username' AND accepted='1'");
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) { array_push($friends, $row["user2"]); }
	for($i = 0; $i < count($friends); $i++){
		$friend = $friends[$i];
		$app = "Status Post";
		$note = $log_username.' posted on: <br /><a href="user.php?u='.$account_name.'#status_'.$id.'">'.$account_name.'&#39;s Profile</a>';
		mysqli_query($db_conx, "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES('$friend','$log_username','$app','$note',now())");			
	}
	mysqli_close($db_conx);
	echo "post_ok|$id";
	exit();
}
?><?php 
//action=status_reply&osid="+osid+"&user="+user+"&data="+data
if (isset($_POST['action']) && $_POST['action'] == "status_reply"){
	// Make sure data is not empty
	if(strlen($_POST['data']) < 1){
		mysqli_close($db_conx);
	    echo "data_empty";
	    exit();
	}
	// Clean the posted variables
	$osid = preg_replace('#[^0-9]#', '', $_POST['sid']);
	$account_name = preg_replace('#[^a-z0-9]#i', '', $_POST['user']);
	$data = htmlentities($_POST['data']);
	$data = mysqli_real_escape_string($db_conx, $data);
	// Make sure account name exists (the profile being posted on)
	$sql = "SELECT COUNT(id) FROM users WHERE username='$account_name' AND activated='1' LIMIT 1";
	$query = mysqli_query($db_conx, $sql);
	$row = mysqli_fetch_row($query);
	if($row[0] < 1){
		mysqli_close($db_conx);
		echo "$account_no_exist";
		exit();
	}
	// Insert the status reply post into the database now
	$sql = "INSERT INTO status(osid, account_name, author, type, data, postdate)
	        VALUES('$osid','$account_name','$log_username','b','$data',now())";
	$query = mysqli_query($db_conx, $sql);
	$id = mysqli_insert_id($db_conx);
	// Insert notifications for everybody in the conversation except this author
	$sql = "SELECT author FROM status WHERE osid='$osid' AND author!='$log_username' GROUP BY author";
	$query = mysqli_query($db_conx, $sql);
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$participant = $row["author"];
		$app = "Status Reply";
		$note = $log_username.' commented here:<br /><a href="user.php?u='.$account_name.'#status_'.$osid.'">Click here to view the conversation</a>';
		mysqli_query($db_conx, "INSERT INTO notifications(username, initiator, app, note, date_time) 
		             VALUES('$participant','$log_username','$app','$note',now())");
	}
	mysqli_close($db_conx);
	echo "reply_ok|$id";
	exit();
}
?><?php 
if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
	if(!isset($_POST['statusid']) || $_POST['statusid'] == ""){
		mysqli_close($db_conx);
		echo "status id is missing";
		exit();
	}
	$statusid = preg_replace('#[^0-9]#', '', $_POST['statusid']);
	// Check to make sure this logged in user actually owns that comment
	$query = mysqli_query($db_conx, "SELECT account_name, author FROM status WHERE id='$statusid' LIMIT 1");
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$account_name = $row["account_name"]; 
		$author = $row["author"];
	}
    if ($author == $log_username || $account_name == $log_username) {
		mysqli_query($db_conx, "DELETE FROM status WHERE osid='$statusid'");
		mysqli_close($db_conx);
	    echo "delete_ok";
		exit();
	}
}
?><?php 
if (isset($_POST['action']) && $_POST['action'] == "delete_reply"){
	if(!isset($_POST['replyid']) || $_POST['replyid'] == ""){
		mysqli_close($db_conx);
		exit();
	}
	$replyid = preg_replace('#[^0-9]#', '', $_POST['replyid']);
	// Check to make sure the person deleting this reply is either the account owner or the person who wrote it
	$query = mysqli_query($db_conx, "SELECT osid, account_name, author FROM status WHERE id='$replyid' LIMIT 1");
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$osid = $row["osid"];
		$account_name = $row["account_name"];
		$author = $row["author"];
	}
    if ($author == $log_username || $account_name == $log_username) {
		mysqli_query($db_conx, "DELETE FROM status WHERE id='$replyid'");
		mysqli_close($db_conx);
	    echo "delete_ok";
		exit();
	}
}
?>

<!-- ******** template_status.php ******** -->

<?php
$status_ui = "";
$statuslist = "";
if($isOwner == "yes"){
	$status_ui = '<textarea id="statustext" onkeyup="statusMax(this,250)" placeholder="What&#39;s new with you '.$u.'?"></textarea>';
	$status_ui .= '<button id="statusBtn" onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\')">Post</button>';
} else if($isFriend == true && $log_username != $u){
	$status_ui = '<textarea id="statustext" onkeyup="statusMax(this,250)" placeholder="Hi '.$log_username.', say something to '.$u.'"></textarea>';
	$status_ui .= '<button id="statusBtn" onclick="postToStatus(\'status_post\',\'c\',\''.$u.'\',\'statustext\')">Post</button>';
}
?><?php 
$sql = "SELECT * FROM status WHERE account_name='$u' AND type='a' OR account_name='$u' AND type='c' ORDER BY postdate DESC LIMIT 20";
$query = mysqli_query($db_conx, $sql);
$statusnumrows = mysqli_num_rows($query);
while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
	$statusid = $row["id"];
	$account_name = $row["account_name"];
	$author = $row["author"];
	$postdate = $row["postdate"];
	$data = $row["data"];
	$data = nl2br($data);
	$data = str_replace("&amp;","&",$data);
	$data = stripslashes($data);
	$statusDeleteButton = '';
	if($author == $log_username || $account_name == $log_username ){
		$statusDeleteButton = '<span id="sdb_'.$statusid.'"><a href="#" onclick="return false;" onmousedown="deleteStatus(\''.$statusid.'\',\'status_'.$statusid.'\');" title="DELETE THIS STATUS AND ITS REPLIES">delete status</a></span> &nbsp; &nbsp;';
	}
	// GATHER UP ANY STATUS REPLIES
	$status_replies = "";
	$query_replies = mysqli_query($db_conx, "SELECT * FROM status WHERE osid='$statusid' AND type='b' ORDER BY postdate ASC");
	$replynumrows = mysqli_num_rows($query_replies);
    if($replynumrows > 0){
        while ($row2 = mysqli_fetch_array($query_replies, MYSQLI_ASSOC)) {
			$statusreplyid = $row2["id"];
			$replyauthor = $row2["author"];
			$replydata = $row2["data"];
			$replydata = nl2br($replydata);
			$replypostdate = $row2["postdate"];
			$replydata = str_replace("&amp;","&",$replydata);
			$replydata = stripslashes($replydata);
			$replyDeleteButton = '';
			if($replyauthor == $log_username || $account_name == $log_username ){
				$replyDeleteButton = '<span id="srdb_'.$statusreplyid.'"><a href="#" onclick="return false;" onmousedown="deleteReply(\''.$statusreplyid.'\',\'reply_'.$statusreplyid.'\');" title="DELETE THIS COMMENT">remove</a></span>';
			}
			$status_replies .= '<div id="reply_'.$statusreplyid.'" class="reply_boxes"><div><b>Reply by <a href="user.php?u='.$replyauthor.'">'.$replyauthor.'</a> '.$replypostdate.':</b> '.$replyDeleteButton.'<br />'.$replydata.'</div></div>';
        }
    }
	$statuslist .= '<div id="status_'.$statusid.'" class="status_boxes"><div><b>Posted by <a href="user.php?u='.$author.'">'.$author.'</a> '.$postdate.':</b> '.$statusDeleteButton.' <br />'.$data.'</div>'.$status_replies.'</div>';
	if($isFriend == true || $log_username == $u){
	    $statuslist .= '<textarea id="replytext_'.$statusid.'" class="replytext" onkeyup="statusMax(this,250)" placeholder="write a comment here"></textarea><button id="replyBtn_'.$statusid.'" onclick="replyToStatus('.$statusid.',\''.$u.'\',\'replytext_'.$statusid.'\',this)">Reply</button>';	
	}
}
?>
<script>
function postToStatus(action,type,user,ta){
	var data = _(ta).value;
	if(data == ""){
		alert("Type something first weenis");
		return false;
	}
	_("statusBtn").disabled = true;
	var ajax = ajaxObj("POST", "php_parsers/status_system.php");
	ajax.onreadystatechange = function() {
		if(ajaxReturn(ajax) == true) {
			var datArray = ajax.responseText.split("|");
			if(datArray[0] == "post_ok"){
				var sid = datArray[1];
				data = data.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\n/g,"<br />").replace(/\r/g,"<br />");
				var currentHTML = _("statusarea").innerHTML;
				_("statusarea").innerHTML = '<div id="status_'+sid+'" class="status_boxes"><div><b>Posted by you just now:</b> <span id="sdb_'+sid+'"><a href="#" onclick="return false;" onmousedown="deleteStatus(\''+sid+'\',\'status_'+sid+'\');" title="DELETE THIS STATUS AND ITS REPLIES">delete status</a></span><br />'+data+'</div></div><textarea id="replytext_'+sid+'" class="replytext" onkeyup="statusMax(this,250)" placeholder="write a comment here"></textarea><button id="replyBtn_'+sid+'" onclick="replyToStatus('+sid+',\'<?php echo $u; ?>\',\'replytext_'+sid+'\',this)">Reply</button>'+currentHTML;
				_("statusBtn").disabled = false;
				_(ta).value = "";
			} else {
				alert(ajax.responseText);
			}
		}
	}
	ajax.send("action="+action+"&type="+type+"&user="+user+"&data="+data);
}
function replyToStatus(sid,user,ta,btn){
	var data = _(ta).value;
	if(data == ""){
		alert("Type something first weenis");
		return false;
	}
	_("replyBtn_"+sid).disabled = true;
	var ajax = ajaxObj("POST", "php_parsers/status_system.php");
	ajax.onreadystatechange = function() {
		if(ajaxReturn(ajax) == true) {
			var datArray = ajax.responseText.split("|");
			if(datArray[0] == "reply_ok"){
				var rid = datArray[1];
				data = data.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\n/g,"<br />").replace(/\r/g,"<br />");
				_("status_"+sid).innerHTML += '<div id="reply_'+rid+'" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_'+rid+'"><a href="#" onclick="return false;" onmousedown="deleteReply(\''+rid+'\',\'reply_'+rid+'\');" title="DELETE THIS COMMENT">remove</a></span><br />'+data+'</div></div>';
				_("replyBtn_"+sid).disabled = false;
				_(ta).value = "";
			} else {
				alert(ajax.responseText);
			}
		}
	}
	ajax.send("action=status_reply&sid="+sid+"&user="+user+"&data="+data);
}
function deleteStatus(statusid,statusbox){
	var conf = confirm("Press OK to confirm deletion of this status and its replies");
	if(conf != true){
		return false;
	}
	var ajax = ajaxObj("POST", "php_parsers/status_system.php");
	ajax.onreadystatechange = function() {
		if(ajaxReturn(ajax) == true) {
			if(ajax.responseText == "delete_ok"){
				_(statusbox).style.display = 'none';
				_("replytext_"+statusid).style.display = 'none';
				_("replyBtn_"+statusid).style.display = 'none';
			} else {
				alert(ajax.responseText);
			}
		}
	}
	ajax.send("action=delete_status&statusid="+statusid);
}
function deleteReply(replyid,replybox){
	var conf = confirm("Press OK to confirm deletion of this reply");
	if(conf != true){
		return false;
	}
	var ajax = ajaxObj("POST", "php_parsers/status_system.php");
	ajax.onreadystatechange = function() {
		if(ajaxReturn(ajax) == true) {
			if(ajax.responseText == "delete_ok"){
				_(replybox).style.display = 'none';
			} else {
				alert(ajax.responseText);
			}
		}
	}
	ajax.send("action=delete_reply&replyid="+replyid);
}
function statusMax(field, maxlimit) {
	if (field.value.length > maxlimit){
		alert(maxlimit+" maximum character limit reached");
		field.value = field.value.substring(0, maxlimit);
	}
}
</script>
<div id="statusui">
  <?php echo $status_ui; ?>
</div>
<div id="statusarea">
  <?php echo $statuslist; ?>
</div>

<!-- ******** user.php ******** -->

<?php
include_once("php_includes/check_login_status.php");
// Initialize any variables that the page might echo
$u = "";
$sex = "Male";
$userlevel = "";
$profile_pic = "";
$profile_pic_btn = "";
$avatar_form = "";
$country = "";
$joindate = "";
$lastsession = "";
// Make sure the _GET username is set, and sanitize it
if(isset($_GET["u"])){
	$u = preg_replace('#[^a-z0-9]#i', '', $_GET['u']);
} else {
    header("location: http://www.webintersect.com");
    exit();	
}
// Select the member from the users table
$sql = "SELECT * FROM users WHERE username='$u' AND activated='1' LIMIT 1";
$user_query = mysqli_query($db_conx, $sql);
// Now make sure that user exists in the table
$numrows = mysqli_num_rows($user_query);
if($numrows < 1){
	echo "That user does not exist or is not yet activated, press back";
    exit();	
}
// Check to see if the viewer is the account owner
$isOwner = "no";
if($u == $log_username && $user_ok == true){
	$isOwner = "yes";
	$profile_pic_btn = '<a href="#" onclick="return false;" onmousedown="toggleElement(\'avatar_form\')">Toggle Avatar Form</a>';
	$avatar_form  = '<form id="avatar_form" enctype="multipart/form-data" method="post" action="php_parsers/photo_system.php">';
	$avatar_form .=   '<h4>Change your avatar</h4>';
	$avatar_form .=   '<input type="file" name="avatar" required>';
	$avatar_form .=   '<p><input type="submit" value="Upload"></p>';
	$avatar_form .= '</form>';
}
// Fetch the user row from the query above
while ($row = mysqli_fetch_array($user_query, MYSQLI_ASSOC)) {
	$profile_id = $row["id"];
	$gender = $row["gender"];
	$country = $row["country"];
	$userlevel = $row["userlevel"];
	$avatar = $row["avatar"];
	$signup = $row["signup"];
	$lastlogin = $row["lastlogin"];
	$joindate = strftime("%b %d, %Y", strtotime($signup));
	$lastsession = strftime("%b %d, %Y", strtotime($lastlogin));
}
if($gender == "f"){
	$sex = "Female";
}
$profile_pic = '<img src="user/'.$u.'/'.$avatar.'" alt="'.$u.'">';
if($avatar == NULL){
	$profile_pic = '<img src="images/avatardefault.jpg" alt="'.$user1.'">';
}
?><?php
$isFriend = false;
$ownerBlockViewer = false;
$viewerBlockOwner = false;
if($u != $log_username && $user_ok == true){
	$friend_check = "SELECT id FROM friends WHERE user1='$log_username' AND user2='$u' AND accepted='1' OR user1='$u' AND user2='$log_username' AND accepted='1' LIMIT 1";
	if(mysqli_num_rows(mysqli_query($db_conx, $friend_check)) > 0){
        $isFriend = true;
    }
	$block_check1 = "SELECT id FROM blockedusers WHERE blocker='$u' AND blockee='$log_username' LIMIT 1";
	if(mysqli_num_rows(mysqli_query($db_conx, $block_check1)) > 0){
        $ownerBlockViewer = true;
    }
	$block_check2 = "SELECT id FROM blockedusers WHERE blocker='$log_username' AND blockee='$u' LIMIT 1";
	if(mysqli_num_rows(mysqli_query($db_conx, $block_check2)) > 0){
        $viewerBlockOwner = true;
    }
}
?><?php 
$friend_button = '<button disabled>Request As Friend</button>';
$block_button = '<button disabled>Block User</button>';
// LOGIC FOR FRIEND BUTTON
if($isFriend == true){
	$friend_button = '<button onclick="friendToggle(\'unfriend\',\''.$u.'\',\'friendBtn\')">Unfriend</button>';
} else if($user_ok == true && $u != $log_username && $ownerBlockViewer == false){
	$friend_button = '<button onclick="friendToggle(\'friend\',\''.$u.'\',\'friendBtn\')">Request As Friend</button>';
}
// LOGIC FOR BLOCK BUTTON
if($viewerBlockOwner == true){
	$block_button = '<button onclick="blockToggle(\'unblock\',\''.$u.'\',\'blockBtn\')">Unblock User</button>';
} else if($user_ok == true && $u != $log_username){
	$block_button = '<button onclick="blockToggle(\'block\',\''.$u.'\',\'blockBtn\')">Block User</button>';
}
?><?php
$friendsHTML = '';
$friends_view_all_link = '';
$sql = "SELECT COUNT(id) FROM friends WHERE user1='$u' AND accepted='1' OR user2='$u' AND accepted='1'";
$query = mysqli_query($db_conx, $sql);
$query_count = mysqli_fetch_row($query);
$friend_count = $query_count[0];
if($friend_count < 1){
	$friendsHTML = $u." has no friends yet";
} else {
	$max = 18;
	$all_friends = array();
	$sql = "SELECT user1 FROM friends WHERE user2='$u' AND accepted='1' ORDER BY RAND() LIMIT $max";
	$query = mysqli_query($db_conx, $sql);
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		array_push($all_friends, $row["user1"]);
	}
	$sql = "SELECT user2 FROM friends WHERE user1='$u' AND accepted='1' ORDER BY RAND() LIMIT $max";
	$query = mysqli_query($db_conx, $sql);
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		array_push($all_friends, $row["user2"]);
	}
	$friendArrayCount = count($all_friends);
	if($friendArrayCount > $max){
		array_splice($all_friends, $max);
	}
	if($friend_count > $max){
		$friends_view_all_link = '<a href="view_friends.php?u='.$u.'">view all</a>';
	}
	$orLogic = '';
	foreach($all_friends as $key => $user){
			$orLogic .= "username='$user' OR ";
	}
	$orLogic = chop($orLogic, "OR ");
	$sql = "SELECT username, avatar FROM users WHERE $orLogic";
	$query = mysqli_query($db_conx, $sql);
	while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$friend_username = $row["username"];
		$friend_avatar = $row["avatar"];
		if($friend_avatar != ""){
			$friend_pic = 'user/'.$friend_username.'/'.$friend_avatar.'';
		} else {
			$friend_pic = 'images/avatardefault.jpg';
		}
		$friendsHTML .= '<a href="user.php?u='.$friend_username.'"><img class="friendpics" src="'.$friend_pic.'" alt="'.$friend_username.'" title="'.$friend_username.'"></a>';
	}
}
?><?php 
$coverpic = "";
$sql = "SELECT filename FROM photos WHERE user='$u' ORDER BY RAND() LIMIT 1";
$query = mysqli_query($db_conx, $sql);
if(mysqli_num_rows($query) > 0){
	$row = mysqli_fetch_row($query);
	$filename = $row[0];
	$coverpic = '<img src="user/'.$u.'/'.$filename.'" alt="pic">';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo $u; ?></title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="style/style.css">
<style type="text/css">
div#profile_pic_box{float:right; border:#999 2px solid; width:200px; height:200px; margin:20px 30px 0px 0px; overflow-y:hidden;}
div#profile_pic_box > img{z-index:2000; width:200px;}
div#profile_pic_box > a {
	display: none;
	position:absolute; 
	margin:140px 0px 0px 120px;
	z-index:4000;
	background:#D8F08E;
	border:#81A332 1px solid;
	border-radius:3px;
	padding:5px;
	font-size:12px;
	text-decoration:none;
	color:#60750B;
}
div#profile_pic_box > form{
	display:none;
	position:absolute; 
	z-index:3000;
	padding:10px;
	opacity:.8;
	background:#F0FEC2;
	width:180px;
	height:180px;
}
div#profile_pic_box:hover a {
    display: block;
}
div#photo_showcase{float:right; background:url(style/photo_showcase_bg.jpg) no-repeat; width:136px; height:127px; margin:20px 30px 0px 0px; cursor:pointer;}
div#photo_showcase > img{width:74px; height:74px; margin:37px 0px 0px 9px;}
img.friendpics{border:#000 1px solid; width:40px; height:40px; margin:2px;}
</style>
<style type="text/css">
textarea#statustext{width:982px; height:80px; padding:8px; border:#999 1px solid; font-size:16px;}
div.status_boxes{padding:12px; line-height:1.5em;}
div.status_boxes > div{padding:8px; border:#99C20C 1px solid; background: #F4FDDF;}
div.status_boxes > div > b{font-size:12px;}
div.status_boxes > button{padding:5px; font-size:12px;}
textarea.replytext{width:98%; height:40px; padding:1%; border:#999 1px solid;}
div.reply_boxes{padding:12px; border:#999 1px solid; background:#F5F5F5;}
div.reply_boxes > div > b{font-size:12px;}
</style>
<script src="js/main.js"></script>
<script src="js/ajax.js"></script>
<script type="text/javascript">
function friendToggle(type,user,elem){
	var conf = confirm("Press OK to confirm the '"+type+"' action for user <?php echo $u; ?>.");
	if(conf != true){
		return false;
	}
	_(elem).innerHTML = 'please wait ...';
	var ajax = ajaxObj("POST", "php_parsers/friend_system.php");
	ajax.onreadystatechange = function() {
		if(ajaxReturn(ajax) == true) {
			if(ajax.responseText == "friend_request_sent"){
				_(elem).innerHTML = 'OK Friend Request Sent';
			} else if(ajax.responseText == "unfriend_ok"){
				_(elem).innerHTML = '<button onclick="friendToggle(\'friend\',\'<?php echo $u; ?>\',\'friendBtn\')">Request As Friend</button>';
			} else {
				alert(ajax.responseText);
				_(elem).innerHTML = 'Try again later';
			}
		}
	}
	ajax.send("type="+type+"&user="+user);
}
function blockToggle(type,blockee,elem){
	var conf = confirm("Press OK to confirm the '"+type+"' action on user <?php echo $u; ?>.");
	if(conf != true){
		return false;
	}
	var elem = document.getElementById(elem);
	elem.innerHTML = 'please wait ...';
	var ajax = ajaxObj("POST", "php_parsers/block_system.php");
	ajax.onreadystatechange = function() {
		if(ajaxReturn(ajax) == true) {
			if(ajax.responseText == "blocked_ok"){
				elem.innerHTML = '<button onclick="blockToggle(\'unblock\',\'<?php echo $u; ?>\',\'blockBtn\')">Unblock User</button>';
			} else if(ajax.responseText == "unblocked_ok"){
				elem.innerHTML = '<button onclick="blockToggle(\'block\',\'<?php echo $u; ?>\',\'blockBtn\')">Block User</button>';
			} else {
				alert(ajax.responseText);
				elem.innerHTML = 'Try again later';
			}
		}
	}
	ajax.send("type="+type+"&blockee="+blockee);
}
</script>
</head>
<body>
<?php include_once("template_pageTop.php"); ?>
<div id="pageMiddle">
  <div id="profile_pic_box" ><?php echo $profile_pic_btn; ?><?php echo $avatar_form; ?><?php echo $profile_pic; ?></div>
  <div id="photo_showcase" onclick="window.location = 'photos.php?u=<?php echo $u; ?>';" title="view <?php echo $u; ?>&#39;s photo galleries">
    <?php echo $coverpic; ?>
  </div>
  <h2><?php echo $u; ?>&lt;script&gt;</h2>
  <p>Is the viewer the page owner, logged in and verified? <b><?php echo $isOwner; ?></b></p>
  <p>Gender: <?php echo $sex; ?></p>
  <p>Country: <?php echo $country; ?></p>
  <p>User Level: <?php echo $userlevel; ?></p>
  <p>Join Date: <?php echo $joindate; ?></p>
  <p>Last Session: <?php echo $lastsession; ?></p>
  <hr />
  <p>Friend Button: <span id="friendBtn"><?php echo $friend_button; ?></span> <?php echo $u." has ".$friend_count." friends"; ?> <?php echo $friends_view_all_link; ?></p>
  <p>Block Button: <span id="blockBtn"><?php echo $block_button; ?></span></p>
  <hr />
  <p><?php echo $friendsHTML; ?></p>
  <hr />
  <?php include_once("template_status.php"); ?>
</div>
<?php include_once("template_pageBottom.php"); ?>
</body>
</html>