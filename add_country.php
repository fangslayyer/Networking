<?php
require('config.php');

if(isset($_GET['edit_id'])) {
	$EditMode = true;
} else {
	$EditMode = false;
}

if($EditMode) {
	$sql = "SELECT * FROM country WHERE ID = '$_GET[edit_id]'";
	$result = mysqli_query($link, $sql);
	$EditRow = mysqli_fetch_array($result);
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Add Country</title>
</head>
<body>
<form action="process.php?field=country" method="post">
<table>
	<tr><td>Iso2 Code</td><td><input type="text" name="ID" maxlength="2"></td></tr>
	<tr><td>Name</td><td><input type="text" name="name" maxlength="30" value="<?php if($EditMode && $EditRow['active']==1) {echo $EditRow['name']; } ?>"><td></tr>
	<tr><td>Mode</td><td><input type="radio" name="active" value="1" <?php if($EditMode && $EditRow['active']==1) {echo "checked"; } ?>>Active
						<input type="radio" name="active" value="0" <?php if($EditMode && $EditRow['active']==0) {echo "checked"; } ?>>Inactive</td></tr>
</table>
<br>
<input type="hidden" name="EditMode" value="<?php echo $EditMode; ?>">
<input type="hidden" name="edit_id" value="<?php echo $_GET['edit_id']; ?>">
<input type="submit">
</form>
<br>
<a href="control.php">Cancel</a>

</body>
</html>