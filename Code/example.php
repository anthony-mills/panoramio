<?php
require('Panoramio.php');

$panoramioClass = new panoramioAPI();
$localImages = $panoramioClass->getPanoramioImages();

if (!empty($localImages)) {
	$imageTemplate = file_get_contents('show_image.phtml');
	foreach($localImages as $localImage) {
		$imageDisplay = str_replace('{image_url}', $localImage->photo_file_url, $imageTemplate);
		$imageDisplay = str_replace('{photo_url}', $localImage->photo_url, $imageDisplay);		
		$imageDisplay = str_replace('{photo_title}', $localImage->photo_title, $imageDisplay);			
		echo $imageDisplay;	
	}
}
?>