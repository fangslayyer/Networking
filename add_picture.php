<?php
require 'config.php';
require 'functions.php';

if(isset($_GET['proc_id'])) {
	$EditMode = true;
} else {
	$EditMode = false;
}

if($EditMode) {
	$sql = "SELECT * FROM picture WHERE ID = $_GET[proc_id]";
	$result = mysqli_query($link, $sql);
	$EditRow = mysqli_fetch_array($result);
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Upload Photo</title>
	<link rel="stylesheet" href="style.css">
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.js" type="text/javascript"></script>
	<!-- <tinymce rich text editor> -->
	<script src="/widgets/tinymce/tinymce.min.js"></script><!-- tinymce text editor -->
	<script src="/js/tinymce.js"></script>
	<!-- </tinymce rich text editor> -->
	<!-- <msdropdown> -->
	<link rel="stylesheet" type="text/css" href="widgets/msdropdown/dd.css" />
	<script src="widgets/msdropdown/jquery.dd.js"></script>
	<!-- </msdropdown> -->
</head>
<body>
<!-- <msdropdown> -->
<script>
$(document).ready(function(e) {		
	$("select.msdropdown").msDropdown({roundedBorder:false});
});
</script>
<!-- </msdropdown> -->

	<form action="process_picture.php" method="post" enctype="multipart/form-data">
		<table>
			<tr><td>person</td>
				<td><select name ="person" class="msdropdown">
				<?php
					$sql = "SELECT g.ID, g.name, city.country FROM person AS g
							LEFT JOIN city ON g.city = city.ID ORDER BY " . RecentOrderBy('person', 'g.ID');
					$result = mysqli_query($link, $sql) or die(mysqli_error($link));
					while ($row = mysqli_fetch_array($result))	{
						echo "<option value='$row[ID]' data-image='icon/country/24/$row[country].png' ";
						if ($EditMode && $EditRow['person'] == $row['ID']) { echo " selected"; }
						echo ">$row[name]</option>";
					}
				?></select></td></tr>
<?php if($EditMode) {
			echo "<tr><td colspan='2'><img src='pictures/$EditRow[name]' height='150px'></td></tr>";
} ?>
			<tr><td>File</td>
				<td><input type="file" name="upload"></td></tr>

		</table>
		<br>Notes:
		<br><textarea name="note" rows="15" cols="100" maxlength="2500"><?php if($EditMode) {echo $EditRow['note'];}?></textarea>
		<input type="hidden" name="process" value="<?php if($EditMode) {echo "edit";} else {echo "add";} ?>">
		<input type="hidden" name="proc_id" value="<?php if($EditMode) {echo $_GET['proc_id'];} ?>">
		<br><input type="submit" name="submit">

		
	</form>
	<br>
	<a href="control.php">Cancel</a>
</body>
</html>