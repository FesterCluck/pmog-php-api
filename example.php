<?php

//Includes
//You'll most likely need each of these on each php doc.
//securlogin.php is just a simple way to keep the login
//in one place. Could probably be more secure.

require_once("pmog-api.php");

print "<HTML><BODY><PRE>";


//Test Data
//I suggest you change the $url variable to your own username for testing
//--------------------------------------------------------------
$url = "http://www.thenethernet.com/users/pmog";
$dp = 10;
$comment = "Test";
$armor = 0;
$crates = 0;
$lightposts = 0;
$mines = 0;
$portals = 0;
$nicks = 0;
$explode = false;
$lock = false;
$recipient = "stephen";
$message = "Test API 2.0";
$portaltitle = "Better News";
$portalurl = "http://www.cnn.com";
$destination = "http://www.ap.org";
$nsfw = false;
$abundant = false;
$myevents = array();
$playerprofile = array();
$playername = "stephen";
//------------------------------------

//Available Functions
//------------------------------------

//StashCrate($url, $dp, $comment, $armor, $crates, $lightposts, $mines, $portals, $nicks, $explode, $lock);
//LootCrate($url);

//StashDPCard($url);
//LootDPCard($url);

//SetMine($url);
//SetWatchDog($url);

//DrawPortal($portaltitle, $portalurl, $destination, $nsfw, $abundant);

//TrackURL($url);

//SendMail($recipient, $message);
//SetPlayerTag($playername, $tag);

//Get 'Flash' message returned from PMOG
//GetFlash();

//$myevents is an array containing user events/pmail
//$myevents = GetEvents();

//$playerprofile is a HudData-like json object containing the requested player's profile
//$playerprofile = GetProfile($playername);


print "</PRE>";
print "</BODY></HTML>";
?>