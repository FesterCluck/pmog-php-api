<?php

//Includes
//You'll most likely need each of these on each php doc.
//securlogin.php is just a simple way to keep the login
//in one place. Could probably be more secure.

require_once("pmog-api.php");

//This demo will stash a crate and return the HudData on screen.
print "<HTML><BODY><PRE>";

//You could put this into the $HudData JSON object, this is just for ease.
//Look in pmog-api.php. You'll see everything runs on the Request() method,
//but I've written helper methods for each.

//Crate Stash/Loot Url
$url = "http://thenethernet.com/guide/badges/the-giver"; 
$dp = 100;
$comment = "Enjoy!";
$armor = 0;
$crates = 1;
$lightposts = 0;
$mines = 0;
$portals = 0;
$nicks = 0;
$explode = false;
$lock = false;
$recipient = "username";
$message = "Test API 2.0";
$portaltitle = "Better News";
$portalurl = "http://www.cnn.com";
$destination = "http://www.ap.org";
$nsfw = false;
$abundant = false;
$myevents = array();
$playername = "username";
$playerprofile = array();

//securelogin.php now allows for storage of multiple users.
$currentlogin = "otheruser";

//Set this to true for Debug messages.
$Debug = false;

//This document is incomplete. Please see pmog-api.php for all new available functionality.
//StashCrate($url, $dp, $comment, $armor, $crates, $lightposts, $mines, $portals, $nicks, $explode, $lock);
//LootCrate($url);
//StashDPCard($url);
//LootDPCard($url);
//SetMine($url);
//SetWatchDog($url);
//DrawPortal($portaltitle, $portalurl, $destination, $nsfw, $abundant);
//TrackURL($url);
//SendMail($recipient, $message);
//LogOff();
//SetPlayerTag($playername, $tag);
//GetFlash();
//$myevents is an array containing user events/pmail
//$myevents = GetEvents();
//$playerprofile is a HudData-like json object containing the requested player's profile
//$playerprofile = GetProfile($playername);
//Read current message in Hud. This is the equivalent of closing a pmail popup.
//$ReadMsg()
//Crate Allies Test
ChangeUser($currentlogin);
$myevents = array();
$myevents = GetEvents();

//Test for reading Events
//
//$LastTimeStamp = gmdate('D, d M Y H:i:s T', mktime(gmdate("H"), gmdate("i"), gmdate("s"), gmdate("m")  , gmdate("d")-1, gmdate("Y")));
//print $LastTimeStamp;
//print "<br />";
//foreach($myevents as $event)
//{
//  if($event[author] != "thenethernet")
//  {
//    print $event[author].": ".$event[description]."<br />";
//  }
//}

//Read All Messages
//PollMessages();
//while($HudData['messages']!=null)
//{
//  set_time_limit(60);
//  print_r($HudData['messages']);
//  ReadMsg();

//  PollMessages();
//}



print "</PRE>";
print "</BODY></HTML>";
?>