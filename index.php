<?php
//phpinfo();


$host = 'localhost';
$database   = 'postgres';
$username = 'postgres';
$password = 'password';

require "postgresdb.php";
$db = new PostgresDb($database, $host, $username, $password);

//$select = $db->get('track_points');

/*	Loop through all entries and fetch reverse-geocode lookup from remote URL in JSON format. Grab the state from the return, and load into the DB.

foreach($select AS $item){
     $geocode = json_decode(file_get_contents("http://data.fcc.gov/api/block/find?format=json&latitude=".$item['lat']."&longitude=".$item['lon']."&showall=true"));
     $state = $geocode->State->code;
     echo $state;
     $db->where('id', $item['id'])->update('track_points',array('state' => $state));
}
*/




// Get disctinct states in dataset
echo "<h2>Get distinct states</h2>";
$select = $db->rawQuery("SELECT DISTINCT state FROM track_points");

foreach($select AS $item){
	echo $item['state'];
	echo "<br>";
}





// Get total time for each state
echo "<h2>Get total time for each state</h2>";
$statearray = array('NC', 'VA', 'OH', 'WV', 'MD', 'KY');
foreach($statearray AS $state){
	$lowtimedb = $db->orderBy('time','ASC')->where('state', $state)->get('track_points', 1);	
	$hightimedb = $db->orderBy('time','DESC')->where('state', $state)->get('track_points', 1);	
	$lowtime =  new DateTime($lowtimedb[0]['time']);
	$hightime =  new DateTime($hightimedb[0]['time']);
	echo $hightimedb[0]['state']." - ";
	$totaltime = date_diff($hightime, $lowtime);
	echo $totaltime->format('%M months, %d days, %h hours, %m minutes, %s seconds');
	echo "<br>";
}




// Get time when state border was crossed between two points
echo "<h2>Get time when state border was crossed</h2>";
$select = $db->orderBy('time','ASC')->get('track_points');

$state1 = array();
$state2 = array();
$i=0;
foreach($select AS $item){
	if($i % 2 == 0){ 
		$state1['state'] = $item['state'];
		$state1['time'] = $item['time'];
	}else{ 
		$state2['state'] = $item['state'];
		$state2['time'] = $item['time'];
	}
	if(isset($state1['state']) && isset($state2['state'])){
		if($state1['state'] != $state2['state']){
			echo $item['time'];	
			echo "<br>";
			if(new DateTime($state1['time']) > new DateTime($state2['time'])){
				echo $state1['state']." -> ".$state2['state'];
			}else{
				echo $state2['state']." -> ".$state1['state'];
			}
			echo "<br>";
			echo "<br>";
		}
	}
	
	$i++;
}

echo "<h2>Get total distance driven in each state</h2>";
function distance($lat1, $lon1, $lat2, $lon2, $unit) {

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
    return ($miles * 1.609344);
  } else if ($unit == "N") {
      return ($miles * 0.8684);
    } else {
        return $miles;
      }
}
$c=1;
$statearray = array('NC', 'VA', 'OH', 'WV', 'MD', 'KY');
foreach($statearray AS $state){
	$select = $db->orderBy('time','ASC')->where('state', $state)->get('track_points');
	$totalmiles = 0;
	for($i=0; $i<count($select)-1; $i+=2){
		$distance = distance($select[$i]['lat'], $select[$i]['lon'], $select[$i+1]['lat'], $select[$i+1]['lon']);
		$totalmiles = round($distance, 2) + $totalmiles;

	}
	echo $state." ".$totalmiles." total miles driven.<br>";
}