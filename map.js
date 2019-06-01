// Inputs:
// 		EditMode,
// 		PlaceId 
// must be defined in php file using this script

// Outputs:
// 		getElementByIdId('place_name')
// 		getElementByIdId('googleID')
// 		getElementByIdId('lat')
// 		getElementByIdId('long')
// if they exist in source page

function initMap() {
	var lat, lng;
	lat = $('option:selected').data('lat');
	lng = $('option:selected').data('lng');

	var map = new google.maps.Map(document.getElementById('map'), {
		center: {lat: lat, lng: lng},
		zoom: 12
	});

	// zoom to selected city
	$('select#city').change(function() {   
		lat = $('option:selected').data('lat');
		lng = $('option:selected').data('lng');
		map.panTo({lat: lat, lng: lng});
		map.setZoom(12);
	});

	// zoom to and get info if found through search bar
	var input = document.getElementById('pac-input');
	var autocomplete = new google.maps.places.Autocomplete(input);
	autocomplete.bindTo('bounds', map);
	map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

	var infowindow = new google.maps.InfoWindow();
	var marker = new google.maps.Marker({
		map: map
	});
	marker.addListener('click', function() {
		infowindow.open(map, marker);
	});

	autocomplete.addListener('place_changed', function () {
		var place = autocomplete.getPlace();
		zoom(place);
		display(place);
	});

	// get info if clicked on
	google.maps.event.addListener(map, "click", function (event) {
		var service = new google.maps.places.PlacesService(map);
		if (event.placeId) {
			service.getDetails({placeId: event.placeId}, function(place, status) {
				if (status === google.maps.places.PlacesServiceStatus.OK) {
					display(place);
				}
			});
		}
	});

	// set map in place if in Edit mode
	if (EditMode) {
		var service = new google.maps.places.PlacesService(map);
		service.getDetails({placeId: EditId}, function(place, status) {
			if (status === google.maps.places.PlacesServiceStatus.OK) {
				zoom(place);
				display(place);
			}
		});
	}

	function zoom (place) {
		if (!place.geometry) {
			return;
		}
		if (place.geometry.viewport) {
			map.fitBounds(place.geometry.viewport);
		} else {
			map.setCenter(place.geometry.location);
			map.setZoom(15);
		}
	}

	function display (place) {
		infowindow.close();
		marker.setPlace({
			placeId: place.place_id,
			location: place.geometry.location
		});
		if (document.getElementById('place_name')) 	{document.getElementById('place_name').value = place.name; }
		if (document.getElementById('googleID')) 	{document.getElementById('googleID').value = place.place_id; }
		if (document.getElementById('lat')) 		{document.getElementById('lat').value = place.geometry.location.lat(); }
		if (document.getElementById('lng'))			{document.getElementById('lng').value = place.geometry.location.lng(); }
	}
}