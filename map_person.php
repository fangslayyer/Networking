<?php
require 'config.php';
require 'functions.php';


$filter = new person_filter;
$sort_fields = array('ID', 'name', 'rating', 'nationality');
$order = new sorter('result', 'persons_map', $sort_fields, true);
$map = new clusterMap_person($filter->where, $order->orderby);
?>
<!DOCTYPE html>
<html>
<head>
	<!-- <jQuery requirements> -->
	<script type="text/javascript" src="//code.jquery.com/jquery-1.8.3.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<!-- </jQuery requirements> -->
	<link rel="stylesheet" href="style.css">
	<title>Map of persons</title>

	<?php $map->js(); ?>
</head>
<body>

<script>
	$( function() {
		<?php 
		$order->js(true);
		$filter->js(true); ?>
	} );
</script>
<div class="baseline">
	<?php
	$order->button_sort();
	$filter->button(); ?>
	<a href="view_person.php">Back</a>
</div>
<?php 
	$order->html_sort();
	$filter->html(); ?>
<div class="cluster-map" id="google-maps"></div>

</body>
</html>