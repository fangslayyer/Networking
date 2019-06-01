<?php
require 'config.php';
require 'functions.php';

if(isset($_COOKIE['current_location'])) {
	$EditMode = true;
	$current_location = json_decode($_COOKIE['current_location'], true);
} else {
	$EditMode = false;
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Change Current Location</title>
	<link rel="stylesheet" href="style.css">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

	<!-- <msdropdown> -->
	<link rel="stylesheet" type="text/css" href="widgets/msdropdown/dd.css" />
	<script src="widgets/msdropdown/jquery.dd.js"></script>
	<!-- </msdropdown> -->
</head>
<body>
<!-- <msdropdown> -->
<script>
$(document).ready(function(e) {		
	$("select.msDropdown").msDropdown({roundedBorder:false});
});
</script>
<!-- </msdropdown> -->

<div class="column" style="width: 30%">
	<h2>Change Current Location</h1><br>
	<?php if(isset($_GET['message'])) {echo $_GET['message']; } ?>
	<form action="process.php?field=current_location&edit_id=1" method="post">
		<div class="form_input">
			<div class="control-group">
				<label>Name</label> 	<input type="text" name="name" maxlength="30" id="place_name" value="<?php if($EditMode) {echo $current_location['name']; } ?>">
			</div>
			<div class="control-group">	
				<label>City*</label>
				<select name="city" class="msdropdown" id="city">
				<?php
					$sql = "SELECT city.ID AS city_ID, city.name AS city_name, lat, lng, city.country FROM city
							LEFT JOIN country on city.country = country.ID
						    WHERE country.active = 1
						    ORDER BY ". RecentOrderBy('city', 'city_ID') . ", country.name, city.name";
					$result = mysqli_query($link, $sql) or die(mysqli_error($link));
					while ($row = mysqli_fetch_array($result))	{
						echo "<option value='$row[city_ID]' data-lat='$row[lat]' data-lng='$row[lng]' data-image='icon/country/24/$row[country].png' ";
						if ($EditMode && $current_location['city'] == $row['city_ID']) {echo " selected"; }
						echo">$row[city_name]</option>";
					}
				?></select>
			</div>
			<div class="control-group">
				<label>Latitude</label> 	<input type="text" name="lat" maxlength="30" id="lat" value="<?php if($EditMode) {echo $current_location['lat']; } ?>" >
			</div>
			<div class="control-group">
				<label>Longitude</label> 	<input type="text" name="lng" maxlength="30" id="lng" value="<?php if($EditMode) {echo $current_location['lng']; } ?>" >
			</div>
			<div class="control-group">
				<label>GoogleID</label> 	<input type="text" name="googleID" maxlength="200" id="googleID" value="<?php if($EditMode) {echo $current_location['googleID']; } ?>" >
			</div>
		</div>
		<div class="form_text">
			<input type="submit"><br><br>
			<a href="control.php">Cancel</a>
		</div>
	</form>
</div>

<!-- GOOGLE MAPS STUFF -->
<div class="column full_map" style="width: 70%">
	<input id="pac-input" class="controls" type="text" placeholder="Enter a location">
	<div id="map"></div>
	<div id="infowindow-content"></div>
</div>
<script>
	var EditMode = <?php if($EditMode) {echo "true";} else {echo "false";} ?>;
	var EditId = <?php if($EditMode) {echo "'".$current_location['googleID']."'";} else {echo "null";} ?>;
</script>
<script src="map.js" type="text/javascript"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDZUqVqQHRq9h2x9BMka6Mk-4Us84EAtLA&libraries=places&callback=initMap"async defer></script>
	
</body>
</html>