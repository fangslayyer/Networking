<?php
require 'config.php';
require 'functions.php';

if(isset($_GET['edit_id'])) {
	$EditMode = true;
	$edit_id = $_GET['edit_id'];
} else {
	$EditMode = false;
}

$status 		= mysqli_query($link, "SELECT * FROM status");
$nationality 	= mysqli_query($link, "SELECT * FROM country WHERE active = 1 AND IsNumeric(ID) = false ORDER BY " . RecentOrderBy('country', 'ID'));
$city 			= mysqli_query($link, "SELECT c.ID, c.name, country.ID AS country FROM city AS c 
											LEFT JOIN country ON c.country = country.ID 
											WHERE country.active = 1 AND c.ID < 10000
											ORDER BY " . RecentOrderBy('city', 'c.ID', false) . " country.name, c.name");
$hometown 		= mysqli_query($link, "SELECT c.ID, c.name, country.ID AS country FROM city AS c 
											LEFT JOIN country ON c.country = country.ID 
											WHERE country.active = 1 AND c.ID < 10000");
$location		= mysqli_query($link, "SELECT l.ID, l.name, city.country FROM location AS l 
											LEFT JOIN city ON city.ID = l.city ORDER BY " . RecentOrderBy('location', 'l.ID'));
$approach		= mysqli_query($link, "SELECT * FROM approach ORDER BY name DESC");
$platform		= mysqli_query($link, "SELECT * FROM platform ORDER BY name");
$tags 			= mysqli_query($link, "SELECT ID, name FROM tag WHERE category_ID = 1  ORDER BY " . RecentOrderBy('tag', 'ID', false) . " name");

if($EditMode) {
	$sql = "SELECT * FROM person WHERE ID = $_GET[edit_id]";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));
	$person = mysqli_fetch_array($result);

	$sql = "SELECT type, handle, platform.name AS platform FROM contact 
		LEFT JOIN platform ON contact.type = platform.ID
		WHERE person = $_GET[edit_id]";
	$platform_result = mysqli_query($link, $sql) or die(mysqli_error($link));

	$sql = "SELECT ID, name, note FROM picture WHERE person = $_GET[edit_id]";
	$img_result = mysqli_query($link, $sql) or die(mysqli_error($link));

	$sql = "SELECT tag_ID FROM tag_assoc WHERE category_ID = 1 AND target_ID = $person[ID]";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));
	$tag_edit = array();
	while ($row = mysqli_fetch_array($result)) {
		array_push($tag_edit, $row['tag_ID']);
	}
} 

$platform_default = "";
while ($row = mysqli_fetch_array($platform)) {
	$platform_default .= "<option value='$row[ID]'>$row[name]</option>";
}

$status_options = "";
while ($row = mysqli_fetch_array($status)) {
	$status_options .= "<option value='$row[ID]' ";
		if ($EditMode && $person['status'] == $row['ID']) {$status_options .= " selected";}
	$status_options .= ">$row[name]</option>";
}

$nationality_options = "";
while ($row = mysqli_fetch_array($nationality)) { 
	$nationality_options .= "<option value='$row[ID]'  data-image='icon/country/24/$row[ID].png' "; 
	if($EditMode && $person['nationality'] == $row['ID']) {$nationality_options .= " selected"; }
	$nationality_options .= ">$row[name]</option>";
}

$hometown_options = "<option value=''>Select Hometown</option>";
while ($row = mysqli_fetch_array($hometown)) {
	$hometown_options .= "<option value='$row[ID]' data-image='icon/country/24/$row[country].png' id='$row[country]' ";
	if($EditMode && $person['hometown'] == $row['ID']) {$hometown_options .= " selected" ;}
	$hometown_options .= ">$row[name]</option>";
}

$city_options = "";
while ($row = mysqli_fetch_array($city)) {
	$city_options .= "<option value='$row[ID]' data-image='icon/country/24/$row[country].png' ";
	if($EditMode && $person['city'] == $row['ID']) {$city_options .= " selected" ;}
	$city_options .= ">$row[name]</option>";
}

$location_options = "";
while ($row = mysqli_fetch_array($location)) {	
	$location_options .= "<option value='$row[ID]' data-image='icon/country/24/$row[country].png' ";
	if($EditMode && $person['met'] == $row['ID']) {$location_options .= " selected" ; }
	$location_options .= ">$row[name]</option>";
}

$approach_options = "";
while ($row = mysqli_fetch_array($approach)) {	
	$approach_options .= "<option value='$row[ID]' ";
	if($EditMode && $person['approach'] == $row['ID']) {$approach_options .= "selected" ; }
	$approach_options .= ">$row[name]</option>";
}

	
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $EditMode? "Edit":"Add";?> person</title>	
	<link rel="stylesheet" href="style.css">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<!-- <tinymce rich text editor> -->
	<script src="/widgets/tinymce/tinymce.min.js"></script><!-- tinymce text editor -->
	<script src="/js/tinymce.js"></script>
	<!-- </tinymce rich text editor> -->
	<!-- <msdropdown> -->
	<link rel="stylesheet" type="text/css" href="widgets/msdropdown/dd.css" />
	<script src="widgets/msdropdown/jquery.dd.js"></script>
	<!-- </msdropdown> -->
	<!-- <Select2> -->
	<link href="/widgets/select2/dist/css/select2.min.css" rel="stylesheet" />
	<script src="/widgets/select2/dist/js/select2.min.js"></script>
	<!-- </Select2> -->
</head>
<body>
<!-- <msdropdown> -->
<script>
$(document).ready(function(e) {		
	$("select.msdropdown").msDropdown({roundedBorder:false});
	// $("select.msdropdown").select2();
});
	$(function() {
		$( "#date1, #date2" ).datepicker({
			showOn: "button",
			buttonImage: "/icon/basic/Calendar/Calendar_24x24.png",
			buttonImageOnly: true,
			buttonText: "Select date"
		});
	$(".tags").select2({
		placeholder: "Insert a tag",
		tags: true,
		tokenSeparators: [',', ' ']
	});

	function formatCountry (country) {
	  if (!country.value) { return country.text; }
	  var $country = $(
	    '<span><img src="icon/country/24/' + country.element.value.toLowerCase() + '.png" class="img-flag" /> ' + country.text + '</span>'
	  );
	  console.log(country);
	  return $country;
	};

	$(".msdropdown").select2({
	  templateResult: formatCountry,
      templateSelection: formatCountry
	});

	// sets country based on hometown
	$("#hometown").change(function() {
		var countryISO = $(this).children(":selected").attr("id");
		$("#nationality option[value='"+countryISO+"']").prop("selected", true);
		$("#nationality").trigger("change");
	});
});
</script>
<!-- </msdropdown> -->
<div class="column">
	<form action="process.php?field=person" method="post">
		<table>
			<tr><td>Name</td> 
				<td><input type="text" name="name" maxlength="30" value="<?php if($EditMode) {echo htmlspecialchars($person['name']);} ?>"></td>
			</tr>
			<tr><td>Status</td> 
				<td><select name="status">
						<?php echo $status_options ?>
					</select>
				</td>
			</tr>
			<tr><td>Rating</td> 
				<td><select name="rating">
					<?php for ($i=20; $i>=2; $i--) { 
						echo '<option value=" ' . $i/2 . '"';
						if ($EditMode && $person['rating'] == $i/2) { echo " selected"; }
						echo '>' . $i/2 . '</option>';}?>
					</select>
				</td>
			</tr>
			<tr><td>Connected</td>
				<td>
					<input type="radio" name="connected" value="1" <?php if($EditMode && $person['connected'] == 1) { echo " checked"; } ?> >Yes 
					<input type="radio" name="connected" value="0" <?php if(!$EditMode || $person['connected'] == 0) { echo " checked"; } ?> >No
				</td>
			</tr>
			<tr><td nowrap>Nationality</td>
				<td><select name="nationality"  class="msdropdown" id="nationality">
						<?php echo $nationality_options ?>
					</select>
				</td>
			</tr>
			<tr><td>Hometown</td>
				<td><select name="hometown" class="msdropdown" id="hometown">
						<?php echo $hometown_options ?>
					</select>
				</td>
			</tr>
			<tr><td>Current City</td>
				<td><select name="city" class="msdropdown">
						<?php echo $city_options ?>
					</select>
				</td>
			</tr>
			<tr><td>Met</td>
				<td nowrap>
					<select name="location" class="msdropdown">	
						<?php echo $location_options ?>
					</select> <a href="add_location.php" target="_blank">Add Location</a>
				</td>
			</tr>
			<tr><td>Approach</td>
				<td><select name="approach">						
						<?php echo $approach_options ?>
					</select>
				</td>
			</tr>
			<tr><td>Last Ping</td>
				<td><input type="text" name="lastPing" id="date1" maxlength="50" 
						value="<?php if ($EditMode && !empty($person['lastPing'])) {echo date("Y-m-d", $person['lastPing']); } ?>">
				</td>
			</tr>
			<tr><td>Next Ping</td>
				<td><input type="text" name="nextPing" id="date2" maxlength="50" 
						value="<?php if ($EditMode && !empty($person['nextPing'])) {echo date("Y-m-d", $person['nextPing']); } ?>">
				</td>
			</tr>
			<tr><td>Ping Interval (days)</td>
				<td><input type="text" name="pingInterval" maxlength="3" 
						value="<?php if($EditMode && !empty($person['pingInterval'])) {echo $person['pingInterval']/86400;} ?>">
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php  if($EditMode) {tag_selector($EditMode, $tag_edit);} else {tag_selector();} ?>
				</td>
			</tr>
			<tr><td colspan="2">Contact(s)</td></tr>
		</table>

		<table id="fieldlist">
			<?php 
			if ($EditMode) { 
				while ($row = mysqli_fetch_array($platform_result)) {
					echo "<tr><td>$row[platform]</td><td><input type='text' name='handle' value='". htmlspecialchars($row['handle'], ENT_QUOTES) ."'>";
						echo "<a href='process_delete.php?field=contact&id=$edit_id&type=$row[type]'>[Del]</a></td></tr>";
				} 
			} ?>
		<tr>
			<td><select name='platform_array[]'>
					<option value=''></option>
					<?php echo $platform_default; ?></select></td>
			<td><input name="handle_array[]" type="text" placeholder="UserID"></td></tr>
		</table>
		<button id="addMore">Add additional contact</button>
		<br>
		Notes:<br>
		<textarea name="note" rows="15" cols="100" maxlength="2500"><?php if($EditMode) {echo htmlspecialchars($person['note'], ENT_QUOTES);} ?></textarea><br><br>
		<input type="hidden" name="EditMode" value="<?php echo $EditMode; ?>">
<?php
if ($EditMode) { ?>
		<input type="hidden" name="edit_id" value="<?php echo $_GET['edit_id']; ?>">
<?php } ?>
		<input type="submit">

		<!-- script for "add more" buttons-->
		<script type="text/javascript">
			$(document).ready(function(){
				// Add more buttons
			    var row = "<tr><td><select name='platform_array[]'><option value=''></option>";
					row +="<?php echo $platform_default; ?>";
			    	row +="</select></td>";
			    	row +="<td><input type='text' name='handle_array[]' placeholder='UserID'> <button class='remove_line'>Remove</button></td></tr>";
				$("#addMore").click(function(e){
					e.preventDefault();
					$("#fieldlist").append(row);
				})
				$("#fieldlist").on("click", ".remove_line", function(e) {
					e.preventDefault();
					$(this).parent().parent().remove();
				})
			})
		</script>
	</form>
	<?php if ($EditMode) { 
		echo '<a href="view_person.php">Cancel</a>';
	} ?>
		<a href="control.php">Back to Homepage</a>
</div>
<div class="column">
	<?php
	if ($EditMode) {
		while ($img_info = mysqli_fetch_array($img_result)) {
			echo "<div class='picture'>";
			echo "<img src='pictures/". htmlspecialchars($img_info['name'], ENT_QUOTES) ."' maxheight='500'><br>";
			echo "<a href='delete_picture.php?proc_id=$img_info[ID]'>[Delete]</a> ";
			echo "<a href='add_picture.php?proc_id=$img_info[ID]&EditMode=1'>[Edit]</a>";
			echo "</div>";		
		}
	}
	?>	
</div>
</body>
</html>