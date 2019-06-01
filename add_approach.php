<?php
require('config.php');

if(isset($_GET['edit_id'])) {
	$EditMode = true;
} else {
	$EditMode = false;
}

if($EditMode) {
	$sql = "SELECT * FROM approach WHERE ID = $_GET[edit_id]";
	$result = mysqli_query($link, $sql);
	$EditRow = mysqli_fetch_array($result);
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Add Approach</title>
</head>
<body>
<form action="process.php?field=approach" method="post">
<table>
	<tr><td>Name</td> 	
		<td><input type="text" name="name" maxlength="30" value="<?php if($EditMode) {echo $EditRow['name']; } ?>"><td></tr>
</table>
<br>
<input type="hidden" name="edit_id" value="<?php if ($EditMode) { echo $_GET['edit_id']; } ?>">
<input type="submit">
</form>
<br>
<a href="control.php">Cancel</a>

</body>
</html>