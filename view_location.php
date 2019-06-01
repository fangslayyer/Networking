<?php
require 'config.php';
require 'functions.php';


$sortfields = array('name', 'type', 'city','country');
$order = new sorter('result', 'location', $sortfields, true);
?>
<!DOCTYPE html>
<html>
<head>
	<title>View Locations</title>
	<link rel="stylesheet" href="style.css">

	<!-- <jQuery requirements> -->
	<script type="text/javascript" src="//code.jquery.com/jquery-1.8.3.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<!-- </jQuery requirements> -->

	<script>
		$( function() {
			<?php 
			$order->js(false); ?>
		} );
	</script>
</head>
<body>
<?php if(isset($_GET['message'])) {echo $_GET['message']."<br>";} ?>
<div class="baseline">
	<?php
	$order->button_sort(); ?>
	<a href="control.php">Back</a>
</div>
<?php
	$order->html_sort(); 
?>
<div class="baseline">
	<a href="add_location.php">[Add Location]</a> <a href="view_map.php?category=location">[View Map]</a>
	<table border="1">
		<tr>
			<td></td>
			<td>Name</td>
			<td>Type</td>
			<td>City</td>
			<td>Latitude</td>
			<td>Longitude</td>
		</tr>
	<?php
		$sql = "SELECT l.ID AS ID, l.googleID, l.name, v.name AS type, c.name AS city, l.lat, l.lng, c.country FROM `location` AS l 
				LEFT JOIN city AS c ON l.city = c.ID
				LEFT JOIN venue AS v ON l.type = v.ID
				ORDER BY $order->orderby";
		$result = mysqli_query($link, $sql) or die(mysqli_error($link));
		while ($row = mysqli_fetch_array($result))	{
			echo "<tr><td><a href='process_delete.php?field=location&id=$row[ID]&sure=0'><img src='icon/basic/delete/Delete_24x24.png' height='24'></a>";
			echo "<a href='add_location.php?edit_id=$row[ID]'><img src='icon/basic/edit/Edit_24x24.png' height='24'></a>";
				if (!empty($row['googleID'])) {
					echo "<a href='https://www.google.com/maps/place/?q=place_id:$row[googleID]' target='_blank'><img src='icon/googleMaps-24.png' height='24'></a>";
				}
				echo "</td>";
			echo "<td>$row[name]</td>";
			echo "<td>$row[type]</td>";
			echo "<td><img src='icon/country/24/$row[country].png' height='24'>$row[city]</td>";
			echo "<td>$row[lat]</td>";
			echo "<td>$row[lng]</td></tr>";
		}
	?>
	</table>
	<br>
	<a href="control.php">Back</a>
</div>
</body>
</html>