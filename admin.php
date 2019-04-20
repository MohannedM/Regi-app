<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>
	

<?php 
if(!logged_in()){
	redirect("index.php");
}
?>
	<div class="jumbotron">
		<h1 class="text-center">Welcome <?php
			if(isset($_SESSION['username'])){
				echo $_SESSION['username'];
			} else if(isset($_COOKIE['username'])) {
				echo $_COOKIE['username'];
			}
		?></h1>
	</div>



<?php include "includes/footer.php"; ?>