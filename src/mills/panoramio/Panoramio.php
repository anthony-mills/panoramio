<?php
/**
 * Simple class for retreiving images from the Panoramio API
 * 
 * @package Panoramio Wrapper Class
 * @author Anthony Mills
 * @copyright 2012 Anthony Mills ( http://anthony-mills.com )
 * @license GPL V3.0
 * @version 0.2
 */
 namespace mills\panoramio;

 class panoramioAPI 
 {
	// Supplied Cordinates to search near for images
	var $_requiredLatitude = '-33.8846';
	var $_requiredLongitude = '151.2181';	
	
	// The outer limits of the box we would like to search for images within
	var $_requiredMinLatitude = 0;
	var $_requiredMinLongitude = 0;
	var $_requiredMaxLatitude = 0;
	var $_requiredMaxLongitude = 0;
	
	// The distance in kilometers from the position you would like to search for images
	var $_locationDistance = 20;
		
	// The default type of Panoramio image set to retrieve
	var $_panoramioSet = 'public';
	var $_panoramioImageNumber = 20;
	var $_panoramioStartingImage = 0;	
	
	// The size for the return images
	var $_panoramioImageSize = 'medium';
	
	// Ordering style of the images
	var $_panoramioOrdering = 'upload_date';

	// Specifics for communication with the actual URL itself
	var $_requestUserAgent = 'info@mypanoramiobot.com';
	var $_requestHeaders = array('Panoramio-Client-Version: 0.1');
	var $_apiUrl = 'http://www.panoramio.com/map/get_panoramas.php';

	/**
	 * Set the location via longitude and latitude of where you would like to get images near
	 * 
	 * @param string $placeLatitude
	 * @param string $placeLongitude
	 */
	public function setRequiredLocation($placeLatitude, $placeLongitude,$locationDistance)
	{
		$this->_requiredLatitude = $placeLatitude;
		$this->_requiredLongitude = $placeLongitude;
		$this->_locationDistance = $locationDistance;
	}

	/**
	* Set the location box via min/max longitude and latitude of where you would like to get images.
	*
	* @param string $requiredMinLatitude
	* @param string $requiredMaxLatitude
	* @param string $requiredMinLongitude
	* @param string $requiredMaxLongitude
	*/
	public function setBoxLocation($requiredMinLatitude, $requiredMaxLatitude, $requiredMinLongitude, $requiredMaxLongitude) {
		// The outer limits of the box we would like to search for images within
		$this->_requiredMinLatitude = $requiredMinLatitude;
		$this->_requiredMaxLatitude = $requiredMaxLatitude;
		$this->_requiredMinLongitude = $requiredMinLongitude;
		$this->_requiredMaxLongitude = $requiredMaxLongitude;
	}

	/**
	 * Set the ordering of images returned from Pamoramio, class default is upload_date but 
	 * can also be set to "popularity"
	 * 
	 * @param string $imageOrder
	 */
	public function orderImages($imageOrder) 
	{
		$this->_panoramioOrdering = $imageOrder;
	}
	
	/**
	 * Set the tyep of set you would like to retrieve this can be either:
	 * 
	 * - public (popular photos)
	 * - full (all photos)
	 * - the user ID of a panoramio user whose photos you would like returned
	 * 
	 * @param string $panoramioSet
	 */
	public function setPanoramioSet($panoramioSet)
	{
		$this->_panoramioSet = $panoramioSet;	
	}
	
	/**
	 * Set a size for the images returned from the Panoramio API
	 * Valid size API options are:
	 * 		original
	 * 		thumbnail
	 * 		mini square
	 * 		square
	 * 		small
	 * 		medium (default)
	 * 
	 * @param string $panoramioSize
	 */	
	 public function setPanoramioSize($panoramioSize)
	 {
	 	$this->_panoramioImageSize = $panoramioSize;
	 }
	
	/**
	 * Get a set of images from the Panoramio API
	 * 
	 * @param int $imageNumber
	 * @return object 
	 */	
	 public function getPanoramioImages($imageNumber = 5, $calculateBox = true, $startingImage = 0 ) 
	 {
		if (!empty($startingImage)) {
			$this->_panoramioStartingImage = $startingImage;
		}
	 	if (!empty($imageNumber)) {
	 		$this->_panoramioImageNumber = $imageNumber;
	 	} 
		if ($calculateBox){
			$this->_calculateBoundingBox();
		}
		$apiRequest = $this->_buildRequest();
		$apiResponse = $this->_processRequest($apiRequest);
		
		if (!empty($apiResponse)) {
			return $apiResponse->photos;
		}
	}
	
	/** 
	 * Calculate the bounding box for a location via its latitude, longitude
	 */
	 protected function _calculateBoundingBox()
	 {
		$minLocation = $this->_calculateNewPosition($this->_requiredLatitude, $this->_requiredLongitude, 225);
		$this->_requiredMinLatitude = $minLocation['latitude'];
		$this->_requiredMinLongitude = $minLocation['longitude'];
		 
		$maxLocation = $this->_calculateNewPosition($this->_requiredLatitude, $this->_requiredLongitude, 45);
		$this->_requiredMaxLatitude = $maxLocation['latitude'];
		$this->_requiredMaxLongitude = $maxLocation['longitude'];
	 }
	 
	 /**
	  * Calculate the position of a new location given a longitude and latitude and a bearing
	  * 
	  * @param int $placeLatitude
	  * @param int $placeLongitude
	  * @param int $directionBearing
	  * 
	  * @return array $newLocation
	  */
	 protected function _calculateNewPosition($placeLatitude, $placeLongitude, $directionBearing)
	 {
	 	$earthRadius = 6371; // Radius of the earth in kilometers
		$newLocation = array();
		$newLocation['latitude'] = rad2deg(asin(sin(deg2rad($placeLatitude)) * cos($this->_locationDistance / $earthRadius) + cos(deg2rad($placeLatitude)) * sin($this->_locationDistance / $earthRadius) * cos(deg2rad($directionBearing))));
		$newLocation['longitude'] = rad2deg(deg2rad($placeLongitude) + atan2(sin(deg2rad($directionBearing)) * sin($this->_locationDistance / $earthRadius) * cos(deg2rad($placeLatitude)), cos($this->_locationDistance / $earthRadius) - sin(deg2rad($placeLatitude)) * sin(deg2rad($newLocation['latitude']))));	 
		
		return 	$newLocation;
	 }
	 
	 /**
	  * Assemble the request data in preperation for passing to the API
	  * 
	  * @return string $apiRequest
	  */
	  protected function _buildRequest() {
		$apiRequest = $this->_apiUrl . '?set=' . $this->_panoramioSet .
					'&from=' . $this->_panoramioStartingImage .
					'&to=' . ($this->_panoramioStartingImage + $this->_panoramioImageNumber) .
					'&minx=' . $this->_requiredMinLongitude  . '&miny=' . $this->_requiredMinLatitude. 
					'&maxx=' . $this->_requiredMaxLongitude . '&maxy=' . $this->_requiredMaxLatitude . 
					'&size=' . $this->_panoramioImageSize . '&order=' . $this->_panoramioOrdering;
		return $apiRequest;
	  }
	  
	 /**
	  * Send a formatted string of data as a GET to the API and collect the response
	  * 
	  * @param string $apiData
	  * @return array $apiResponse
	  */
	 protected function _processRequest($apiRequest)
	 {
		$ch = curl_init($apiRequest);	 	
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_requestUserAgent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_requestHeaders);
		
		$apiResponse = curl_exec($ch);
		$responseInformation = curl_getinfo($ch);
		curl_close($ch);
		
		if( intval( $responseInformation['http_code'] ) == 200 ) {
			return json_decode($apiResponse);	
		}	
	 }
	
 }
