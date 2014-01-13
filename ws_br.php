<?php

	require_once("decodePolylineToArray.php");

	// G for Google API, M for MapQuest
	$mode="M";
	$filename="B3";

	echo "Starting process: reading records from ".$filename.".csv and using ".$mode." API ...";
	flush();

	$fr = fopen("./in/".$filename.".csv", "r");
	$fw = fopen("./out/".$filename.".csv", 'w');

	while (!feof($fr) ) {
		$line_of_text = fgetcsv($fr, 1024, ';');

		if ($mode =="G")
		{
			// Limits to 2,500 per day
			// Can't use the result anywhere else but a Google Maps app
			// Can't store the results
			$url = "http://maps.googleapis.com/maps/api/directions/json?origin=".$line_of_text[2].",".$line_of_text[1]."&destination=".$line_of_text[4].",".$line_of_text[3]."&sensor=false";
		}
		else
		{
			// By experience, it seems that batches close to 500 records have issues (experienced 3 times today)
			// => recommended batch size is 400
			// http://open.mapquestapi.com/directions/#advancedparameters
			// Note from the MapQuest Directions API page: http://developer.mapquest.com/web/products/open/directions-service 
			// If your application will get heavy usage, please let us know by sending us an email at open@mapquest.com. Please include the estimate of your expected usage so that we will be aware and accommodate the extra traffic.
			$url = "http://open.mapquestapi.com/directions/v1/route?outFormat=json&from=".$line_of_text[2].",".$line_of_text[1]."&to=".$line_of_text[4].",".$line_of_text[3]."&shapeFormat=cmp&generalize=0&routeType=bicycle&key=Fmjtd|luuanuuyll%2C7s%3Do5-96bx9r";
		}
		echo $url."\n";

		$contents = file_get_contents($url);

		$json = json_decode($contents,true);

		$line_out = array($line_of_text[0]);
		$route_steps='';
		$route_steps_counter=0;

		if ($mode=="G")
		{
			$route_steps_length=count($json["routes"][0]["legs"][0]["steps"]);
		
			foreach ($json["routes"][0]["legs"][0]["steps"] as $r)
			{
				$route_steps_counter++;
				$single_step = '';
			
				foreach(decodePolylineToArray($r["polyline"]["points"]) as $p)
				{
					$single_step .= $p[1]." ".$p[0].",";
				}
				$route_steps .= $single_step;
			}
		}
		else
		{
			// The list of long/lat for the whole route is encoded in json.route.shape.shapePoints
			foreach(decodePolylineToArray($json["route"]["shape"]["shapePoints"]) as $c)
			{
				$route_steps_counter++;
				$route_steps .= $c[1]." ".$c[0].",";
			}
		}
		// Removing the trainling comma
		$route_steps = substr($route_steps, 0, -1);

		//echo var_dump($route_steps);
		$line_out[1]=$route_steps;
		fputs($fw, implode($line_out, ';')."\n");
	}
	fclose($fr);

	fclose($fw);

	echo "Finished process: ".$route_steps_counter." steps processed.";
?>
