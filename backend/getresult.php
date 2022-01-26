<?php
/**
 * This class handles the XmlHttp-messages between the tally server and the voter
 * getAllVotes
 * ErrorNo start at 7200
 */

chdir(__DIR__); require_once './connectioncheck.php';  // answers if &connectioncheck is part of the URL and exists

chdir(__DIR__); require_once './tools/exception.php';
chdir(__DIR__); require_once './tools/loadmodules.php';
chdir(__DIR__); require_once './tools/getcmd.php';

header('Access-Control-Allow-Origin: *', true); // this allows any cross-site scripting
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

$HTTP_RAW_POST_DATA = file_get_contents('php://input'); // read the post data, works in php 7 without muddling in php.ini

if ($HTTP_RAW_POST_DATA !== false) {
	$electionIdPlace = function ($a) {
		if (! isset($a['electionId'])) WrongRequestException::throwException(7200, 'Election id missing in client request'	, $httpRawPostData);
		return      $a['electionId'];
	};
	try {
		$data = getData($HTTP_RAW_POST_DATA); // throws an error if this command is not there
		$el = loadElectionModules($HTTP_RAW_POST_DATA, $electionIdPlace);
		$result = $el->tally->handleTallyReq($data);
	} catch (ElectionServerException $e) {
		$result = $e->makeServerAnswer();
	}
	print json_encode($result);
}

?>