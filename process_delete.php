<?php
require('config.php');

foreach ($_POST as $key => $value)	{
	$$key = $value;
}

foreach ($_GET as $key => $value)	{
	$$key = $value;
}

	
$message = "Successfully deleted ";
switch ($field) {
	case "contact":
		$sql = "DELETE FROM contact WHERE person='$id' AND type='$type'";
		mysqli_query($link, $sql) or die(mysqli_error($link));
		header("Location: add_person.php?edit_id=$id");
	break;

	case "person":
		if ($sure) {
			$sql = "SELECT name FROM picture WHERE person='$id';";
			$result = mysqli_query($link, $sql) or die(mysqli_error($link));
			while ($row = mysqli_fetch_array($result)) {
				unlink("pictures/$row[name]");
			}

			$sql = "SELECT dayte.ID AS dayte FROM dayte WHERE person = '$id'";
			$result = mysqli_query($link, $sql) or die(mysqli_error($link));
			$sqld = "DELETE FROM dayte_locations WHERE dayte IN (";
			while ($row = mysqli_fetch_array($result)) {
				$sql .="$row[dayte], ";
			}
			$sqld .="-1);";
			$sqld .="DELETE FROM person WHERE ID='$id';
					DELETE FROM dayte WHERE person='$id';
					DELETE FROM picture WHERE person='$id';
					DELETE FROM contact WHERE person='$id';";
			mysqli_multi_query($link, $sqld) or die(mysqli_error($link));
			header("Location: view_person.php?message=$message");
		} else {
			$result = mysqli_query($link, "SELECT name FROM person WHERE ID='$id'");
			$person = mysqli_fetch_array($result);
			echo "Are you sure you want to delete $person[name]? <br>
				This will also delete all <b>Dates, Pictures, and Contacts</b> associated with $person[name]. <br>
				<a href='process_delete.php?field=person&id=$id&sure=1'>Yes</a> <a href='view_person.php'>No</a>";
		}
	break;

	case "dayte":
		if($sure) {
			$sql = "DELETE FROM dayte WHERE ID = '$id'; 
					DELETE FROM dayte_locations WHERE dayte = '$id';";
			mysqli_multi_query($link, $sql) or die(mysqli_error($link));
			$message .= "date";
			header("Location: view_dayte.php?message=$message");
		} else {
			$sql = "SELECT d.ID AS ID, 
					person.name AS person,
		            person.nationality,
					d.start, 
				    D.duration,
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
		        WHERE d.ID = $id
			    GROUP BY d.ID";
			$result = mysqli_query($link, $sql) or die(mysqli_error($link));
			$row = mysqli_fetch_array($result);
			$location_flags = "";
			foreach(explode(",", $row['locationISO']) as $locationISO) {
				$location_flags .="<img src='icon/country/24/$locationISO.png' height='24'>";
			}
			echo "Are you sure you want to delete the following date?<br>";
			echo "<table border='1'><tr><td>person</td><td>Start</td><td>Duration</td><td>Venue</td><td>Cost</td><td>Share</td><td>Notes</td></tr>";
				echo "<td><img src='icon/country/24/$row[nationality].png' height='24'>$row[person]</td>";
				echo "<td>" . date("j F Y H:i", $row['start']) . "</td>";
				echo "<td class='numeric'>" . round($row['duration'] / 60, 0) . "</td>";
				echo "<td>$location_flags"."$row[location]</td>";
				echo "<td>" . floatval($row['cost']) . "</td>";
				echo "<td>" . round($row['share'], 2) . "</td>";
				echo "<td>$row[note]</td></tr>";
			echo "</table>";
			echo "<a href='process_delete.php?field=dayte&id=$id&sure=1'>Yes</a> <a href='view_dayte.php'>No</a>";
		}
	break;

	case "location":
		$sql = "SELECT l.name, city.name AS city, city.country FROM location AS l 
				LEFT JOIN city ON l.city=city.ID WHERE l.ID=$id";
		$result = mysqli_query($link, $sql) or die(mysqli_error($link));
		$location = mysqli_fetch_array($result);

		$sql_g = "SELECT * FROM person WHERE met = '$id'";
		$sql_dl = "SELECT * from dayte_locations WHERE location = '$id'";
		$result_g = mysqli_query($link, $sql_g);
		$result_dl = mysqli_query($link, $sql_dl);
		if (mysqli_num_rows($result_g)==0 && mysqli_num_rows($result_dl)==0) {
			if ($sure) {
				$sql = "DELETE FROM location WHERE ID = '$id'";
				mysqli_query($link, $sql) or die(mysqli_error($link));
				$message .= "location $location[name] in $location[city]";
				header("Location: view_location.php?message=$message");
			} else {
				echo "Are you sure you want to delete Location: $location[name] in <img src='icon/country/24/$location[country].png' height='24'>$location[city]?<br>";
				echo "<a href='process_delete.php?field=location&id=$id&sure=1'>Yes</a> <a href='view_dayte.php'>No</a>";
			}
		} else {
			if (mysqli_num_rows($result_g) != 0) {
				echo "Cannot delete this location as it is being used by person->met<br>";
			}
			if (mysqli_num_rows($result_dl) != 0) {
				echo "Cannot delete this location as it is being used by dayte_locations->location<br>";
			}
			echo "<a href='$_SERVER[HTTP_REFERER]'>Go back</a>";
		}
		
	break;

	case "approach":
		$sql = "SELECT * FROM person WHERE approach = '$id'";
	break;
	case "platform":
		$sql = "SELECT * FROM contact WHERE type = '$id'";
	break;
	case "venue":
		$sql = "SELECT * FROM location WHERE type = '$id'";
	break;
	case "status":
		$sql = "SELECT * FROM person WHERE status = '$id'";
}
switch ($field) {
	case "approach":
	case "platform":
	case "venue":
	case "status":
		$result = mysqli_query($link, $sql) or die(mysqli_error($link));
		if (mysqli_num_rows($result) == 0) {
			if ($sure) {
				$sql = "DELETE FROM $field WHERE ID = '$id'";
				mysqli_query($link, $sql) or die(mysqli_error($link));
				$message .= $field;
				header("Location: view_$field.php?message=$message");
			} else {
				$sql = "SELECT * FROM $field WHERE ID = $id";
				$result = mysqli_query($link, $sql) or die(mysqli_error($link));
				$row = mysqli_fetch_array($result);
				echo "Are you sure you want to delete $field: $row[name]?<br>";
				echo "<a href='process_delete.php?field=$field&id=$id&sure=1'>Yes</a> <a href='view_dayte.php'>No</a>";
			}			
		} else {
			echo "You can not delete this $field as it is used by another element<br>";
			echo "<a href='$_SERVER[HTTP_REFERER]'>Go back</a>";
		}
	break;
}


?>