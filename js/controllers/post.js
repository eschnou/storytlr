/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 *  JAVASCRIPT FOR POSTING
 */
 
 function submitFormPost() {
		var type = $('type').value;
		
		if (type == 'image' || type == 'audio') {
			return $('formPost').submit();
		}
		
        form = new ValidateForm($('formPost'),"admin/post/verify");
        form.errorElem = $('error_messages');
        form.successCallback = function() {callbackPostSuccess();};
        return form.submit();
}

function callbackPostSuccess() {
	var form = 'formPost';
	$(form).submit();
}

function onDateChange() {
	new CalendarDateSelect($('date'), {
		popup:'force', 
		hidden:true, 
		popup_by:$('date_text'), 
		time:true, 
		minute_interval: 1,
		after_close: onDateChanged
	});
}

function onLocationChange() {
	$('location_summary').hide();
	$('location_selector').show();
}

function onDateChanged() {
	var date = $('date').value;
	if (date != '') {
		$('date_type').value = 'other';
		$('date_text').innerHTML = date;
	} 
}


var map = null;
var geocoder = null;
var marker = null;

var deliciousIcon = null;
var diggIcon = null;
var facebookIcon = null;
var flickrIcon = null;
var googlereaderIcon = null;
var laconicaIcon = null;
var lastfmIcon = null;
var picasaIcon = null;
var qikIcon = null;
var rssIcon = null;
var seesmicIcon = null;
var stuffpressIcon = null;
var tumblrIcon = null;
var twitterIcon = null;
var twitpicIcon = null;
var vimeoIcon = null;
var youtubeIcon = null;

function initMaps() {
  	if (GBrowserIsCompatible()) {
        
        map = new GMap2(document.getElementById("map_canvas"));
        geocoder = new GClientGeocoder();
        
        map.setCenter(new GLatLng(0,0),0);
         			  	
	  	map.setUIToDefault();
        map.setMapType(G_PHYSICAL_MAP );
        map.setZoom(1);
        
        // marker icons
        // create first, rest will be based on this
        deliciousIcon = new GIcon(G_DEFAULT_ICON);
        deliciousIcon.image = "images/delicious_m.png";
    	deliciousIcon.shadow = 'images/marker_s.png';
    	deliciousIcon.iconSize = new GSize(38, 38);
    	deliciousIcon.shadowSize = new GSize(57,38);
    	deliciousIcon.iconAnchor = new GPoint(19,38);
    	deliciousIcon.infoWindowAnchor = new GPoint(0,100);
    	deliciousIcon.imageMap = [20,0,22,1,24,2,25,3,26,4,27,5,28,6,28,7,29,8,30,9,30,10,30,11,30,12,30,13,31,14,31,15,30,16,30,17,30,18,30,19,30,20,29,21,29,22,28,23,27,24,26,25,25,26,24,27,22,28,20,29,19,30,18,31,18,32,18,33,17,34,17,35,17,36,17,37,14,37,14,36,14,35,14,34,13,33,13,32,13,31,12,30,11,29,9,28,7,27,6,26,5,25,4,24,3,23,3,22,2,21,2,20,1,19,1,18,1,17,1,16,1,15,1,14,1,13,1,12,1,11,1,10,2,9,2,8,3,7,4,6,4,5,5,4,6,3,7,2,9,1,12,0];
    	
	  	diggIcon = new GIcon(deliciousIcon);
  		facebookIcon = new GIcon(deliciousIcon);
  		flickrIcon = new GIcon(deliciousIcon);
  		googlereaderIcon = new GIcon(deliciousIcon);
  		laconicaIcon = new GIcon(deliciousIcon);
  		lastfmIcon = new GIcon(deliciousIcon);
  		picasaIcon = new GIcon(deliciousIcon);
  		qikIcon = new GIcon(deliciousIcon);
  		rssIcon = new GIcon(deliciousIcon);
  		seesmicIcon = new GIcon(deliciousIcon);
  		stuffpressIcon = new GIcon(deliciousIcon);
  		tumblrIcon = new GIcon(deliciousIcon);
  		twitterIcon = new GIcon(deliciousIcon);
  		twitpicIcon = new GIcon(deliciousIcon);
  		vimeoIcon = new GIcon(deliciousIcon);
  		youtubeIcon = new GIcon(deliciousIcon);
    	
		diggIcon.image = "images/digg_m.png";
    	facebookIcon.image = "images/facebook_m.png";
  		flickrIcon.image = "images/flickr_m.png";
  		googlereaderIcon.image = "images/googlereader_m.png";
  		laconicaIcon.image = "images/delicious_m.png";
  		lastfmIcon.image = "images/lastfm_m.png";
  		picasaIcon.image = "images/picasa_m.png";
  		qikIcon.image = "images/qik_m.png";
  		rssIcon.image = "images/rss_m.png";
  		seesmicIcon.image = "images/seesmic_m.png";
  		stuffpressIcon.image = "images/storytlr_m.png";
  		tumblrIcon.image = "images/tumblr_m.png";
  		twitterIcon.image = "images/twitter_m.png";
  		twitpicIcon.image = "images/twitpic_m.png";
  		vimeoIcon.image = "images/vimeo_m.png";
  		youtubeIcon.image = "images/youtube_m.png";
  		
        
	 }
}

function showAddress(address) {
	geocoder.getLatLng(
	    	address,
	    	function(point) {
	      		if (!point) {
	        		alert(address + " not found");
	      		} else {
	        		if (marker) map.removeOverlay(marker);
	      			map.setCenter(point, 13);
	        		marker = new GMarker(point, {draggable: true, icon: stuffpressIcon});
	        		map.addOverlay(marker);
	        		$('map_marker').setStyle({ display: 'block' }); 
					$('latitude').value = point.lat();
	        		$('longitude').value = point.lng();	
	        		GEvent.addListener(marker, "dragend", function () {
	        			var point = marker.getLatLng();
	        			$('latitude').value = point.lat();
		        		$('longitude').value = point.lng();	
	        		})
	      		}
	    	}
	  	);
}

function clearlocation() {
	$('map_marker').setStyle({ display: 'none' }); 
	map.removeOverlay(marker);
	$('latitude').value = null;
	$('longitude').value = null;
}

function initMarker() {
	if (marker) map.removeOverlay(marker);
	var point = new GLatLng($('latitude').value, $('longitude').value);
	marker = new GMarker(point, {draggable: true});
	map.setCenter(point, 13);
	map.addOverlay(marker);

	GEvent.addListener(marker, "dragend", function () {
		var point = marker.getLatLng();
		$('latitude').value = point.lat();
		$('longitude').value = point.lng();	
	})
}
