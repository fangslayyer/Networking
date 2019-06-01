<?php
require 'config.php';
require 'functions.php';

$sortfields = array('person','start','duration','location','cost','share');
$order = new sorter('result', 'date', $sortfields);
$filter = new dayte_filter();

// Query sql for table to display dates
$sql = "SELECT d.ID AS ID, 
			person.name AS person,
            person.nationality,
			d.start, 
		    d.duration,
		    GROUP_CONCAT(DISTINCT location.name ORDER BY dl.sequence ASC SEPARATOR '~~~') AS location, 
            GROUP_CONCAT(DISTINCT city.country ORDER BY dl.sequence) AS locationISO,
		    SUM(dl.cost) AS cost, 
	        SUM(dl.cost * dl.share) / SUM(dl.cost) AS share,
		   	d.note 
	    FROM dayte AS d 
		LEFT JOIN person ON d.person = person.ID
	    LEFT JOIN dayte_locations AS dl ON dl.dayte = d.ID
	    LEFT JOIN location ON location.ID = dl.location
        LEFT JOIN city ON city.ID = location.city
        WHERE $filter->where
	    GROUP BY d.ID
	    ORDER BY $order->orderby";
$dayte_table = mysqli_query($link, $sql) or die(mysqli_error($link));
// echo "<pre>";
// echo $sql;
// print_r(get_defined_vars());
// echo "</pre>";
// count number of selected items and compare with total number of items
$daytes_selected = mysqli_num_rows($dayte_table);
$result = mysqli_query($link, "SELECT COUNT(*) FROM dayte") or die(mysqli_error($link));
$tmp = mysqli_fetch_array($result);
$daytes_total = $tmp['COUNT(*)'];
$filter_message = "Selected $daytes_selected of $daytes_total daytes";
if ($daytes_total == $daytes_selected) { $filter_hidden = true;} else { $filter_hidden = false;}
?>
<!DOCTYPE html>
<html>
<head>
	<title>View Dates</title>
	<link rel="stylesheet" href="style.css">

	<!-- <jQuery requirements> -->
	<script type="text/javascript" src="//code.jquery.com/jquery-1.8.3.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<!-- </jQuery requirements> -->

	<script>
		$( function() {
			<?php 
			$order->js(false);
			$filter->js($filter_hidden); ?>
		} );
	</script>
</head>
<body>
<?php if(isset($_GET['message'])) {echo $_GET['message']."<br>";} ?>
<div class="baseline">
	<?php
		$order->button_sort();
		$filter->button(); ?>
	<a href="control.php">Back</a>
</div>
<?php 
	$order->html_sort(); 
	$filter->html($filter_message);
?>
<div class="baseline">
	<a href="add_dayte.php">[Add Date]</a><!-- <a href="map_dayte.php">[View Map]</a> -->
	<table border="1">
		<tr><td></td>
			<td>person</td>
			<td>Start</td>
			<td>Duration</td>
			<td>Location</td>
			<td>Cost</td>
			<td>Share</td>
			<td>Notes</td>
		</tr>
	<?php
		while ($row = mysqli_fetch_array($dayte_table))	{
			$location_flags = "";
			foreach(explode(",", $row['locationISO']) as $locationISO) {
				$location_flags .="<img src='icon/country/24/$locationISO.png' height='24'>";
			}
			echo "<tr><td><a href='process_delete.php?field=dayte&id=$row[ID]&sure=0'><img src='icon/basic/delete/Delete_24x24.png' height='24'></a>"; 
				echo "<a href='add_dayte.php?edit_id=$row[ID]'><img src='icon/basic/edit/Edit_24x24.png' height='24'></a>";
				echo "<a href='map_dayte.php?id=$row[ID]'><img src='icon/googleMaps-24.png' height='24'></a></td>";
			echo "<td><img src='icon/country/24/$row[nationality].png' height='24'>$row[person]</td>";
			echo "<td>" . date("j F Y H:i", $row['start']) . "</td>";
			echo "<td class='numeric'>" . round($row['duration'] / 60, 0) . "</td>";
			echo "<td>$location_flags"."$row[location]</td>";
			echo "<td>" . floatval($row['cost']) . "</td>";
			echo "<td>" . round($row['share'], 2) . "</td>";
			echo "<td>$row[note]</td></tr>";
		}
	?>
	</table>
	<a href="control.php">Back</a>
</div>
</body>
</html>