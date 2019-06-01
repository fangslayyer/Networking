<?php
require 'config.php';
require 'functions.php';

foreach ($_POST as $key => $value)	{
	$$key = $value;
}
foreach ($_GET as $key => $value)	{
	$$key = $value;
}

// cookies for updating recent selections
if(!isset($_COOKIE['recent'])){
	$test = array(array());
	setcookie('recent', json_encode($test), MAX_COOKIE, '/', 'localhost');
	$_COOKIE['recent'] = json_encode($test);
}

function empty2null($value) {
	if (trim($value) === "") {
		return "NULL";
	} else {
		return "'$value'";
	}
}

function UpdateRecent($field, $id) { 
	$MAX_RECENT = 9; // number of recent enteries you wish to store -1
	$recent = json_decode($_COOKIE['recent'], true);
	if (isset($recent[$field])){
		$notFound = true;
		for ($i = 0; $i < count($recent[$field]) && $notFound; $i++) {
			if ($recent[$field][$i] == $id) {
				$front = array_slice($recent[$field], 0, $i);
				$back = array_slice($recent[$field], $i + 1);
				array_unshift($front, $id);
				$recent[$field] = array_merge($front, $back);
				$notFound = false;
			}
		}
		if (isset($recent[$field][$MAX_RECENT])) {
			unset($recent[$field][$MAX_RECENT]);
		} 
		if ($notFound) {
			array_unshift($recent[$field], $id);
		}
	} else {
		$recent[$field][0] = $id;
	}
	$update = json_encode($recent);
	setcookie('recent', $update, MAX_COOKIE, '/', 'localhost');
	$_COOKIE['recent'] = $update;
	return;
}

function AddDayte_Locations($dayte_id, $location_array, $cost_array, $share_array, $updateLocation = false) {
	global $link;
	$sql2 = "";
	foreach ($location_array as $key => $location) {
		if (!empty($location)) {
			if ($updateLocation) {UpdateRecent('location', $location);}
			if ($cost_array[$key] == 0) {$share_array[$key] = "NULL";}
			$sql2 .= " ('$dayte_id', '$key', '$location', '$cost_array[$key]', '$share_array[$key]'),";
		}
	}
	if(!empty($sql2)) {
		$sql2 = "INSERT INTO dayte_locations (dayte, sequence, location, cost, share) VALUES " . $sql2;
		$sql2 = rtrim($sql2, ",");
		echo $sql2;
		mysqli_query($link, $sql2) or die(mysqli_error($link));
	}
	return;
}

function CheckAdd_tag($tag_array = array(), $person_id, $update_recent = false, $append_tags = false) {
	global $link;

	if (!$append_tags) {
		$sql = "DELETE FROM tag_assoc WHERE category_ID = 1 AND target_ID = '$person_id'";
		mysqli_query($link, $sql) or die(mysqli_error($link));
	}

	$existing_tags = array();
	$sql = "SELECT ID, name FROM tag WHERE category_ID = 1";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));
	while ($row = mysqli_fetch_array($result)) {
		array_push($existing_tags, $row['ID']);
	}

	foreach($tag_array as $tag_id_name) {
		$tag_id_name = mysqli_escape_string($link, $tag_id_name);
		$tag_id = explode("~", $tag_id_name)[0];
		$tag_name = explode("~", $tag_id_name)[1];
		if (empty($tag_name)) {
			$tag_name = ucfirst($tag_id);
			$tag_id = nextID('tag');
			$sql = "INSERT INTO tag (ID, category_ID, name) VALUES ($tag_id, 1, '$tag_name')";
			mysqli_query($link, $sql) or die(mysqli_error($link));
		} else {
			if(!in_array($tag_id, $existing_tags)) {
				echo "no ~ character allowed in tags!";
				continue;
			}
		}
		$tag_query = "INSERT INTO tag_assoc (tag_ID, category_ID, target_ID) VALUES ('$tag_id', 1, '$person_id')";
		echo "tag_query";
		mysqli_query($link, $tag_query) or die(mysqli_error($link));
		if($update_recent) { UpdateRecent('tag', $tag_id);}
	}
	return;
}

if (isset($edit_id) && !empty($edit_id)) {
	$EditMode = true;
} else {
	$EditMode = false;
}

if ($EditMode) {
	$message = "Successfully edited ";
	switch ($field) {
		case "venue":
		case "platform":
		case "approach":
		case "status":
		case "tag":
			$name = mysqli_real_escape_string($link, $name);
			$sql = "UPDATE $field SET name = '$name' WHERE ID = $edit_id";
			$message .= $name;
			mysqli_query($link, $sql) or die(mysqli_error($link));
			header("Location: control.php?message=$message");
		break;

		case "country":
			$name = mysqli_real_escape_string($link, $name);
			$sql = "UPDATE $field SET name = '$name', active = '$active' WHERE ID = '$edit_id'";
			mysqli_query($link, $sql) or die(mysqli_error($link));
			$message .= $name;
			header("Location: view_country.php");
		break;

		case "city":
			$name = mysqli_real_escape_string($link, $name);
			$sql = "UPDATE city SET 
						name = '$name', 
						country = '$country', 
						lat = IF('$lat'='', NULL, '$lat'), 
						lng = IF('$lng'='', NULL, '$lng') 
					WHERE ID = $edit_id";
			$message .= $name;
			mysqli_query($link, $sql) or die(mysqli_error($link));
			header("Location: control.php?message=$message");
		break;

		case "location":
			$name = mysqli_real_escape_string($link, $name);
			$note = mysqli_real_escape_string($link, $note);
			$sql = "UPDATE location SET 
						googleID = IF('$googleID'='', NULL, '$googleID'),
						name = '$name', 
						type = '$location_type',
						city = '$city', 
						lat = IF('$lat'='', NULL, '$lat'), 
						lng = IF('$lng'='', NULL, '$lng'),
						note = '$note'
					WHERE ID = IF('$edit_id'='', NULL, '$edit_id')";
			$message .= $name;
			mysqli_query($link, $sql) or die(mysqli_error($link));
			header("Location: control.php?message=$message");
		break;

		case "person":
			if (isset($tag)) {CheckAdd_tag($tag, $edit_id);}

			$result = mysqli_query($link, "SELECT g.city AS current_city FROM person AS g WHERE g.ID = $edit_id") or die(mysqli_error($link));
			$get_city = mysqli_fetch_array($result);
			$current_city = $get_city['current_city'];
			if ($current_city == $city) {
				$city_lastupdate = "";
			} else {
				$city_lastupdate = "city_lastupdate = ". time() . ",";
			}

			$name = mysqli_real_escape_string($link, $name);
			$note = mysqli_real_escape_string($link, $note);
			$lastPing = strtotime($lastPing);
			$nextPing = strtotime($nextPing);
			$pingInterval = $pingInterval * 86400;
			$sql = "UPDATE person SET 
						status = '$status', 
						name = '$name', 
						rating = '$rating', 
						connected = '$connected', 
						nationality = '$nationality', 
						hometown = " . empty2null($hometown) . ",
						city = '$city', 
						$city_lastupdate
						met = '$location', 
						approach = '$approach',
						lastPing = " . empty2null($lastPing) . ",
						nextPing = " . empty2null($nextPing) . ",
						pingInterval = " . empty2null($pingInterval) . ",
						note = '$note' 
					WHERE ID = $edit_id";
			$sql2 = "";
			foreach ($platform_array as $key => $platform) {
				if(!empty($platform) && !empty($handle_array[$key])) {
					$handle_array[$key] = mysqli_real_escape_string($link, $handle_array[$key]);
					$sql2 .= " ('$edit_id', '$platform', '$handle_array[$key]'),"; }
			}
			if (!empty($sql2)) {
				$sql2 = "INSERT INTO contact (person, type, handle) VALUES " . $sql2;
				$sql2 = rtrim($sql2, ",");
				$sql2 .= " ON DUPLICATE KEY UPDATE person=VALUES(person), type=VALUES(type), handle=VALUES(handle) ";
				mysqli_query($link, $sql2) or die(mysqli_error($link));
			}

			$message .= $name;
			mysqli_query($link, $sql) or die(mysqli_error($link));
			header("Location: view_person.php?message=$message");
		break;

		case "dayte":
			$note = mysqli_real_escape_string($link, $note);
			$start = strtotime($start);
			$duration = 60 * $duration;
			$sql = "UPDATE dayte SET
				person = '$person',
				start = '$start',
				duration = '$duration',
				note = '$note'
				WHERE ID = $edit_id";
			mysqli_query($link, $sql) or die(mysqli_error($link));

			mysqli_query($link, "DELETE FROM dayte_locations WHERE dayte = '$edit_id'") or die(mysqli_error($link));
			AddDayte_Locations($edit_id, $location_array, $cost_array, $share_array, false);
			$message .= "date";
			header("Location: control.php?message=$message");
		break;	

		case "current_location":
			$current['name'] = $name;
			$current['city'] = $city;
			$current['lat'] = $lat;
			$current['lng'] = $lng;
			$current['googleID'] = $googleID;
			setcookie('current_location', json_encode($current), MAX_COOKIE, '/', 'localhost');
			$message .= "your current location";
			header("Location: control.php?message=$message");
		break;

		case "ping":
			$sql = "UPDATE person SET lastPing = '" . time() . "' WHERE ID = '$edit_id' ";
			mysqli_query($link, $sql) or die(mysqli_error($link));
			$message = "Sucessfully pinged $edit_id";
			header("Location: view_person.php?message=$message");
		break;
	}
} else {
	$message = "Successfully added ";
	switch ($field) {
		case "venue":
		case "platform":
		case "country":
		case "approach":
		case "status":
		case "tag":
			$name = mysqli_real_escape_string($link, $name);
			$sql = "INSERT INTO $field (name) VALUES ('$name');";
			$message .= $name;
			mysqli_query($link, $sql) or die(mysqli_error($link));
			header("Location: view_$field.php?message=$message");
		break;

		case "city":
			$name = mysqli_real_escape_string($link, $name);
			$sql = "INSERT INTO city (name, country, lat, lng) VALUES ('$name', '$country', IF('$lat'='', NULL, '$lat'), IF('$lng'='', NULL, '$lng'))";
			$message .= $name;
			mysqli_query($link, $sql) or die(mysqli_error($link));
			header("Location: control.php?message=$message");
		break;

		case "location":
			$check = "SELECT ID, name, googleID FROM location WHERE googleID = '$googleID'";
			$result = mysqli_query($link, $check) or die(mysqli_error($link));
			if(mysqli_num_rows($result)) {
				$row = mysqli_fetch_array($result);
				$message = "$row[name] has already been added!";
				header ("Location: add_location.php?edit_id=$row[ID]&message=$message");
			} else {
				UpdateRecent('location', nextID('location'));
				UpdateRecent('city', $city);
				$name = mysqli_real_escape_string($link, $name);
				$note = mysqli_real_escape_string($link, $note);
				$sql = "INSERT INTO location (googleID, name, type, city, lat, lng, note) 
						VALUES (".empty2null($googleID).", '$name', '$location_type', '$city', ".empty2null($lat).", ".empty2null($lng).", ".empty2null($note).")";
				$message .= $name;
			}
			mysqli_query($link, $sql) or die(mysqli_error($link));
			header("Location: view_location.php?message=$message");
		break;

		case "person":
			$person_id = nextID('person');
			$now = time();
			UpdateRecent('person', $person_id);
			UpdateRecent('country', $nationality);
			UpdateRecent('city', $city);
			UpdateRecent('location', $location);

			if (isset($tag)) {CheckAdd_tag($tag, $person_id, true);}
			
			$name = mysqli_real_escape_string($link, $name);
			$note = mysqli_real_escape_string($link, $note);
			$lastPing = strtotime($lastPing);
			$nextPing = strtotime($nextPing);
			$pingInterval = $pingInterval * 86400;
			$sql = "INSERT INTO person 	(status, name, rating, connected, nationality, hometown, city, city_lastupdate, met, approach, lastPing, nextPing, pingInterval, note) VALUES
					('$status', '$name', '$rating', '$connected', '$nationality',".empty2null($hometown)." '$city', '$now', '$location', '$approach', ".empty2null($lastPing).", ".empty2null($nextPing).", ".empty2null($pingInterval).", '$note')";
			mysqli_query($link, $sql) or die(mysqli_error($link));
			$sql2 = "";
			foreach ($platform_array as $key => $platform) {
				if(!empty($platform) && !empty($handle_array[$key])) { 
					$handle_array[$key] = mysqli_real_escape_string($link, $handle_array[$key]);
					$sql2 .= " ('$person_id', '$platform', '$handle_array[$key]'),"; }
			}
			if (!empty($sql2)) {
				$sql2 = "INSERT INTO contact (person, type, handle) VALUES " . $sql2;
				$sql2 = rtrim($sql2, ",");
				mysqli_query($link, $sql2) or die(mysqli_error($link));
			}

			$message .= $name;
			header("Location: view_person.php?message=$message");
		break;

		case "dayte":
			UpdateRecent('person', $person);
			$note = mysqli_real_escape_string($link, $note);
			$start = strtotime($start);
			$duration = 60 * $duration;
			
			AddDayte_Locations(nextID("dayte"), $location_array, $cost_array, $share_array, true);
			$sql = "INSERT INTO dayte	(person, start, duration, note) VALUES ('$person', '$start', '$duration', '$note')";
			mysqli_query($link, $sql) or die(mysqli_error($link));

			$message .= "date";
			header("Location: control.php?message=$message");
		break;

		case "assign_tags":
			// echo "<pre>";
			// print_r(get_defined_vars());
			if ($remove) {
				$sql = "";
				foreach ($tag as $tag_id_name) {
					$tag_id = explode("~", $tag_id_name)[0];
					foreach($person as $person_id) {
						$sql .= "category_ID = 1 AND tag_ID = $tag_id AND target_ID = $person_id OR ";
					}
				}
				if (!empty($sql)) {
					$sql = rtrim($sql, "OR ");
					$sql = "DELETE FROM tag_assoc WHERE " . $sql;
					mysqli_query($link, $sql) or die(mysqli_error($link));
					$message = "Successfully removed tags";
					header("Location: view_person.php?message=$message");
				} else {
					$message = "Unable to remove tags";
					header("Location: view_person.php");
				}
			} else {
				foreach ($person as $person_id) {
					CheckAdd_tag($tag, $person_id, false, !empty($append));
				}
				$message = "Sucessfully added tags";
				header("Location: view_person.php?message=$message");
			}
		break;
	}
	$message .= " to $field list.";
}

// echo "<pre>";
// print_r(get_defined_vars());

?>