<?php
require('config.php');
?>
<!DOCTYPE html>
<html>
<head>
	<title>Delete Picture</title>
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="widgets/chosen/chosen.css">
</head>
<body>
	Are you sure you would like to delete the following picture?
	<table>
		<tr><td>Person</td>
			<td>
			<?php
				$sql = "SELECT p.ID, p.name AS pic_name, p.note, person.name AS person_name FROM picture AS p LEFT JOIN person ON p.person = person.ID WHERE p.ID = $_GET[proc_id]";
				$result = mysqli_query($link, $sql) or die(mysqli_error());
				$row = mysqli_fetch_array($result);
				echo $row['person_name'];
			?></td></tr>
	<?php 
		echo "<tr><td colspan='2'><img src='pictures/$row[pic_name]' height='150px'></td></tr>"; ?>
	</table>
	<br><?php {echo $row['note'];} ?>
	<br>
	<a href="process_picture.php?process=delete&DelSure=1&proc_id=<?php echo $row['ID']; ?>">Yes</a>
	<a href="view_picture.php">Cancel</a>
</body>
</html>