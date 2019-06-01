<?php
require('config.php');

if(isset($_GET['edit_id'])) {
	$EditMode = true;
} else {
	$EditMode = false;
}

if($EditMode) {
	$sql = "SELECT * FROM city WHERE ID = $_GET[edit_id]";
	$result = mysqli_query($link, $sql);
	$EditRow = mysqli_fetch_array($result);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Add City</title>
</head>
<body>
<form action="process.php?field=city" method="post">
<table>
	<tr><td>Name</td> 	<td><input type="text" name="name" maxlength="30" value="<?php if($EditMode) {echo $EditRow['name'];} ?>"><td></tr>
	<tr><td>Country</td>
		<td><select name ="country">
		<?php
			$sql = "SELECT * FROM country";
			$result = mysqli_query($link, $sql) or die(mysqli_error());
			while ($row = mysqli_fetch_array($result))	{
				echo "<option value='$row[id]'";
				if ($EditMode && $EditRow['country'] == $row['ID']) {echo "selected"; }
				echo ">$row[name]</option>";
			}
		?></select></td></tr>
	<tr><td>Latitude</td> 	<td><input type="text" name="lat" maxlength="30" value="<?php if($EditMode) {echo $EditRow['lat'];} ?>"><td></tr>
	<tr><td>Longitude</td> 	<td><input type="text" name="lng" maxlength="30" value="<?php if($EditMode) {echo $EditRow['lng'];} ?>"><td></tr>
</table>
<br>
<input type="hidden" name="edit_id" value="<?php if($EditMode) {echo $_GET['edit_id'];} ?>">
<input type="submit"><br>
<a href="control.php">Cancel</a>
</form>


</body>
</html>