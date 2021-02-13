view this document showing the included diagrams: https://stackedit.io/viewer#!url=https://raw.githubusercontent.com/pfefffer/vvvote/master/doc/protocol/protocol-format-definition.md
# Description of the JSON Format Used in VVVote Method to Blind the Voter

# Table of Content

[TOC]

# Create a New Election

	POST /backend/newelection.php

authModule can be "sharedPassw" or "oAuth2"
## Example for "sharedPassw"


	{
		"auth": "sharedPassw",
		"authData": {
			"RegistrationStartDate": "2014-01-27T21:20:00Z",
			"RegistrationEndDate": "2030-10-10T21:20:00Z",
			"VotingStart": "2020-10-15T15:51:37.236Z",
			"VotingEnd": "2020-10-15T18:15:37.236Z",
			"DelayUntil": ["Thu, 15 Oct 2020 15:51:37 GMT", "2020-10-15T15:56:37.236Z", "2020-10-15T16:01:37.236Z", "2020-10-15T16:06:37.236Z", "2020-10-15T16:11:37.236Z", "2020-10-15T16:16:37.236Z", "2020-10-15T16:21:37.236Z", "2020-10-15T16:26:37.236Z", "2020-10-15T16:31:37.236Z", "2020-10-15T16:36:37.236Z", "2020-10-15T16:41:37.236Z", "2020-10-15T16:46:37.236Z", "2020-10-15T16:51:37.236Z", "2020-10-15T16:56:37.236Z", "2020-10-15T17:01:37.236Z", "2020-10-15T17:06:37.236Z", "2020-10-15T17:11:37.236Z", "2020-10-15T17:16:37.236Z", "2020-10-15T17:21:37.236Z", "2020-10-15T17:26:37.236Z", "2020-10-15T17:31:37.236Z", "2020-10-15T17:36:37.236Z", "2020-10-15T17:41:37.236Z", "2020-10-15T17:46:37.236Z", "2020-10-15T17:51:37.236Z", "2020-10-15T17:56:37.236Z", "2020-10-15T18:01:37.236Z", "2020-10-15T18:06:37.236Z", "2020-10-15T18:11:37.236Z", "2020-10-15T18:16:37.236Z"],
			"sharedPassw": "voting secret"
		},
		"tally": "publishOnly",
		"questions": [{
				"questionID": 1,
				"questionWording": "Enter the time of day the basketball court should be closed."
			}
		],
	"electionId": "Clsoing Time of Basketball Court"
	}

Explanation
"auth": can be set to "sharedPasswd", "externalToken", "oAuth2", "userPassw". 
	"oAuth2": An OAuth2 server is requiered. The user will be redirected there to login.
	"sharedPasswd": Password that the voters need to know in order to be allowed to vote
	"externalToken": The user will be asked for a token. A server is required that verifies it (https://github.com/basisentscheid/basisentscheid can do that)
"RegistrationStartDate": date when the phase 1 "obtaining return envelopes" starts
"RegistrationEndDate": date when the phase 1 "obtaining return envelopes" ends
"electionId": Name of the election which is displayed to the user. It must be unique.
optional "electionTitle": If provided, this will be shown to the user instead of the electionId 
"DelayUntil": list of dates (will be interpreted by strtotime() in php). Only relevant if registration phase and voting phase overlap. The client will prohibit sending the vote after the generation of the return envelope until the next date given in the list is reached. This is meant to prevent that the server's administrator can recovery the voter's identity by the time intercorrelation between return envelope generation and receiving the vote.  
"tally": can currently set to "publishOnly" and "configurableTally".
	"publishOnly": The voters can enter an arbitrary string which will be displayed in the result page.
	"configurableTally": Multiple choice, single choice, "yes-no", "yes-no-abstention" etc. can be used
"Questions:" A list of questions (motions), containing an id, and the questition the voters shall answer.
 

Answer, if everything is ok:

	{
		"cmd": "saveElectionUrl",
		"configUrl": "https:\/\/abstimmung.piratenpartei-nrw.de\/backend\/getelectionconfig.php?confighash=4a030ec7d483280a030c961572b7d341c09446baf98a043c642f474dc383682b"
	}

or, in case of an error, for example:

	{
		"cmd": "error",
		"errorNo": 2120,
		"errorTxt": "This election id is already used\ntt123"
	}



## Example for "oAuth2":
	{
		"authModule": "oAuth2",
		"authData": {
			"nested_groups": ["KV Düsseldorf"],
			"verified": true,
			"eligible": true,
			"RegistrationStartDate": "2014-01-27T21:20:00Z",
			"RegistrationEndDate": "2014-10-10T21:20:00Z",
			"serverId": "BEOBayern",
			"listId": "123"
		},
		"electionId": "tt1231"
	}

Explanation
"serverId": Id of the OAuth2 to be used. On serverside several OAuth2 servers can be configured.
"nested_groups": list of groups which are allowed to take part in the election (e.g. the id of devisions of a party). If empty no restriction is applied
"verified": if true: only verified users are allowed to vote
"eligible": if true: only users who are marked as eligible are allowed to vote (this can be used for adding restrictions from the statue of a party, e.g. did the user pay his membership fee?)
"listId": if set, the user must be a member of the given listId in order to be allowed to vote (This can be used for example to restrict the allowed voters to a certain working group). The according OAuth2 server must provide this information in order to work.


# Phase 1: Obtain Return Envelope

## Step A: download the election configuration

	GET /backend/getelectionconfig.php?confighash=1c1038751a8f5202caabdec3413042da3ec54683b5f1d8069333371c0d61a22e&api

Server's answer:
Shared Password:

	{
		"electionId": "tt1232",
		"auth": "sharedPassw",
		"authConfig": [],
		"blinding": "blindedVoter",
		"tally": "publishOnly",
		"cmd": "loadElectionConfig"
	}


OAuth2-BEO:

	{
		"electionId": "tt1231",
		"auth": "oAuth2",
		"authConfig": {
			"serverId": "BEOBayern",
			"listId": "123",
			"nested_groups": [2],
			"verified": true,
			"eligible": true,
			"RegistrationStartDate": "2014-01-27T21:20:00+00:00",
			"RegistrationEndDate": "2014-10-10T21:20:00+00:00"
		},
	"blinding": "blindedVoter",
	"tally": "publishOnly",
	"cmd": "loadElectionConfig"
	}

## Step B: Obtain Return Envelope

### Step B.1
Voter->Server

	POST /backend/getpermission.php
{
	"cmd": "pickBallots",
	"questions": [{
		"questionID": 1,
		"ballots": [{
			"blindedHash": "1D978A4B986423AFB4F4241115FDE37A8E6DF08FCCFBD4633021AC17FD728152A820FE384",
			"ballotno": 0
		},
		{
			"blindedHash": "473ABB166B3458F337416EE413E41F6988C241D4C661CE34346C92E5422168C66BCB768B4",
			"ballotno": 1
		},
		{
			"blindedHash": "2B3A36ACB371BAE93613B19F3D8F3CF5DC24828A972A20845C9C71F556FDE8EDD12457135",
			"ballotno": 2
		},
		{
			"blindedHash": "219C8EAD6B208A6BC09E7B9F62B1A01650E96F38C01898318390246201AB84E04CD43318E",
			"ballotno": 3
		},
		{
			"blindedHash": "E1391F52BBBF7C8A77C34039827F73640C9EB363ACBB5B49871FB6390E2751B855FA8EB",
			"ballotno": 4
		}]
	},
	{
		"questionID": 2,
		"ballots": [{
			"blindedHash": "1D978A4B986423AFB4F4241115FDE37A8E6DF08FCCFBD4633021AC17FD728152A820FE384",
			"ballotno": 0
		},
		{
			"blindedHash": "473ABB166B3458F337416EE413E41F6988C241D4C661CE34346C92E5422168C66BCB768B4",
			"ballotno": 1
		},
		{
			"blindedHash": "2B3A36ACB371BAE93613B19F3D8F3CF5DC24828A972A20845C9C71F556FDE8EDD12457135",
			"ballotno": 2
		},
		{
			"blindedHash": "219C8EAD6B208A6BC09E7B9F62B1A01650E96F38C01898318390246201AB84E04CD43318E",
			"ballotno": 3
		},
		{
			"blindedHash": "E1391F52BBBF7C8A77C34039827F73640C9EB363ACBB5B49871FB6390E2751B855FA8EB",
			"ballotno": 4
		}]
	},
	"credentials": {
		"voterId": "ich",
		"secret": "123"
	},
	"electionId": "tt1232"
}
Answer, if everything is ok (2):

{
	"questions": [{
		"questionID": 1,
		"picked": [1,3,2]
	},
	{
		"questionID": 2,
		"picked": [1,5,2]
	}],
	"cmd": "unblindBallots"
}


### Step B.3
	POST /backend/getpermission.php
"questions": [{
	"questionID": 1,
	"ballots": [{
		"votingno": "EBF8DCFF60257063A2D3560F8E2D07A7417B9AEEFA6DD6C5F120974501508D88DBBD9FF7 10001",
		"salt": "8CCED8E5B5C73678AAE583807D60C407BA0B20376969E414E1B292A111F92E81461CB494",
		"electionId": "tt1232",
		"hash": "4d48a56f9be2f786b29781371b00bec16839577ab34243fe92d11bd0c73dc66d",
		"unblindf": "307C0CAE049A06F2AB6695D60B5922C72DEB9A7E0797A87F482BE826616A6017856E21344",
		"blindedHash": "473ABB166B3458F337416EE413E41F6988C241D4C661CE34346C92E5422168C66BCB768B4",
		"ballotno": 1
	},
	{
		"votingno": "1A94749E652D8CBB5A530D0FBB3A1DB8580116AF74EB9AA9D00B76F586E6DDAF102DEB09 10001",
		"salt": "139C9B397B757A6B9518703EAF48BC88E5155ADEE027237791399F7666042C0D00D20C0",
		"electionId": "tt1232",
		"hash": "550df26e6bb6391b2262ba19a0df5ce0718bd5d2cd9ce91eb3ca191257c878ca",
		"unblindf": "3FB1E8CDC6539B3A98098C4BDD5937ECC9DB8B3A4C9EF75AD118339AE61B7634D8B077945",
		"blindedHash": "219C8EAD6B208A6BC09E7B9F62B1A01650E96F38C01898318390246201AB84E04CD43318E",
		"ballotno": 3
	},
	{
		"votingno": "26E71096B23386A2250BCCBE5692E628648E3B9B24BCB1578641648562F73D073EB292D5 10001",
		"salt": "2C1E9E4313CB2454BE00AC6A29227F289795B61C366882DA883C08AED6F12413453C0386",
		"electionId": "tt1232",
		"hash": "0064ffc2c27c49bc2736df96143146d4a2c8d85446735c9afeecd8ea40838d64",
		"unblindf": "5005EBC17F8500020DBDAE98F8E13B07C9925C39CE814E201476E5777C5C13A401175D887",
		"blindedHash": "2B3A36ACB371BAE93613B19F3D8F3CF5DC24828A972A20845C9C71F556FDE8EDD12457135",
		"ballotno": 2
	}]
	},{
	"questionID": 2,
	"ballots": [{
		"votingno": "EBF8DCFF60257063A2D3560F8E2D07A7417B9AEEFA6DD6C5F120974501508D88DBBD9FF7 10001",
		"salt": "8CCED8E5B5C73678AAE583807D60C407BA0B20376969E414E1B292A111F92E81461CB494",
		"electionId": "tt1232",
		"hash": "4d48a56f9be2f786b29781371b00bec16839577ab34243fe92d11bd0c73dc66d",
		"unblindf": "307C0CAE049A06F2AB6695D60B5922C72DEB9A7E0797A87F482BE826616A6017856E21344",
		"blindedHash": "473ABB166B3458F337416EE413E41F6988C241D4C661CE34346C92E5422168C66BCB768B4",
		"ballotno": 1
	},
	{
		"votingno": "1A94749E652D8CBB5A530D0FBB3A1DB8580116AF74EB9AA9D00B76F586E6DDAF102DEB09 10001",
		"salt": "139C9B397B757A6B9518703EAF48BC88E5155ADEE027237791399F7666042C0D00D20C0",
		"electionId": "tt1232",
		"hash": "550df26e6bb6391b2262ba19a0df5ce0718bd5d2cd9ce91eb3ca191257c878ca",
		"unblindf": "3FB1E8CDC6539B3A98098C4BDD5937ECC9DB8B3A4C9EF75AD118339AE61B7634D8B077945",
		"blindedHash": "219C8EAD6B208A6BC09E7B9F62B1A01650E96F38C01898318390246201AB84E04CD43318E",
		"ballotno": 3
	},
	{
		"votingno": "26E71096B23386A2250BCCBE5692E628648E3B9B24BCB1578641648562F73D073EB292D5 10001",
		"salt": "2C1E9E4313CB2454BE00AC6A29227F289795B61C366882DA883C08AED6F12413453C0386",
		"electionId": "tt1232",
		"hash": "0064ffc2c27c49bc2736df96143146d4a2c8d85446735c9afeecd8ea40838d64",
		"unblindf": "5005EBC17F8500020DBDAE98F8E13B07C9925C39CE814E201476E5777C5C13A401175D887",
		"blindedHash": "2B3A36ACB371BAE93613B19F3D8F3CF5DC24828A972A20845C9C71F556FDE8EDD12457135",
		"ballotno": 2
	}]
	}],
	
	"cmd": "signBallots",
	"credentials": {
		"voterId": "ich",
		"secret": "123"
	},
	"electionId": "tt1232"
}


Explanation
"votingno": RSA public key created by the voter as candidate for anonymous signature of the vote
"salt": just a random string
"unblindf": factor to unblind the hash transmitted in step B.1

only for debug purposes, not used: "blindedHash", "hash", "electionId"

Answer, if everything is ok:

	{
		"ballots": [{
			"ballotno": 3,
			"blindedHash": "1EDEC138E0E89BB10E7BDE06672D13A80411A86BC162F81CB60FFEEA42022CF4D9FEC6D1D",
			"sigs": [{
				"sig": "3784F268FA231B428092F969A1A8A46A7F6038239A315BF491230ACCF49E2019F336BDDEC",
				"sigBy": "PermissionServer1",
				"serSig": "0554421462f39081f769c181da237972a1dad8ca1d68ec28e6da725b387f8b53138564389f"
		},
		{
				"sig": "d9a225c859fe03f60be77452e42ebf2c792e6b85700eeaf849b60f347284174eff9d04d1",
				"sigBy": "PermissionServer2",
				"serSig": "02a6f5acd933cd9f6533618012ea35369b5ed30b3c4e05ad23197b5aa8e379b5fb53075e9d"
		}]
	}],
		"cmd": "savePermission"
	}

# Phase 2: Cast the Vote

>POST /backend/storevote.php (send through a service which anonymizes the ip address of the voter!!!): 

	{
		"permission": {
			"str": "{\"electionId\":\"tt1232\",\"votingno\":\"1A94749E652D8CBB5A530D0FBB3A1DB8580116AF74EB9AA9D00B76F586E6DDAF102DEB09 10001\",\"salt\":\"139C9B397B757A6B9518703EAF48BC88E5155ADEE027237791399F7666042C0D00D20C0\"}",
			"hash": "550df26e6bb6391b2262ba19a0df5ce0718bd5d2cd9ce91eb3ca191257c878ca",
			"sigs": [{
				"blindSig": "0554421462f39081f769c181da237972a1dad8ca1d68ec28e6da725b387f8b53138564389f",
				"sig": "3784F268FA231B428092F969A1A8A46A7F6038239A315BF491230ACCF49E2019F336BDDEC",
				"sigBy": "PermissionServer1",
				"sigOk": 1,
				"serSig": "0554421462f39081f769c181da237972a1dad8ca1d68ec28e6da725b387f8b53138564389f"
			},
			{
				"blindSig": "d9a225c859fe03f60be77452e42ebf2c792e6b85700eeaf849b60f347284174eff9d04d1",
				"sig": "3784F268FA231B428092F969A1A8A46A7F6038239A315BF491230ACCF49E2019F336BDDEC",
				"sigBy": "PermissionServer2",
				"sigOk": 1,
				"serSig": "02a6f5acd933cd9f6533618012ea35369b5ed30b3c4e05ad23197b5aa8e379b5fb53075e9d"
		}],
			"signed": {
				"electionId": "tt1232",
				"votingno": "1A94749E652D8CBB5A530D0FBB3A1DB8580116AF74EB9AA9D00B76F586E6DDAF102DEB09 10001",
				"salt": "139C9B397B757A6B9518703EAF48BC88E5155ADEE027237791399F7666042C0D00D20C0"
			}
		},
		"vote": {
			"vote": "dafür",
			"sig": "A5D2EC4DD1633EE198AD29A319B3CAAA8A1F30EB00CBB421D3773A9A2C4C1287F26F455"
		},
		"cmd": "storeVote"
	}


Answer, if everything is ok:

	----vvvote----
	{"cmd":"saveYourCountedVote"}
	----vvvote----





> Written with [StackEdit](https://stackedit.io/).

