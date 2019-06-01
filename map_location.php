<?php
require 'config.php';
require 'functions.php';


$sort_fields = array('ID', 'name', 'city', 'country');
$order = new sorter('result' ,'location', $sort_fields, true);
$map = new clusterMap_location("1", $order->orderby);
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
	<title>Map of locations</title>

	<?php $map->js(); ?>
</head>
<body>

<script>
	$( function() {
		<?php 
		$order->js(true); ?>
	} );
</script>
<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>">Back</a>
<?php 
	$order->html_sort(); ?>

<div class="cluster-map" id="google-maps"></div>
</body>
</html>