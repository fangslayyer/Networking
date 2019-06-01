<?php
require 'config.php';
require 'functions.php';


$order = new sorter('result', 'picture', array('person','file'))
?>
<!DOCTYPE html>
<html>
<head>
	<title>View Pictures</title>
	<link rel="stylesheet" href="style.css">

	<!-- <jQuery requirements> -->
	<script type="text/javascript" src="//code.jquery.com/jquery-1.8.3.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<!-- </jQuery requirements> -->

	<script>
		$( function() {
			<?php 
			$order->js(false); ?>
		} );
	</script>
</head>
<body>
<div class="baseline">
	<?php
		$order->button_sort();?>
	<a href="control.php">Back</a>
</div>
<?php $order->html_sort(); ?>
<div class="baseline">
	<table border="1">
		<tr><td></td><td>Person</td><td>Picture</td><td>Note</td></tr>
	<?php
		$sql = "SELECT p.ID AS ID, p.name AS file, person.name AS person, p.note FROM picture AS p 
					LEFT JOIN person ON p.person = person.ID
					ORDER BY $order->orderby";
		$result = mysqli_query($link, $sql) or die(mysqli_error($link));
		while ($row = mysqli_fetch_array($result))	{
			echo "<tr><td><a href='delete_picture.php?proc_id=$row[ID]'><img src='icon/basic/delete/Delete_24x24.png' height='24'></a>";
				echo "<a href='add_picture.php?proc_id=$row[ID]'><img src='icon/basic/edit/Edit_24x24.png' height='24'></a></td>";
			echo "<td>$row[person]</td>";
			echo "<td><img src='pictures/$row[file]' height='100px'></td>";
			echo "<td>$row[note]</td></tr>";
		}
	?>
	</table>
	<br>
	<a href="control.php">Back</a>
</div>
</body>
</html>