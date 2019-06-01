<?php
require('config.php');

?>
<!DOCTYPE html>
<html>
<head>
	<title>View Countries</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<a href="control.php">Back</a>
<table border='1'>
	<tr><th></th><th>Name</th></tr>
<?php
	$sql = "SELECT * FROM country";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));
	while ($row = mysqli_fetch_array($result))	{
		if ($row['active']) {
			$class = "green";
			$ChangeLink = "<a href='process.php?field=country&edit_id=$row[ID]&name=$row[name]&active=0'>"; 
			$ChangeLink.= "<img src='icon/basic/cancel/Cancel_24x24.png' height='24'></a>";
		} else {
			$class = "red";
			$ChangeLink = "<a href='process.php?field=country&edit_id=$row[ID]&name=$row[name]&active=1'>";
			$ChangeLink.= "<img src='icon/basic/check/Check_24x24.png' height='24'></a>";
		}
		echo "<tr class=$class><td><a href='add_country.php?edit_id=$row[ID]'>";
			echo "<img src='icon/basic/edit/Edit_24x24.png' height='24'></a>$ChangeLink</td>";
		echo "<td><img src='icon/country/24/$row[ID].png' height='24' class='flag24'> $row[name]</td></tr>";
	}
?>
</table>
<br>
<a href="control.php">Back</a>
</body>
</html>