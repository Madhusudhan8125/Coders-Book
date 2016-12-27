<?php
include_once("../php_include/check_login_status.php");
if($user_ok != true || $log_username == "") {
	exit();
}
?>
<?php 
if (isset($_POST["show"]) && $_POST["show"] == "galpics"){
	$picstring = "";
	$gallery = preg_replace('#[^a-z 0-9,]#i', '', $_POST["gallery"]);
	$user = preg_replace('#[^a-z0-9]#i', '', $_POST["user"]);
	$sql = "SELECT * FROM photos WHERE user='$user' AND gallery='$gallery' ORDER BY uploaddate ASC";
	$query = mysqli_query($db_con, $sql);
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$id = $row["id"];
		$filename = $row["filename"];
		$description = $row["description"];
		$uploaddate = $row["uploaddate"];
		$picstring .= "$id|$filename|$description|$uploaddate|||";
    }
	mysqli_close($db_con);
	$picstring = trim($picstring, "|||");
	echo $picstring;
	exit();
}
?><?php
if($user_ok != true || $log_username == "") {
	exit();
}
?>
<?php 
if (isset($_FILES["avatar"]["name"]) && $_FILES["avatar"]["tmp_name"] != ""){
	$fileName = $_FILES["avatar"]["name"];
    $fileTmpLoc = $_FILES["avatar"]["tmp_name"];
	$fileType = $_FILES["avatar"]["type"];
	$fileSize = $_FILES["avatar"]["size"];
	$fileErrorMsg = $_FILES["avatar"]["error"];
	$kaboom = explode(".", $fileName);
	$fileExt = end($kaboom);
	list($width, $height) = getimagesize($fileTmpLoc);
	if($width < 10 || $height < 10){
		header("location: ../message.php?msg=ERROR: That image has no dimensions");
        exit();	
	}
	$db_file_name = rand(100000000000,999999999999).".".$fileExt;
	if($fileSize > 1048576) {
		header("location: ../message.php?msg=ERROR: Your image file was larger than 1mb");
		exit();	
	} else if (!preg_match("/\.(gif|jpg|png)$/i", $fileName) ) {
		header("location: ../message.php?msg=ERROR: Your image file was not jpg, gif or png type");
		exit();
	} else if ($fileErrorMsg == 1) {
		header("location: ../message.php?msg=ERROR: An unknown error occurred");
		exit();
	}
	$sql = "SELECT avatar FROM users WHERE username='$log_username' LIMIT 1";
	$query = mysqli_query($db_con, $sql);
	$row = mysqli_fetch_row($query);
	$avatar = $row[0];
	if($avatar != ""){
		$picurl = "../user/$log_username/$avatar"; 
	    if (file_exists($picurl)) { unlink($picurl); }
	}
	$moveResult = move_uploaded_file($fileTmpLoc, "../user/$log_username/$db_file_name");
	if ($moveResult != true) {
		header("location: ../message.php?msg=ERROR: File upload failed");
		exit();
	}
	include_once("../php_include/image_resize.php");
	$target_file = "../user/$log_username/$db_file_name";
	$resized_file = "../user/$log_username/$db_file_name";
	$wmax = 200;
	$hmax = 300;
	img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	$sql = "UPDATE users SET avatar='$db_file_name' WHERE username='$log_username' LIMIT 1";
	$query = mysqli_query($db_con, $sql);
	mysqli_close($db_con);
	header("location: ../user.php?u=$log_username");
	exit();
}
?>
<?php 
if (isset($_FILES["photo"]["name"]) && isset($_POST["gallery"])){
	$sql = "SELECT COUNT(id) FROM photos WHERE user='$log_username'";
	$query = mysqli_query($db_con, $sql);
	$row = mysqli_fetch_row($query);
	if($row[0] > 14){
		header("location: ../message.php?msg=The demo system allows only 15 pictures total");
        exit();	
	}
	$gallery = preg_replace('#[^a-z 0-9,]#i', '', $_POST["gallery"]);
	$fileName = $_FILES["photo"]["name"];
    $fileTmpLoc = $_FILES["photo"]["tmp_name"];
	$fileType = $_FILES["photo"]["type"];
	$fileSize = $_FILES["photo"]["size"];
	$fileErrorMsg = $_FILES["photo"]["error"];
	$kaboom = explode(".", $fileName);
	$fileExt = end($kaboom);
	$db_file_name = date("DMjGisY")."".rand(1000,9999).".".$fileExt;
	// WedFeb272120452014RAND.jpg
/* DAYS 
d - day of the month 2 digits (01-31) 
j - day of the month (1-31) 
D - 3 letter day (Mon - Sun) 
l - full name of day (Monday - Sunday) 
N - 1=Monday, 2=Tuesday, etc (1-7) 
S - suffix for date (st, nd, rd) 
w - 0=Sunday, 1=Monday (0-6) 
z - day of the year (1=365)

WEEK 
W - week of the year (1-52)

MONTH 
F - Full name of month (January - December)
m - 2 digit month number (01-12) 
n - month number (1-12) 
M - 3 letter month (Jan - Dec) 
t - Days in the month (28-31)

YEAR
L - leap year (0 no, 1 yes)
o - ISO-8601 year number (Ex. 1979, 2006)
Y - four digit year (Ex. 1979, 2006)
y - two digit year (Ex. 79, 06)

TIME
a - am or pm
A - AM or PM
B - Swatch Internet time (000 - 999)
g - 12 hour (1-12)
G - 24 hour c (0-23)
h - 2 digit 12 hour (01-12)
H - 2 digit 24 hour (00-23)
i - 2 digit minutes (00-59)
s 0 2 digit seconds (00-59)

OTHER e - timezone (Ex: GMT, CST)
I - daylight savings (1=yes, 0=no)
O - offset GMT (Ex: 0200)
Z - offset in seconds (-43200 - 43200)
r - full RFC 2822 formatted date*/
	list($width, $height) = getimagesize($fileTmpLoc);
	if($width < 10 || $height < 10){
		header("location: ../message.php?msg=ERROR: That image has no dimensions");
        exit();	
	}
	if($fileSize > 1048576) {
		header("location: ../message.php?msg=ERROR: Your image file was larger than 1mb");
		exit();	
	} else if (!preg_match("/\.(gif|jpg|png)$/i", $fileName) ) {
		header("location: ../message.php?msg=ERROR: Your image file was not jpg, gif or png type");
		exit();
	} else if ($fileErrorMsg == 1) {
		header("location: ../message.php?msg=ERROR: An unknown error occurred");
		exit();
	}
	$moveResult = move_uploaded_file($fileTmpLoc, "../user/$log_username/$db_file_name");
	if ($moveResult != true) {
		header("location: ../message.php?msg=ERROR: File upload failed");
		exit();
	}
	include_once("../php_include/image_resize.php");
	$wmax = 800;
	$hmax = 600;
	if($width > $wmax || $height > $hmax){
		$target_file = "../user/$log_username/$db_file_name";
	    $resized_file = "../user/$log_username/$db_file_name";
		img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	}
	$sql = "INSERT INTO photos(user, gallery, filename, uploaddate) VALUES ('$log_username','$gallery','$db_file_name',now())";
	$query = mysqli_query($db_con, $sql);
	mysqli_close($db_con);
	header("location: ../photos.php?u=$log_username");
	exit();
}
?><?php 
if (isset($_POST["delete"]) && $_POST["id"] != ""){
	$id = preg_replace('#[^0-9]#', '', $_POST["id"]);
	$query = mysqli_query($db_con, "SELECT user, filename FROM photos WHERE id='$id' LIMIT 1");
	$row = mysqli_fetch_row($query);
    $user = $row[0];
	$filename = $row[1];
	if($user == $log_username){
		$picurl = "../user/$log_username/$filename"; 
	    if (file_exists($picurl)) {
			unlink($picurl);
			$sql = "DELETE FROM photos WHERE id='$id' LIMIT 1";
	        $query = mysqli_query($db_con, $sql);
		}
	}
	mysqli_close($db_con);
	echo "deleted_ok";
	exit();
}
?>
