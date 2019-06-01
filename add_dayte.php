<?php
require 'config.php';
require 'functions.php';

if(isset($_GET['edit_id'])) {
	$EditMode = true;
} else {
	$EditMode = false;
}

if($EditMode) {
	$sql = "SELECT * FROM dayte WHERE ID = $_GET[edit_id]";
	$result = mysqli_query($link, $sql);
	$Edit_Dayte = mysqli_fetch_array($result);

	$sql = "SELECT location, sequence, cost, share FROM dayte_locations 
			WHERE dayte = $_GET[edit_id] ORDER BY sequence";
	$location_result = mysqli_query($link, $sql) or die(mysqli_error($link));
}

$location		= mysqli_query($link, "SELECT l.ID, l.name, city.country FROM location AS l 
											LEFT JOIN city ON city.ID = l.city ORDER BY " . RecentOrderBy('location', 'l.ID'));
$location_default = "";
$max_location = 0;
while ($row = mysqli_fetch_assoc($location)) {
	if ($EditMode) {
		foreach ($row as $heading => $value){
			$edit_location[$max_location][$heading] = $value;
		}
		$max_location++;
	}
	$location_default .= "<option value='$row[ID]' data-image='icon/country/24/$row[country].png' ";
	$location_default .=">$row[name]</option>";
}
// echo "<pre>";
// print_r($edit_location);
// die;
?>
<!DOCTYPE html>
<html>
<head>
	<title>Add Date</title>

	<link rel="stylesheet" href="style.css">
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<!-- <Date picker> -->
	<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/0.5.10/css/bootstrap-material-design.min.css"> -->
	<link rel="stylesheet" href="./widgets/dateSelector/bootstrap-material-datetimepicker.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="http://momentjs.com/downloads/moment-with-locales.min.js"></script>
	<script src="./widgets/dateSelector/bootstrap-material-datetimepicker.js"></script>
	<!-- </Date picker> -->
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
<!-- <"add more" buttons>-->
<script type="text/javascript">
	$(document).ready(function(){
		// Add more buttons
	    var row = "<tr><td><select name='location_array[]' class='msdropdown'><option value=''>Select Location</option>";
			row +="<?php echo $location_default; ?>";
	    	row +="</select></td>";
	    	row +="<td><input type='text' name='cost_array[]' placeholder='Cost'> <input type='text' name='share_array[]' placeholder='Share'> "
	    	row +="<button class='remove_line'>Remove</button></td></tr>";
		$("#addMore").click(function(e){
			e.preventDefault();
			$("#fieldlist").append(row);
			$("select.msdropdown").msDropdown({roundedBorder:false}); // newly added list will also get "msdropdown" style
		})
		$("#fieldlist").on("click", ".remove_line", function(e) {
			e.preventDefault();
			$(this).parent().parent().hide(300, function() {$(this).remove();});
		})
	})
</script>
<!-- </"add more" buttons>-->

<form action="process.php?field=dayte" method="post">
<table>
	<tr><td>person</td>
		<td><select name ="person" class="msdropdown">
		<?php
			$sql = "SELECT ID, name, nationality FROM person ORDER BY " . RecentOrderBy('person', 'ID');
			$result = mysqli_query($link, $sql) or die(mysqli_error());
			while ($row = mysqli_fetch_array($result))	{
				echo "<option value='$row[ID]' data-image='icon/country/24/$row[nationality].png' ";
				if (($EditMode && $Edit_Dayte['person'] == $row['ID']) || (isset($_GET['person_id'])) && $_GET['person_id'] == $row['ID']) {echo "selected"; }
				echo ">$row[name]</option>";
			}
		?></select></td></tr>
	<tr><td>Start</td><td><input type="text" name="start" id="date-format" maxlength="50" placeholder="click to select" value="<?php if ($EditMode) {echo date("j F Y H:i", $Edit_Dayte['start']); } ?>">
		<script type="text/javascript">
			$('#date-format').bootstrapMaterialDatePicker
			({
				format: 'DD MMMM YYYY HH:mm'
			});
		</script></td></tr>
	<tr><td>Duration</td><td><input type="text" name="duration" maxlength="30" value="<?php if ($EditMode) {echo $Edit_Dayte['duration']/60 ; } ?>"></td></tr>
	<tr><td colspan="2">Locations</td></tr>
</table>
<table id="fieldlist">
<?php 
if ($EditMode) { 
	while ($row = mysqli_fetch_array($location_result)) { ?>
		<tr><td><select name="location_array[]" class="msdropdown">
			<?php 
				foreach ($edit_location as $row_number => $heading) {
					echo "<option value='$heading[ID]' data-image='icon/country/24/$heading[country].png' ";
					if ($heading['ID'] == $row['location']) {echo " selected";}
					echo ">$heading[name]</option>";
				}
			?></select></td>
			<td><input type="text" name="cost_array[]" placeholder="Cost" value="<?php echo floatval($row['cost']); ?>">
				<input type="text" name="share_array[]" placeholder="Share" value="<?php echo floatval($row['share']); ?>"> 
				<button class="remove_line">Remove</button></td>
		</tr>
<?php	} 
} else { ?>
	<tr><td><select name="location_array[]" class="msdropdown">
		<?php echo $location_default; ?></select></td>
		<td><input type="text" name="cost_array[]" placeholder="Cost"><input type="text" name="share_array[]" placeholder="Share"></td>
	</tr>
<?php } ?>
</table>
<button id="addMore">Add additional location</button> <a href="add_location.php" target="_blank">Add location to database</a>
<br>
Notes:<br>
<textarea name="note" rows="15" cols="100" maxlength="2500"><?	php if($EditMode) {echo $Edit_Dayte['note'];} ?></textarea>
<br>
<input type="hidden" name="edit_id" value="<?php if ($EditMode) { echo $_GET['edit_id']; } ?>">
<input type="submit"><br>
<a href="control.php">Cancel</a>

<!-- scripts required for chosen filter as you type -->
<script src="chosen/chosen.jquery.js"></script>
<script src="chosen/prism.js"></script>
<script src="chosen/init.js"></script> 	
</form>


</body>
</html>