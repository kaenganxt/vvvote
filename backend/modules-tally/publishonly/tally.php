<?php

/**
 * 
 * @author r
 * error no starts at 1100
 */

/**
 * return 404 if called directly
 */
if(count(get_included_files()) < 2) {
	header('HTTP/1.0 404 Not Found');
	echo "<h1>404 Not Found</h1>";
	echo "The page that you have requested could not be found.";
	exit;
}

chdir(__DIR__); require_once './../../tools/crypt.php';
chdir(__DIR__); require_once './../../tools/exception.php';
chdir(__DIR__); require_once './../../root-classes/tally.php';
chdir(__DIR__); require_once './dbPublishOnlyTally.php';

/**
 * Class that just collects and publishes the votes
 * errorno starts at 2000
 * @author r
 *
 */
class PublishOnlyTally extends Tally {
	const name = "publishOnly";
	
	var $db;
	var $crypt;
	var $blinder;
	function __construct($dbInfo, Crypt $crypt, Blinder $blinder_) {
		$this->db = new DbPublishOnlyTally($dbInfo);
		$this->crypt = $crypt;
		$this->blinder = $blinder_;
	}
	
	function isFirstVote($electionId, $votingno) { // TODO make sure that this is called only after the sigs have been checked
		return $this->db->isFirstVote($electionId, $votingno);
	}
	
	
	function sigsOk($vote) {
		// check the sig of the voter on the vote
		$votersigOk = $this->crypt->verifyVoterSig($vote);
		if (! ($votersigOk === true) ) return false;
		// check the sigs of the permission servers
		$permissionSigsOk = $this->blinder->verifyPermission($vote);
		
		return  ($votersigOk && $permissionSigsOk);
	}
	
	/**
	 * 
	 * return false if storing of the vote failed
	 * @param unknown $vote
	 */
	function store($electionId, $votingno, $vote, $voterreq) {
		return $this->db->storeVote($electionId, $votingno, $voterreq);
	}
	
	/*
	 function decrypVoterReq($voterReq) {
		$encryptedkey = $voterReq['enckey'];
		$encmethod = $voterReq['encType'];
		$hmacmethos = $voterReq['hmacType'];
		$hmacEncKey = $voterReq['hmacKey'];
		$hmacKey = $this->crypt->decrypt($hmacEncKey);
		$ciphered = $voterReq['ciphered'];
		// TODO maybe you want message integrity check (if you think encryption CBC-AES is not enough to ensure that no one can alter it in a certain way) add another key and calculate a HMAC
		$decryptedkey = $this->crypt->decrypt($encryptedkey); // TODO may be you want deffie-hellman key exchange here in order to provide perfect forward security. But if you plan to publish the votes anyway, it is not needed. 
		switch ($method) {
			case 'aes160':
				$engine = new Crypt_AES(CRYPT_AES_MODE_CBC);
			    $engine->setKey($decryptedkey);
				$decryptedVoteStr = $engine->decrypt($ciphered);
			break;
			
			default:
				WrongRequestException::throwException(1102, 'tally-decryptVoterReq: cipher not supported', "requested cipher: $method, supported aes160 only");
			break;
		}
		$decryptedVote = json_decode($decryptedVoteStr);
		if ($decryptedVote == null) { // json could not be decoded
			WrongRequestException::throwException(1103, 'tally-decrypt: Error while decoding JSON request', $req);
		}
		return $decryptedVote; 
	}
	*/
	
	function storeVoteEvent($voterReq) {
		// $vote = $this->decrypVoterReq($voterReq);
		// check if the voting is open for the given electionId (period in time)
		try {
			$electionId = $voterReq['permission']['signed']['electionId'];
			$votingno   = $voterReq['permission']['signed']['votingno'];
			$vote       = $voterReq['vote']['vote'];
		} catch (OutOfBoundsException $e) {
			WrongRequestException::throwException(110201, 'The request ist missing >electionId< and/or >votingno<', "complete request: " . var_export($voterReq, true));
		} catch (OutOfRangeException $e) {
			WrongRequestException::throwException(1103, 'The request ist missing >electionId< and/or >votingno<', "complete request: " . var_export($voterReq, true));
		}
		
		$isVotingPhase = $this->blinder->auth->checkphase('voting'); // throws a WrongRequestException if not in voting phase
		if ($isVotingPhase !== true) WrongRequestException::throwException(9, 'Not in voting phase', '');
		
		$isfirstv = $this->isFirstVote($electionId, $votingno);
		if (! $isfirstv) {
			WrongRequestException::throwException(1102, 'For this election, a vote from you is already stored', "Election: $electionId, Voting number $votingno");
		}
		try {
			$ok = $this->sigsOk($voterReq);
			if ($ok) {
				$this->store($electionId, $votingno, $vote, $voterReq);
			} else WrongRequestException::throwException(1104, 'Signature verification failed.', ''); ;
		} catch (Exception $e) {
			WrongRequestException::throwException(1104, 'Signature verification failed.', "details: " . $e->__toString() ); ;
		}
		// sign the vote and send it back
		global $tServerKeys, $tserverkey;
		$myKey = new Crypt($tServerKeys, $tserverkey);
		$sig = $myKey->JwsSign($voterReq);
		return array('cmd' => 'saveYourCountedVote', 'sig' => $sig);
	}

	function getAllVotesEvent($voterReq) {
		// TODO check if client is allowed to see the election result (e.g. was allowed to vote / did vote himself)
		
		// check if voting phase has ended
		$isGetResultPhase = $this->blinder->auth->checkPhase('getResult'); // throws an error if not in the phase
		if ($isGetResultPhase !== true) WrongRequestException::throwException(9, 'Not in phase of getting the result', '');

		// TODO check in election config database if only election admin can see all votes
		$allvotes = $this->db->getVotes($voterReq['electionId']);
		// $result = $this->db->getResult($voterReq['electionId']);
		/* TODO move this to a new function getResult / EndElection
		foreach ($allvotes as $vote) {
			$this->sigsOk($vote);
		}
		*/
//		$blindedInfos = $this->election->getBlindedInfos(); // infos from blinding module 
		$ret = array ('data' => array('allVotes' => $allvotes),  
				      'cmd' => 'verifyCountVotes');
		return $ret;
	}
	
	function handleTallyReq($voterReq) {
			$result = array();
			//	print "voterReq\n";
			//	var_export($voterReq);
			// $this.verifysyntax($result);
			switch ($voterReq['cmd']) {
				case 'storeVote':
					$result = $this->storeVoteEvent($voterReq);
					break;
				case 'getAllVotes':
					$result = $this->getAllVotesEvent($voterReq);
					break;
				default:
					WrongRequestException::throwException(1101, 'Error unknown tally command (accepting "storeVote" and "getAllVotes" only)', $voterReq);
					break;
			}
		return $result;
	
	}
	
}