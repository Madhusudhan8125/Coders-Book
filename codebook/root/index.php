<?php
include_once("php_include/check_login_status.php");
$sql="SELECT username, avatar FROM users WHERE avatar IS NOT NULL AND activated='1' ORDER BY RAND() LIMIT 32";
$query=mysqli_query($db_con, $sql);
$usernumrows=mysqli_num_rows($query);
$userlist="";
while ($row=mysqli_fetch_array($query, MYSQLI_ASSOC))
{
$u=$row["username"];
$avatar=$row["avatar"];
$profile_pic='user/'.$u.'/'.$avatar;
if($avatar == NULL){
		$profile_pic = 'images/avatardefault.jpg';
	}
$userlist.='<a href="user.php?u='.$u.'" title="'.$u.'"><img src="'.$profile_pic.'" alt="'.$u.'" style="width:100px; height:100px; margin:5px;"></a>';
}
$sql = "SELECT COUNT(id) FROM users WHERE activated='1'";
$query = mysqli_query($db_con, $sql);
$row=mysqli_fetch_array($query);
$usercount = $row[0];


?>
<html>
<head>
<meta charset="UTF-8">
<title>  Codebook </title>
<link rel="stylesheet" href="style/style.css">

</head>
<body>
<!--  -->
<center><h1> Welcome to Codebook </h1> </center>
<?php include_once("template_pagetop.php"); ?>
<div id="pagemiddle">
<h4> Total Users = </h4> <?php echo $usercount; ?>

<br>
<hr>
<h4>Random Users</h4> <?php echo $userlist; ?>
<hr>&nbsp;
</div>
<?php include_once("template_pagebottom.php"); ?>
</body>
</html>
