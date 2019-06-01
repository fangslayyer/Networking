<?php
require 'config.php';
require 'functions.php';

$order_fields = array('ID', 'status', 'name', 'rating', 'connected', 'nationality', 'hometown', 'city', 'met', 'approach', 'lastPing', 'nextPing', 'distance');
$order = new sorter('result', 'person', $order_fields, true);
if (isset($_GET['linkOrder'])) {
	$order->orderby = $_GET['linkOrder'] . ", " . $order->orderby;
}

if (isset($_GET['reset']) && $_GET['reset'] == "result") {$order->reset();}
$filter = new person_filter;

// Query sql for table to display persons
$current_lat = currentLocation("lat");
$current_lng = currentLocation("lng");
$table_query = "SELECT g.ID AS ID, status.name AS status, g.name, g.rating, g.connected, g.lastPing, g.nextPing, g.pingInterval, g.note,
	country.name AS nationality, 
	country.ID AS nationalityISO,
	hcity.name AS hometown,
	city.name AS city, 
	city.country AS cityISO, 
	location.name AS met, 
	location.ID AS met_ID,
    location_country.locationISO,
	approach.name AS approach,  
	GROUP_CONCAT(DISTINCT picture.name) AS ImageNames, 
    GROUP_CONCAT(DISTINCT platform.name ORDER BY platform.name) AS platform, 
    GROUP_CONCAT(DISTINCT contact.handle ORDER BY platform.name) AS userid,
    GROUP_CONCAT(DISTINCT tag.name ORDER BY tag.name) AS tags,
    ( 6371 * acos( cos( radians($current_lat) ) 
          * cos( radians( city.lat ) ) 
          * cos( radians( city.lng ) - radians($current_lng) ) 
          + sin( radians($current_lat) ) 
          * sin( radians( city.lat ) ) ) ) AS distance 
    FROM person AS g 
    LEFT JOIN status ON g.status = status.ID
	LEFT JOIN country ON g.nationality = country.ID
	LEFT JOIN city ON g.city = city.ID
	LEFT JOin city AS hcity ON g.hometown = hcity.ID
	LEFT JOIN location ON g.met = location.ID
    LEFT JOIN (
        SELECT country.ID as locationISO, location.ID AS locationID FROM location 
		LEFT JOIN city ON city.ID = location.city
        LEFT JOIN country ON country.ID = city.country) AS location_country
        ON location_country.locationID = g.met
	LEFT JOIN approach ON g.approach = approach.ID
	LEFT JOIN picture ON g.ID = picture.person
    LEFT JOIN contact ON g.ID = contact.person
    LEFT JOIN platform ON platform.ID = contact.type
    LEFT JOIN tag_assoc ON g.ID = tag_assoc.target_ID 
    LEFT JOIN tag ON tag_assoc.tag_ID = tag.ID
    WHERE $filter->where
	GROUP BY g.ID
	HAVING $filter->having
	ORDER BY " . $order->orderby;
// echo "<pre>";
// echo $table_query;
// die();
$table_result = mysqli_query($link, $table_query) or die(mysqli_error($link));
$persons_selected = mysqli_num_rows($table_result);

// $table_headings = array('Status', 'Name', 'Rating', 'connected', 'Nationality', 'Location', 'Met', 'Approach', 'Contact', 'Distance', 'Last Ping', 'Next', 'Ping Interval', 'Notes', 'Pictures');
$table_fields = array('status', 'name', 'rating', 'connected', 'nationality', 'hometown', 'location', 'met', 'approach', 'contact', 'distance', 'lastPing', 'nextPing', 'pingInterval', 'tags', 'note', 'pictures');
$table = new person_column_sorter('column', 'person', $table_fields);
$table->build_table($table_result);
if (isset($_GET['reset']) && $_GET['reset'] == "column") {$table->reset();}

// count number of selected items and compare with total number of items
$persons_selected = mysqli_num_rows($table_result);
$result = mysqli_query($link, "SELECT COUNT(*) FROM person") or die(mysqli_error($link));
$tmp = mysqli_fetch_array($result);
$persons_total = $tmp['COUNT(*)'];
$filter_message = "Selected $persons_selected of $persons_total persons";
if ($persons_total == $persons_selected) { $filter_hidden = true;} else { $filter_hidden = false;}
?>

<!DOCTYPE html>
<html>
<head>
	<title>View persons</title>
	<link rel="stylesheet" href="style.css">

	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<!-- <Select2> -->
	<link href="/widgets/select2/dist/css/select2.min.css" rel="stylesheet" />
	<script src="/widgets/select2/dist/js/select2.min.js"></script>
	<!-- </Select2> -->
</head>
<body>
<a href="control.php">Back</a><br>
<?php if (isset($_GET['message'])) {echo $_GET['message'] . "<br>"; } ?>

<script>
$( function() {
	<?php 
	$order->js(true);
	$filter->js($filter_hidden);
	$table->js(true); ?>

	// <select2> - change tags option if remove checkbox is checked
	$(".assign_tags").hide();
	var tags_mode = false;
	$("#assign_tags_toggle").click(function() {
		tags_mode = !tags_mode;
		$(".assign_tags").toggle();
		$("span.operations").toggle();
		if (tags_mode) {
			$("tr.hide_selected_row").addClass('selected_row');
			$("tr.hide_selected_row").removeClass('hide_selected_row');
		} else {
			$("tr.selected_row").addClass('hide_selected_row');
			$("tr.selected_row").removeClass('selected_row');
		}
	});

	function useSelect2(remove_mode) {
		var placeholder;
		if (remove_mode) {
			enable_tags = false;
			placeholder = "Insert a tag to delete";
		} else {
			enable_tags = true;
			placeholder = "Insert a tag to assign";
		}
		$(".select2").select2({
			placeholder: placeholder,
			tags: enable_tags,
			tokenSeparators: [',', ' '],
			createTag: function (tag) {
				// check if the option is already there
				found = false;
				$(".select2 option").each(function() {
					if ($.trim(tag.term).toUpperCase() == $.trim($(this).text()).toUpperCase()) {
				        found = true;
				    }
				});

				// if it's not there, then show the suggestion
				if (!found) {
				    return {
				    	// value: tag.term,
				        id: tag.term,
				        text: tag.term + " (new)",
				        isNew: true
				    };
				}
			}
		});
	}

	useSelect2(false);
	$('#remove_tags:checkbox').change(function() {
		if($(this).is(':checked')) {
			$('#tags_submit').prop('value', "Remove Tags");
			useSelect2(true);
		} else {
			$('#tags_submit').prop('value', "Assign Tags");
			useSelect2(false);
		}
	});

	$('tr').click(function (event) {
		if (tags_mode && event.target.type !== 'checkbox') {
			$(':checkbox', this).trigger('click');
		}
	})

	$('.assign_tags:checkbox').change(function() {
		var isChecked = $(this).is(':checked');
		if (isChecked) {
			$(this).closest('tr').addClass('selected_row');
		} else { 
			$(this).closest('tr').removeClass('selected_row');
		}
	});
} );
</script>
<div class="baseline">
	<?php
	$order->button_sort();
	$filter->button();
	$table->button_sort(); ?>
	<button id="assign_tags_toggle">Tags Mode</button>
</div>
<?php 
$order->html_sort();
$filter->html($filter_message); 
$table->html_sort();
?>
<div class="baseline">
	<a href="add_person.php">[Add person]</a> 
	<a href="map_person.php">[View Map]</a> 
	<a href="control.php">[Control Panel]</a> <br>
<?php	
$table->html_table();
// echo "<pre>";
// print_r(get_defined_vars());
// echo "</pre>";
?>
</div>

<a href="control.php">Back</a>
</body>
</html>