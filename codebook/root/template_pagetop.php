<?php
// It is important for any file that includes this file, to have
// check_login_status.php included at its very top.
$note = '<img src="images/note_grey1.png" width="22" height="22" alt="Notes" title="This notification is for logged in members">';
$loginLink = '<a href="login.php">Log In</a> &nbsp; | &nbsp; <a href="signup.php">Sign Up</a>';
if($user_ok == true) {
	$sql = "SELECT notescheck FROM users WHERE username='$log_username' LIMIT 1";
	$query = mysqli_query($db_con, $sql);
	$row = mysqli_fetch_row($query);
	$notescheck = $row[0];
	$sql = "SELECT id FROM notifications WHERE username='$log_username' AND date_time > '$notescheck' LIMIT 1";
	$query = mysqli_query($db_con, $sql);
	$numrows = mysqli_num_rows($query);
    if ($numrows == 0) {
		$note = '<a href="notifications.php" title="Your notifications and friend requests"><img src="images/note_brown.png" width="22" height="22" alt="Notes"></a>';
    } else {
		$note = '<a href="notifications.php" title="You have new notifications"><img src="images/note_gif.gif" width="22" height="22" alt="Notes"></a>';
	}
    $loginLink = '<a href="user.php?u='.$log_username.'">'.$log_username.'</a> &nbsp; | &nbsp; <a href="logout.php">Log Out</a>';
} 
?>
<div id="pagetop">
	<div id="pagetopwrap">
		<div id="pagetoplogo">
			<a href="http://www.codersbook.com"><img src="images/logo3.png" alt="logo" title="Codebook"></a>
		</div>
		<div id="pagetoprest">
		`	<div id="menu1">
				<div>
					 <?php echo $note; ?> &nbsp; &nbsp; <?php echo $loginLink; ?>
				</div>
			</div>
			<div id="menu2">
				<div>
					<a href="http://www.codersbook.com"><img src="images/home4.png" alt="home" title="Home"></a>
					<a href="http://localhost:8081/JobForMigrants/">Requests</a>
					<a href="http://www.codersbook.com">Profile</a>
					<a href="http://www.codersbook.com">Logout</a>
				</div>
			</div>
		</div>
	</div>
</div>