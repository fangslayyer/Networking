<?php
require('config.php');

foreach ($_GET as $key => $value)	{
	$$key = $value;
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Network Homepage</title>
</head>
<body>
<?php if (!empty($message)) {echo $message . "<br>";} ?>
<!-- <a href="index.php">[Home]</a> -->
<p>
	<table border="0">
		<tr><td>Person</td>		<td><a href="add_person.php">[Add]</a></td>			<td><a href="view_person.php">[View]</a></td></tr>
		<tr><td>Date</td>		<td><a href="add_dayte.php">[Add]</a></td>			<td><a href="view_dayte.php">[View]</a></td></tr>
		<tr><td>Location</td>	<td><a href="add_location.php">[Add]</a></td>		<td><a href="view_location.php">[View]</a></td></tr>
		<tr><td>Tag</td>		<td><a href="add_tag.php">[Add]</a></td>			<td><a href="view_tag.php">[View]</a></td></tr>
		<tr><td>Picture</td>	<td><a href="add_picture.php">[Add]</a></td>		<td><a href="view_picture.php">[View]</a></td></tr>
		<tr><td>Platform</td>	<td><a href="add_platform.php">[Add]</a></td>		<td><a href="view_platform.php">[View]</a></td></tr>
		<tr><td>Venue</td>		<td><a href="add_venue.php">[Add]</a></td>			<td><a href="view_venue.php">[View]</a></td></tr>
		<tr><td>Approach</td>	<td><a href="add_approach.php">[Add]</a></td>		<td><a href="view_approach.php">[View]</a></td></tr>
		<tr><td>Status</td>		<td><a href="add_status.php">[Add]</a></td>			<td><a href="view_status.php">[View]</a></td></tr>
		<tr><td>City</td>		<td><a href="add_city.php">[Add]</a></td>			<td><a href="view_city.php">[View]</a></td></tr>
		<tr><td>Country</td>	<td><a href="add_country.php">[Add]</a></td>		<td><a href="view_country.php">[View]</a></td></tr>
		<tr><td colspan="3"><a href="current_location.php">Set Current Location</a></td></tr>
	</table>
	<?php 
	echo "<pre>";
	print_r(get_defined_vars());
	echo "</pre>"
	?>
</body>
</html>