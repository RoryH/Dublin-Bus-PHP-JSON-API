<?php
require_once('lib/nusoap/nusoap.php');
include 'utils.php';

class dbapi {
	static $client;

	public function init() {
		self::$client = new nusoap_client('http://rtpi.dublinbus.ie/DublinBusRTPIService.asmx?WSDL', true,'', '', '', '');
		$err = self::$client->getError();
		if ($err) {
			header("HTTP/1.1 502 Bad Gateway", true, 502);
			header("Content-Type: application/json",true);
			die (json_encode(array('error' => "Unknown error occurred initialising API")));
		}
	}
	
	private function testForError($res) {
		if (isset($res['faultcode']) && isset($res['faultstring'])) {
			header("HTTP/1.1 500 Internal Server Error", true, 500);
			header("Content-Type: application/json",true);
			header("Content-Type: application/json",true);
			echo (json_encode(array('error' => $res['faultstring'])));
			return false;
		}
		return true;
	}

	public function getStops($route) {
		$result = self::$client->call('GetStopDataByRoute', array('route' => $route));
		
		if (self::testForError($result)) {
			header("Content-Type: application/json",true);
			echo json_encode(array('route' => $route, 'stops' =>$result['GetStopDataByRouteResult']['diffgram']['StopDataByRoute']));
		}
	}
	
	public function getStopTimes($stop) {
		$result = self::$client->call('GetRealTimeStopData', array('stopId' => $stop, 'forceRefresh' => 1));
		
		if (self::testForError($result)) {
			header("Content-Type: application/json",true);
			if (isset($result['GetRealTimeStopDataResult']['diffgram']['DocumentElement'])) {
				echo json_encode(array('stopId' => $stop, 'departures' => $result['GetRealTimeStopDataResult']['diffgram']['DocumentElement']['StopData']));
			} else {
				echo json_encode(array('stopId' => $stop, 'departures' => array()));
			}
		}
	}

	public function badRequest($msg) {
		header("HTTP/1.1 400 Bad Request", true, 400);
		header("Content-Type: application/json",true);
		die (json_encode(array('error' => $msg)));
	}
}


dbapi::init();

if (!isset($_GET['o'])) {
	dbapi::badRequest("No method specified in parameter 'o'");
} else {
	switch ($_GET['o']) {
		case "getStops":
			if (!isset($_GET['route'])) {
				dbapi::badRequest("Missing parameter 'route'");
			} else {
				dbapi::getStops($_GET['route']);
			}
			break;
		case "getStopTimes":
			if (!isset($_GET['stop'])) {
				dbapi::badRequest("Missing parameter 'stop'");
			} else {
				dbapi::getStopTimes($_GET['stop']);
			}
			break;
	}
}
 ?>
