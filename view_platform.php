<?php
require('config.php');

?>
<!DOCTYPE html>
<html>
<head>
	<title>View Platform</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<?php if(isset($_GET['message'])) {echo $_GET['message'];} ?>
<br><a href="add_platform.php">[Add Platform]</a>
<table border="1">
	<tr><td></td><td>Name</td></tr>
<?php
	$sql = "SELECT * FROM Platform ORDER BY name";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));
	while ($row = mysqli_fetch_array($result))	{
		echo "<tr><td><a href='process_delete.php?field=platform&id=$row[ID]&sure=0'><img src='icon/basic/delete/Delete_24x24.png' height='24'></a>";
		echo "<a href='add_platform.php?edit_id=$row[ID]'><img src='icon/basic/edit/Edit_24x24.png' height='24'></a></td>";
		echo "<td>$row[name]</td></tr>";
	}
?>
</table>
<br>
<a href="control.php">Back</a>
</body>
</html>