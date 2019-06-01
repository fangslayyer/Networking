<?php
require('config.php');

?>
<!DOCTYPE html>
<html>
<head>
	<title>View Cities</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<a href="control.php">Back</a>
<table border="1">
	<tr><th></th><th>Name</th><th>Province</th><th>Country</th><th>Latitude</th><th>Longitude</th><th>Population</th></tr>
<?php
	$sql = "SELECT city.*, country.name AS country_name, country.ID AS country_ID FROM CITY 
		LEFT JOIN country ON city.country = country.ID
	    WHERE country.active = 1
	    ORDER BY country.name, city.province, city.name";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));
	while ($row = mysqli_fetch_array($result))	{
		echo "<tr><td><a href='add_city.php?edit_id=$row[ID]'>[EDIT]</a></td>";
		echo "<td>$row[name]</td>";
		echo "<td>$row[province]</td>";
		echo "<td><img src='icon/country/16/$row[country_ID].png' height='16px'> $row[country_name]</td>";
		echo "<td>$row[lat]</td>";
		echo "<td>$row[lng]</td>";
		echo "<td align='right'>" . number_format($row['population']) . "</td></tr>";
	}
?>
</table>
<br>
<a href="control.php">Back</a>
</body>
</html>