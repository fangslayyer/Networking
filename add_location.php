<?php
require 'config.php';
require 'functions.php';

if(isset($_GET['edit_id'])) {
	$EditMode = true;
} else {
	$EditMode = false;
}

if($EditMode) {
	$sql = "SELECT * FROM location WHERE ID = $_GET[edit_id]";
	$result = mysqli_query($link, $sql);
	$EditRow = mysqli_fetch_array($result);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Add Location</title>
	<link rel="stylesheet" href="style.css">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<!-- <tinymce rich text editor> -->
	<script src="/widgets/tinymce/tinymce.min.js"></script><!-- tinymce text editor -->
	<script>
		tinymce.init({
		  selector: 'textarea',
		  height: 400,
		  menubar: false,
		  plugins: [
		    'advlist autolink lists link image charmap print preview anchor',
		    'searchreplace visualblocks code fullscreen',
		    'insertdatetime media table contextmenu paste code'
		  ],
		  toolbar: 'undo redo | insert | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
		  content_css: [
		    '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
		    '//www.tinymce.com/css/codepen.min.css']
		});
	</script>
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

<?php if(isset($_GET['message'])) {echo $_GET['message']; } ?>
<div class="column form">
<form action="process.php?field=location" method="post">
<table width="200px">
	<tr><td>Name</td> 	<td><input type="text" name="name" maxlength="100" id="place_name" value="<?php if($EditMode) {echo $EditRow['name']; } ?>"><td></tr>
	<tr><td>City*</td>
		<td><select name="city" class="msdropdown" id="city">
		<?php
			$sql = "SELECT city.ID AS city_ID, city.name AS city_name, lat, lng, city.country FROM city
					LEFT JOIN country on city.country = country.ID
				    WHERE country.active = 1
				    ORDER BY ". RecentOrderBy('city', 'city_ID') . ", country.name, city.name";
			$result = mysqli_query($link, $sql) or die(mysqli_error($link));
			while ($row = mysqli_fetch_array($result))	{
				echo "<option value='$row[city_ID]' data-lat='$row[lat]' data-lng='$row[lng]' data-image='icon/country/24/$row[country].png' ";
				if ($EditMode && $EditRow['city'] == $row['city_ID']) {echo "selected"; }
				echo">$row[city_name]</option>";
			}
		?></select></td></tr>
	<tr><td>Type*</td><td nowrap><select name="location_type" class="msdropdown">
			<?php
				$result = mysqli_query($link, "SELECT ID, name FROM venue ORDER BY name");
				while ($row = mysqli_fetch_array($result)) {
				echo "<option value='$row[ID]'";
				if ($EditMode && $EditRow['type'] == $row['ID']) {echo "selected"; }
				echo ">$row[name]</option>"; } ?>
			</select> <a href="add_venue.php" target="_blank">Add Type</a></td></tr>
	<tr><td>Latitude</td> 	<td><input type="text" name="lat" maxlength="30" id="lat" value="<?php if($EditMode) {echo $EditRow['lat']; } ?>"><td></tr>
	<tr><td>Longitude</td> 	<td><input type="text" name="lng" maxlength="30" id="lng" value="<?php if($EditMode) {echo $EditRow['lng']; } ?>"><td></tr>
	<tr><td>GoogleID</td> 	<td><input type="text" name="googleID" maxlength="200" id="googleID" value="<?php if($EditMode) {echo $EditRow['googleID']; } ?>"><td></tr>
</table>
Notes:<br>
<textarea name="note" maxlength="5000"><?php if($EditMode) {echo $EditRow['note'];} ?></textarea><br>
<input type="hidden" name="edit_id" value="<?php if($EditMode) {echo $_GET['edit_id'];} ?>">
<input type="submit"><br>
<a href="control.php">Cancel</a>
</form>
</div>

<!-- GOOGLE MAPS STUFF -->
<input id="pac-input" class="controls" type="text" placeholder="Enter a location">
<div class="column full_map"> 
	<div id="map"></div>
	<div id="infowindow-content"></div>
</div>
<script>
	var EditMode = <?php if($EditMode) {echo "true";} else {echo "false";} ?>;
	var EditId = <?php if($EditMode) {echo "'".$EditRow['googleID']."'";} else {echo "null";} ?>;
</script>
<script src="map.js" type="text/javascript"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDZUqVqQHRq9h2x9BMka6Mk-4Us84EAtLA&libraries=places&callback=initMap" async defer></script>

</body>
</html>