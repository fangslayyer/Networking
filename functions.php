<?php
// Finds next autoincrement ID of table
// $table name of table of interest
// return next id
function nextID($table) {
	global $link;
	$sql = "SHOW TABLE STATUS FROM `network` WHERE `name` LIKE '$table'";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));
	$status = mysqli_fetch_array($result);
	return $status['Auto_increment'];
}

// Returns sql ORDER BY code to prioritize the 5 most recent selections
// $table name of table of interest
// $id_string name of identifier column
// return sql ORDER BY code to prioritize the 5 most recent selections
// 
// **Must add 1 after call of function if no other sort fields after**
function RecentOrderBy($table, $id_string, $onlyCondition = true) {
	$orderBy = "";
	if (isset($_COOKIE['recent'])) {
		$recent = json_decode($_COOKIE['recent'], true);
		if (isset($recent[$table])) {
			foreach ($recent[$table] as $value) {
				$orderBy .= " $id_string = '$value' DESC,";
			}
			$orderBy = rtrim($orderBy, ",");
			$orderBy .= " ";
		}
	}
	if (empty($orderBy)) {
		if ($onlyCondition) { return " 1";} 	else {return " ";}
	} else {
		if ($onlyCondition) { return $orderBy;} else {return $orderBy . ", ";}
	}
}

// Returns parameters of current location or returns parameters of Taipei if current location is not set
function currentLocation($field) {
	if (isset($_COOKIE['current_location']) && isset(json_decode($_COOKIE['current_location'], true)[$field])) {
		return json_decode($_COOKIE['current_location'], true)[$field];
	} else {
		switch ($field) {
			case "lat":
				return 25.0246; // coordinates of Taipei city
			break;
			case "lng":
				return 121.529;
			break;
		}
	}
}

function tag_selector($EditMode = false, $tag_edit = array()) {
	global $link;
	$tag = mysqli_query($link, "SELECT ID, name FROM tag WHERE category_ID = 1  ORDER BY " . RecentOrderBy('tag', 'ID', false) . " name");
	$tag_select = '<select multiple class="select2 tags" name="tag[]">';
	while ($row = mysqli_fetch_array($tag)) {
		$tag_select .= "<option value='$row[ID]~$row[name]' ";
		if($EditMode && in_array($row['ID'], $tag_edit)) {$tag_select .= "selected" ; }
		$tag_select .= ">$row[name]</option>";
	}
	$tag_select .= "</select>";
	return $tag_select;
}

// ||===============================================||
// ||					CLASSES		  				||
// ||===============================================||
abstract class filter {
	public $where;
	public $having;

	function __construct() {
		function getSQL(&$sql, $condition) {
			$sql = "";
			if (isset($_POST[$condition])) {
				foreach ($_POST[$condition] as $category_compare => $value) {
					if (empty($value) && $value != "0") {
						continue;
					}
					$category = explode("_", $category_compare)[0];
					$compare = explode("_", $category_compare)[1];

					if (is_array($value)) {
						foreach ($value as $value_2) {
							$sql .= "$category $compare '$value_2' OR ";
						}
						$sql = rtrim($sql, "OR ");
						$sql .= " AND ";
					} else { 
						$sql .= "$category $compare '$value' AND "; 
					}
				}
				if (!empty($sql)) {
					$sql = rtrim($sql, "AND ");
					$sql.= " ";
				} else {
					$sql = " 1 ";
				}
			} else { //	TODO: IMPLEMENT COOKIES
				$sql = " 1 ";
			}
		}
		getSQL($this->where, "where");
		getSQL($this->having, "having");
	}

	public function js($start_hidden) {
		if($start_hidden) {
			$hidden_js = '$("#filter").hide();';
		} else $hidden_js = "";

		echo <<<EOD
			$hidden_js
			$("#showHideFilters").click(function(){
				$("#filter").toggle(200);
			});
			$("#resetDefault").click(function() {
				$("div#filter option:selected").prop('selected', false);
				$("div#filter option[value='']").prop('selected', true);
			});
			
EOD;
		return;
	}

	// determines if current option was sent via POST, and selects it if it was
	protected function selected($condition, $category, $option_value) {
		if (isset($_POST[$condition][$category])) {
			if (is_array($_POST[$condition][$category])) {
				if (in_array($option_value, $_POST[$condition][$category])) {
					return " selected";
				}
			} else if ((string)($_POST[$condition][$category]) === (string)$option_value) {
				return " selected";
			}
		}
		return "";
	}

	// creats html button required to submit filter options
	public function button() {
		echo '<button id="showHideFilters">Show/Hide Filters</button>';
		return;
	}
}

class person_filter extends filter {
	
	public function html($message = "") {
		global $link;
		$current_lat = currentLocation("lat");
		$current_lng = currentLocation("lng");
		// Get options for filters
		$sql = "SELECT ID, name FROM country WHERE active = 1 AND IsNumeric(ID) = false ORDER BY name";
			$activeCountries = mysqli_query($link, $sql) or die(mysqli_error($link));
		$sql = "SELECT ID, name FROM approach ORDER BY name";
			$activeApproaches = mysqli_query($link, $sql) or die(mysqli_error($link));
		$sql = "SELECT ( 6371 * acos( cos( radians($current_lat) ) * cos( radians( city.lat ) ) 
		              * cos( radians( city.lng ) - radians($current_lng) ) 
		              + sin( radians($current_lat) ) 
		              * sin( radians( city.lat ) ) ) ) AS distance 
					FROM person
					LEFT JOIN city ON city.ID = person.city";
			$result = mysqli_query($link, $sql) or die(mysqli_error($link));
			$distances_tofilter = array(10, 20, 50, 100, 500, 1000);
			$distances_counter= array_fill(0, 6, 0);
			while ($row = mysqli_fetch_array($result)) {
				foreach ($distances_tofilter as $key => $sort_distance) {
					if ($row['distance'] < $sort_distance) {
						$distances_counter[$key]++;
					}
				}
			}
		$status = mysqli_query($link, "SELECT * FROM status") or die(mysqli_error($link));

		// Get html code for filter options
		$status_options = "";
		while ($row = mysqli_fetch_array($status)) {
			$status_options .= "<option value='$row[ID]' " .parent::selected("where", "status_=", $row['ID']). ">$row[name]</option>";
		}
		
		$rating_options = "";
		for ($i=10; $i>=5; $i--) {
			$rating_options .= "<option value='$i' " .parent::selected("where", "rating_>=", $i). ">$i or above</option>";
		}

		$nationality_options = "";
		while ($row = mysqli_fetch_array($activeCountries)) {
			$nationality_options .= "<option value='$row[ID]' " .parent::selected("where", "nationality_=", $row['ID']). ">$row[name]</option>";
		}

		$approach_options = "";
		while ($row = mysqli_fetch_array($activeApproaches)) {
			$approach_options .= "<option value='$row[ID]' " .parent::selected("where", "approach_=", $row['ID']). ">$row[name]</option>";
		}

		$distance_options = "";
		foreach ($distances_tofilter as $key => $this_distance) {
			$distance_options .= "<option value='$this_distance' " .parent::selected("having", "distance_<=", $this_distance);
			$distance_options .= ">$this_distance" . "km ($distances_counter[$key] persons)</option>";
		}

		echo <<<EOD
<div class="column" id="filter">
	$message
	<form method="POST" action="$_SERVER[PHP_SELF]">
		<table>
			<tr><td>Status</td>
				<td>Rating</td>
				<td>Nationality</td>
				<td>Location</td>
				<td>Approach</td>
				<td>Distance</td>
			</tr>
			<tr><td><select multiple name="where[status_=]">
						<option value=""></option>
						$status_options
					</select>
				</td>
				<td><select name="where[rating_>=]">
						<option value=""></option>
						$rating_options						
					</select></td>
				<td><select multiple name="where[nationality_=][]"> 
						<option value=""></option>
						$nationality_options
					</select></td>
				<td>[N/A]</td>
				<td><select name="where[g.approach_=]">
						<option value=""></option>
						$approach_options 
					</select>
				</td>
				<td><select name="having[distance_<=]">
						<option value=""></option>
						$distance_options 
					</select></td>
			</tr>
		</table>
		<button type="submit">Filter!</button> 
		<button type="reset" value="reset">Reset to Last</button>
		<button type="submit" id="resetDefault">Reset to Default</button>
	</form>
</div>
EOD;
		return;
	}
}

class dayte_filter extends filter {
	public function html($message) {
		global $link;
		$sql = "SELECT ID, name FROM person ORDER BY ". RecentOrderBy("person", "ID");
			$persons = mysqli_query($link, $sql) or die(mysqli_error($link));
		$sql = "SELECT ID, name FROM location ORDER BY ". RecentOrderBy("location", "ID");
			$locations = mysqli_query($link, $sql) or die(mysqli_error($link));

		// get html code for filter options
		$person_options = "";
		while ($row = mysqli_fetch_array($persons)) {
			$person_options .= "<option value='$row[ID]' " .parent::selected("where", "person_=", $row['ID']). ">$row[name]</option>";
		}

		$start_times = array("1 week"=>1, "2 weeks"=>2, "1 month"=>4, "2 months"=>4*2, "4 months"=>4*4, "6 months"=>4*6, "1 year"=>4*12);
		foreach ($start_times as $key => $value) { $start_times[$key] = 86400*7*$value;}
		$start_options_less = "";
		foreach ($start_times as $name => $time) {
			$start_options_less .= "<option value='$time' " .parent::selected("where", "start_<=", $time). ">$name ago</option>";
		}
		$start_options_more = "";
		foreach ($start_times as $name => $time) {
			$start_options_more .= "<option value='$time' " .parent::selected("where", "start_>=", $time). ">$name ago</option>";
		}

		$duration_times = array("1 hour"=> 1, "2 hours"=> 2, "4 hours"=> 4, "8 hours"=> 8, "12 hours"=> 12);
		foreach ($duration_times as $key => $value) { $duration_times[$key] = 3600*$value; }
		$duration_options_less = "";
		foreach ($duration_times as $name => $time) {
			$duration_options_less .= "<option value='$time' " .parent::selected("where", "duration_<=", $time). ">$name</option>";
		}
		$duration_options_more = "";
		foreach ($duration_times as $name => $time) {
			$duration_options_more .= "<option value='$time' " .parent::selected("where", "duration_>=", $time). ">$name</option>";
		}

		$location_options = "";
		while($row = mysqli_fetch_array($locations)) {
			$location_options .= "<option value='$row[ID]' " .parent::selected("where", "location_=", $row['ID']). ">$row[name]</option>";
		}

		$cost_filters = array(0, 100, 200, 500, 1000);
		$cost_options_less = "";
		foreach ($cost_filters as $cost) {
			$cost_options_less .= "<option value='$cost' " .parent::selected("where", "cost_<=", $cost). ">$cost</option>";
		}
		$cost_options_more = "";
		foreach ($cost_filters as $cost) {
			$cost_options_more .= "<option value='$cost' " .parent::selected("where", "cost_>=", $cost). ">$cost</option>";
		}

		$share_filters = array(0, 0.25, 0.5, 0.75, 1);
		$share_options_less = "";
		foreach ($share_filters as $share) {
			$share_options_less .= "<option value='$share' " .parent::selected("where", "share_<=", $share). ">$share</option>";
		}
		$share_options_more = "";
		foreach ($share_filters as $share) {
			$share_options_more .= "<option value='$share' " .parent::selected("where", "share_>=", $share). ">$share</option>";
		}

		echo <<<EOD
<div class="baseline" id="filter">
	$message
	<form method="POST" action="$_SERVER[PHP_SELF]">
		<table>
			<tr>
				<td>person</td>
				<td colspan="2">Start</td>
				<td colspan="2">Duration</td>
				<td>Location</td>
				<td colspan="2">Cost</td>
				<td colspan="2">Share</td>
			</tr>
			<tr>
				<td></td>
				<td>More than</td>
					<td>Less than</td>
				<td>More than</td>
					<td>Less than</td>
				<td></td>
				<td>More than</td>
					<td>Less than</td>
				<td>More than</td>
					<td>Less than</td>
			</tr>
			<tr>
				<td><select multiple name="where[person_=]">
						<option value=""></option>
						$person_options
				</td>
				<td><select name="where[start_>=]">
						<option value=""></option>
						$start_options_more						
					</select>
					</td>
					<td><select name="where[start_<=]">
							<option value=""></option>
							$start_options_less						
						</select>
					</td>
				<td><select name="where[duration_>=]"> 
						<option value=""></option>
						$duration_options_more
					</select></td>
					<td><select name="where[duration_<=]"> 
						<option value=""></option>
						$duration_options_less
						</select>
					</td>
				<td><select multiple name="where[location_=]">
						<option value=""></option>
						$location_options						
					</select></td>
				<td><select name="where[cost_>=]"> 
						<option value=""></option>
						$cost_options_more
					</select></td>
					<td><select name="where[cost_<=]"> 
						<option value=""></option>
						$cost_options_less
						</select>
					</td>
				<td><select name="where[share_>=]"> 
						<option value=""></option>
						$share_options_more
					</select></td>
					<td><select name="where[share_<=]"> 
						<option value=""></option>
						$share_options_less
						</select>
					</td>
			</tr>
		</table>
		<button type="submit">Filter!</button> 
		<button type="reset" value="reset">Reset to Last</button>
		<button type="submit" id="resetDefault">Reset to Default</button>
	</form>
</div>
EOD;
	}
}


class sorter {
	public $orderby; // sql ORDER BY category
	
	private $type; // result or column
	private $category;  // for what are you ordering? (eg. view persons, view map, etc)
	private $desc_option;

	protected $all_fields = array();
	protected $included_fields = array();
	protected $excluded_fields = array();
	// protected $cat_order_cookie = array();
	protected $desc_cookie = array();

	
	// orders @array $included_fields by POST order if set, if not, cookie value if set
	// stores POST order in cookie
	// generates ORDER BY sql clause as $orderby
	function __construct($type, $category, $all_fields, $desc_option = false) {

		$this->type = $type;
		$this->category = $category;
		$this->all_fields = $all_fields;
		$this->desc_option = $desc_option;

		if ($desc_option) {
			if (!empty($_COOKIE['sortDESC_'.$type]) && !empty(json_decode($_COOKIE['sortDESC_'.$type], true)[$category])) {
				$this->desc_cookie = json_decode($_COOKIE['sortDESC_'.$type], true)[$category];
			}
		}
		// Get saved sort order and generate SQL query for it
		if (!empty($_COOKIE['includeOrder_'.$type])) {
			$cookie_array = json_decode($_COOKIE['includeOrder_'.$type], true);
		}
		if (!empty($_POST['includeOrder_'.$type])) {
			// set cookie to store sort order
			$cookie_array[$category] = $_POST['includeOrder_'.$type];
			setcookie('includeOrder_'.$type, json_encode($cookie_array), MAX_COOKIE);
			
			$params = array();
			parse_str($_POST['includeOrder_'.$type], $params);
			$this->included_fields = $params['order'];
			$this->orderby = "";
			foreach($this->included_fields as $field) {
				$this->orderby .= " " . $field;
				if (!empty($_POST['sortDESC_'.$type]) && in_array($field, $_POST['sortDESC_'.$type])) {
					$this->orderby .= " DESC";
				}
				$this->orderby .= ",";
			}
			$this->orderby = rtrim($this->orderby, ",");
		} else if (!empty($cookie_array[$category])) {
			$cookie_parsed = array();
			parse_str($cookie_array[$category], $cookie_parsed);
			$this->included_fields = $cookie_parsed['order'];

			if ($desc_option) {
				$this->orderby = "";
				foreach($this->included_fields as $field) {
					$this->orderby .= " " . $field;
					if (in_array($field, $this->desc_cookie)) {
						$this->orderby .= " DESC";
					}
					$this->orderby .= ",";
				}
				$this->orderby = rtrim($this->orderby, ",");
			}
		} else {
			$this->orderby = " 1 ";
			$this->included_fields = ($type=="result" ? array() : $all_fields);
		}

		foreach($all_fields as $field) {
			if (!in_array($field, $this->included_fields)) {
				array_push($this->excluded_fields, $field);
			}
		}

		// set cookie to store DESC options
		if ($desc_option) {
			if (isset($_POST['sortDESC_'.$type])) {
				if (isset($_COOKIE['sortDESC_'.$type])) {
					$sortDESCArray = json_decode($_COOKIE['sortDESC_'.$type], true);
				} 
				$sortDESCArray[$category] = $_POST['sortDESC_'.$type];
				setcookie('sortDESC_'.$type, json_encode($sortDESCArray), MAX_COOKIE);
			}
		}
	return;
	}

	public function reset() {
		$this->orderby = " 1 ";
		$this->included_fields = ($this->type=="result" ? array() : $this->all_fields);
		$this->excluded_fields = ($this->type=="result" ? $this->all_fields : array());
		$this->desc_cookie = array();
		setcookie('includeOrder_'.$this->type, "", 1);
		setcookie('sortDESC_'.$this->type, "", 1);
	}

	public function js($start_hidden) {
		if($start_hidden) {
			$hidden_js = "$('div#sorter_$this->type').hide();";
		} else $hidden_js = "";

		echo <<<EOD
			$( "#included_$this->type, #excluded_$this->type" ).sortable({
				connectWith: ".connectedSortable",
				placeholder: "ui-state-highlight"
			});

			$('form').submit(function(){
				var sortedInclude = $("#included_$this->type").sortable("serialize");
				$('#includeOrder_$this->type').val(sortedInclude);
				var sortedExclude = $("#excluded_$this->type").sortable("serialize");
				$('#excludeOrder_$this->type').val(sortedExclude);
			});

			$hidden_js
			$("#sorter_$this->type").click(function(){
				$("div#sorter_$this->type").toggle(200);
			});
EOD;
		return;
	}
	public function button_sort() {
		echo "<button id='sorter_$this->type'>Show/Hide Sort Options</button>";
	}
	public function html_sort() {
		$includedList = "";
		foreach ($this->included_fields as $field) {
			$includedList .= "<li id='order_$field' class='ui-state-default'><span class='ui-icon ui-icon-arrowthick-2-n-s'></span><label>".ucfirst($field)."</label>";
			if ($this->desc_option) {
				$includedList .= "<input type='checkbox' name='sortDESC_$this->type[]' value='$field' ";
				if ((!empty($_POST['sortDESC_'.$this->type]) && in_array($field, $_POST['sortDESC_'.$this->type])) || in_array($field, $this->desc_cookie)) {
					$includedList .= " checked";
				}
				$includedList .= ">Desc";
			}
			$includedList .= "</li>\n";
		}
		$excludedList = "";
		foreach ($this->excluded_fields as $field) {
			$excludedList .= "<li id='order_$field' class='ui-state-default'><span class='ui-icon ui-icon-arrowthick-2-n-s'></span><label>".ucfirst($field)."</label>";
			if ($this->desc_option) {
				$excludedList .= "<input type='checkbox' name='sortDESC_$this->type[]' value='$field' ";

				if ((!empty($_POST['sortDESC_'.$this->type]) && in_array($field, $_POST['sortDESC_'.$this->type])) || in_array($field, $this->desc_cookie)) {
					$excludedList .= " checked";
				}
				$excludedList .= ">Desc";
			}
			$excludedList .= "</li>\n";
		}

		echo <<<EOD
<div class="column" id="sorter_$this->type">
	<form method="POST" action="$_SERVER[PHP_SELF]">
		<input type="hidden" name="includeOrder_$this->type" id="includeOrder_$this->type">
		<input type="hidden" name="excludeOrder_$this->type" id="excludeOrder_$this->type">

		<ul id="included_$this->type" class="connectedSortable">
			$includedList
		</ul>
		<ul id="excluded_$this->type" class="connectedSortable">
			$excludedList
		</ul>
		<div class="baseline">
			<button type="submit">Sort!</button>
			 <a href="$_SERVER[PHP_SELF]?reset=$this->type">Reset</a>	
		</div>
	</form>
</div>
EOD;
		return;
	}	
}
// TODO: don't have to build object - build code on page with $sorter->included_fields
class person_column_sorter extends sorter {
	private $table_result;
	private $cols_showing;

	public function build_table($table_result) {
		$this->table_result = $table_result;
		$this->cols_showing = count($this->included_fields);
	}

	public function html_table() {
		$headings = "<th></th>";
		foreach ($this->included_fields as $field) {
			$headings .= "<th><a href='view_person.php?linkOrder=$field'>".ucfirst($field)."</a></th>";
		}
		$message = "";
		if ($this->cols_showing != count($this->all_fields)) {
			$message = "Showing $this->cols_showing of ".count($this->all_fields)." columns";
		}

		$tag_selector = tag_selector();
		echo <<<EOD
		
	<form method="POST" action="process.php?field=assign_tags&append=true">
		<div class="assign_tags">
			$tag_selector
			<input type="checkbox" id="remove_tags" name="remove"> Remove
			<input type="submit" id="tags_submit" value="Assign Tags!">
		</div>
		$message 
		<table border="1">
			<tr>
				$headings
			</tr>
EOD;

		while ($row = mysqli_fetch_array($this->table_result))	{

			$col['op'] = "<tr class=' ";
				if (time() <= $row['lastPing'] + $row['pingInterval'] || time() <= $row['nextPing']) {
					$col['op'] .= "green ";
				} else if (time() <= $row['lastPing'] + 1.1*$row['pingInterval'] + 2*86400 ) {
					$col['op'] .= "yellow ";
				} else {
					$col['op'] .= "red ";
				}
				$col['op'] .= " '><td nowrap><span class='operations'>";
				$col['op'] .= "<a href='add_person.php?edit_id=$row[ID]'><img src='icon/basic/edit/Edit_24x24.png' height='24'></a>";
				$col['op'] .= "<a href='process_delete.php?field=person&id=$row[ID]&sure=0'><img src='icon/basic/delete/Delete_24x24.png' height='24'></a>";
				$col['op'] .= "<a href='add_dayte.php?person_id=$row[ID]'><img src='icon/basic/add/Add_24x24.png' height='24'></a></span>";
				$col['op'] .= "<input type='checkbox' class='assign_tags' name='person[]' value='$row[ID]'></td>";
			$col['status'] = "<td>$row[status]</td>";
			$col['name'] = "<td><a href='add_person.php?edit_id=$row[ID]'>$row[name]</a></td>";
			$col['rating'] = "<td class='numeric'>".floatval($row['rating'])."</td>";
			$col['connected'] = "<td>$row[connected]</td>";
			$col['nationality'] = "<td><img src='icon/country/32/$row[nationalityISO].png' height='32'>$row[nationality]</td>";
			$col['hometown'] = "<td>$row[hometown]</td>";
			$col['location'] = "<td><img src='icon/country/32/$row[cityISO].png' height='32'>$row[city]</td>";
			$col['met'] = "<td><img src='icon/country/32/$row[locationISO].png' height='32'>";
				$col['met'] .= "<a href='add_location.php?edit_id=$row[met_ID]' target='_blank'>$row[met]</a></td>";
			$col['approach'] = "<td>$row[approach]</td>";
			// $col['contact'] = "<td>";
			// 	if (!(is_null($row["platform"]) || is_null($row["userid"]))) {
			// 		$platform_array = explode(",", $row['platform']);
			// 		$handle_array = explode(",", $row['userid']);
			// 		foreach ($platform_array as $key => $value) {
			// 			$col['contact'] .= $platform_array[$key] . ": " . $handle_array[$key] . "<br>";
			// 		}
			// 	}
			// 	$col['contact'] .= "</td>";
			$col['contact'] = "<td>";
				if (!(is_null($row["platform"]) || is_null($row["userid"]))) {
					$platform_array = explode(",", $row['platform']);
					$handle_array = explode(",", $row['userid']);
					foreach ($platform_array as $key => $value) {
						if (in_array($value, array('Wechat', 'Facebook', 'Line', 'Skype', 'Tandem'))) {
							$col['contact'] .= "<img src='/icon/platform/32/$value.png' title='$handle_array[$key]' height='32'>";
						} else {
							$col['contact'] .= " <abbr title='$handle_array[$key]'>$value</abbr>";
						}
					}
				}
				$col['contact'] .= "</td>";
			$col['tags'] = "<td>";
				if (!is_null($row["tags"])) {
					$tags_array = explode(",", $row['tags']);
					foreach ($tags_array as $tag) {
						$col['tags'] .= "$tag, ";
					}
					$col['tags'] = rtrim($col['tags'], ", "); 
				}
				$col['tags'] .= "</td>";
			$col['distance'] = "<td class='numeric'>".round($row['distance'],1)."</td>";
			$col['lastPing'] = "<td><a href='process.php?&field=ping&edit_id=$row[ID]'><img src='icon/basic/Refresh/Refresh_32x32.png' height='32'></a>";
				$col['lastPing'] .= floor((time() - $row['lastPing'])/86400) . " days ago</td>";
			$col['nextPing'] = "<td>";
				if (!empty($row['nextPing']) && $row['nextPing'] > $row['lastPing']) {
					$col['nextPing'] .= "In ".floor(($row['nextPing'] - time()) / 86400)." days";
				}
				$col['nextPing'] .= "</td>";
			$col['pingInterval'] = "<td>" . $row['pingInterval']/86400 . " days</td>";
			$col['note'] = "<td>$row[note]</td>";
			$col['pictures'] = "<td><a href='add_picture.php?person=$row[ID]'><img src='icon/basic/Upload/Upload_32x32.png' height='32'></a> ";
				if (!is_null($row["ImageNames"])) {
					$imageArray = explode(",", $row["ImageNames"]);
					foreach ($imageArray as $imageName) {
						$imageCode .= "<a href='pictures/$imageName'><img src='pictures/$imageName' height='100'></a> ";
					}
				}
				$col['pictures'] .= "</td>";

			echo "<tr>";
			echo $col['op'];

			foreach ($this->included_fields as $field) {
				echo $col[$field] . "\n";
			}
		}
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}
}


abstract class clusterMap {

	protected function parse_js($markers_js, $minZoom, $maxZoom, $zoom) {
		$c_lat = currentLocation("lat");
		$c_lng = currentLocation("lng");

		 return <<<EOD
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDZUqVqQHRq9h2x9BMka6Mk-4Us84EAtLA" async defer></script>
<script type="text/javascript" src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
<script>	 
	$(window).load(function(){
		var marker;
		var map;
		var infoWindow = new google.maps.InfoWindow();

		function initialize() {
			var markers = [
				$markers_js
			];

			map = new google.maps.Map(document.getElementById('google-maps'), {
				minZoom: $minZoom,
				maxZoom: $maxZoom,
				zoom: $zoom,
				center: {lat: $c_lat, lng: $c_lng},
				mapTypeId: google.maps.MapTypeId.ROADMAP
			});

			var markerCluster = new MarkerClusterer(map, markers, {
				gridSize: 20,
				maxZoom: 10,
				zoomOnClick: false,
				imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
			});

			google.maps.event.addListener(markerCluster, 'clusterclick', function(cluster) {
				var markersInCluster = cluster.getMarkers();
				var popup = "";
				for (var i = 0; i < markersInCluster.length; i++) {
					if (i < 10) {
						popup += markersInCluster[i].popup + '<br>';
					} else {
						popup += "plus " + (markersInCluster.length - i) + " more";
						break;
					}
				}

				infoWindow.setContent(markersInCluster.length + " persons<br>" + popup);
				infoWindow.setPosition(cluster.getCenter());
				infoWindow.open(map);
			});

			for (var i = 0; i < markers.length; i++) {
				google.maps.event.addListener(markers[i], 'click', (function(marker) {
					return function() {
						infoWindow.setContent(this.popup);
						infoWindow.open(map, this);
					};
				})(markers[i]));
			}
		}

		$(document).ready(function() {
			initialize();
		});
	});
</script>
EOD;
}
	// function html() {
		// echo '<div class="photo-map" id="google-maps"></div>';
	// }
}

class clusterMap_person extends clusterMap {
	private $markers_js;
	private $minZoom = 2;
	private $maxZoom = 10;
	private $zoom = 7;

	function __construct($sql_where, $sql_orderby) {
		global $link;
		$sql = "SELECT g.ID, g.name, g.rating, g.nationality, c.lat, c.lng FROM person AS g
			LEFT JOIN city AS c ON g.city = c.ID
			WHERE $sql_where
			ORDER BY $sql_orderby";
		$result = mysqli_query($link, $sql) or die(mysqli_error($link));
		$total_rows = mysqli_num_rows($result);

		$this->markers_js = "";
		$row_num = 0;
		while ($row = mysqli_fetch_array($result)) {
			if (empty($row['lat']) || empty($row['lng'])) {
				++$row_num;
				continue;
			}
			$row['name'] = mysqli_escape_string($link, $row['name']);
			if (++$row_num != $total_rows) {
				$separatorComma = ",";
			} else {
				$separatorComma = "";
			}
			$this->markers_js .= <<<EOD
				new google.maps.Marker({
				position: {lat: $row[lat], lng: $row[lng]},
				map: map,
				popup: "<img src='/icon/country/16/$row[nationality].png' height='16'><a href='add_person.php?edit_id=$row[ID]' target='_blank'>$row[name]</a> $row[rating]"
			}) $separatorComma
EOD;
		}
	}
	public function js() {
		echo parent::parse_js($this->markers_js, $this->minZoom, $this->maxZoom, $this->zoom);
	}
}

class clusterMap_location extends clusterMap {
	private $markers_js;
	private $minZoom = 4;
	private $maxZoom = 18;
	private $zoom = 13;

	function __construct($sql_where, $sql_orderby) {
		global $link;
		$sql = "SELECT l.ID AS ID, l.name, c.name AS city, l.lat, l.lng, c.country FROM `location` AS l 
			LEFT JOIN city AS c ON l.city = c.ID
			ORDER BY $sql_orderby";
		$result = mysqli_query($link, $sql) or die(mysqli_error($link));
		$total_rows = mysqli_num_rows($result);

		$this->markers_js = "";
		$row_num = 0;
		while ($row = mysqli_fetch_array($result)) {
			if (empty($row['lat']) || empty($row['lng'])) {
				++$row_num;
				continue;
			}
			if (++$row_num != $total_rows) {
				$separatorComma = ",";
			} else {
				$separatorComma = "";
			}
			$this->markers_js .= <<<EOD
				new google.maps.Marker({
				position: {lat: $row[lat], lng: $row[lng]},
				map: map,
				popup: "$row[name]"
			}) $separatorComma
EOD;
		}
	}
	public function js() {
		echo parent::parse_js($this->markers_js, $this->minZoom, $this->maxZoom, $this->zoom);
	}
}

class clusterMap_dayte extends clusterMap {
	private $markers_js;
	private $minZoom = 4;
	private $maxZoom = 18;
	private $zoom = 13;

	function __construct($sql_where, $sql_orderby) {
		global $link;
		$sql = "SELECT dl.*, l.name AS name, l.lat, l.lng FROM dayte_locations AS dl
			LEFT JOIN location AS l ON dl.location = l.ID
			WHERE dl.dayte = $_GET[id]
			ORDER BY $sql_orderby";
		$result = mysqli_query($link, $sql) or die(mysqli_error($link));
		$total_rows = mysqli_num_rows($result);

		$this->markers_js = "";
		$row_num = 0;
		while ($row = mysqli_fetch_array($result)) {
			if (++$row_num != $total_rows) {
				$separatorComma = ",";
			} else {
				$separatorComma = "";
			}
			$label = $row['sequence'] + 1;
			$popup = "<b>$row[name]</b> <br>Spent: ".floatval($row['cost'])."<br>Share: ".(round($row['share'],2)*100)."%";
			$this->markers_js .= <<<EOD
				new google.maps.Marker({
				position: {lat: $row[lat], lng: $row[lng]},
				map: map,
				label: "$label",
				popup: "$popup"
			}) $separatorComma
EOD;
		}
	}
	public function js() {
		echo parent::parse_js($this->markers_js, $this->minZoom, $this->maxZoom, $this->zoom);
	}
}


?>