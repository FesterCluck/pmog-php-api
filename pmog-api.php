<?php

//This code not including json.php authored by Stephen Kraushaar
//Gimme some props, yo.
//v2.0

//Includes
require_once("json.php");
require_once("securelogin.php");

//Globals
$headers = '';
$cookies = '';
$json = new Services_JSON();
$HudData = array();
$HudData['user'] = array();

//Retrieves PMail/Events
//Messages.rss
//
//Returns:
// array[#]['title']
// array[#]['link']
// array[#]['description']
// array[#]['pubDate']
// array[#]['guid']
// array[#]['author']

function GetEvents()
{
  $pdata = GetHUD();
  // Declarations
  $rss = domxml_new_doc('1.0');
  $itemPropertyText = new DomText('1.0');
  $events = array();
  // Request Events
  $data = Request("myevents", $pdata);
  
  // Parse XML to an array
  $rss = domxml_open_mem($data);
  $items = $rss->get_elements_by_tagname("item");
  foreach($items as $item) {
    $itemProperties = $item->get_elements_by_tagname('*');
    $events[] = array();
    foreach($itemProperties as $itemProperty) {
      $itemPropertyText = $itemProperty->first_child();
      $propertyName = $itemProperty->node_name();
      $propertyValue = $itemPropertyText->node_value();
      $events[sizeof($events)-1][$propertyName] = $propertyValue;      
    }
  }
  return $events;
}

//Get a User's Profile
//Returns a HudData-like json object

function GetProfile($username)
{
  global $json;
  $data = array();
  $data['login'] = $username;
  $data = Request("profile", $data);
  $data = get_object_vars($json->decode($data));
  $data['levels'] = get_object_vars($data['levels']); 
  return $data;
}

//Send PMail

function SendMail($recipient, $message)
{
  $data = GetHUD(); 
  $data['messages'] = array(
    "pmail_to"=>"@".$recipient,
    "pmail_message"=>$message);
  $data = Request("sendmail", $data);
  UpdateHUD($data);
}

//Set Player Tag

function SetPlayerTag($playername, $tag)
{
  // Declarations
  $data = GetHUD(); 

  // Setup data object for login
  $data['tags'] = array();
  $data['tags'][0] = $tag;
  $data['url'] = "/users/".$playername;

  // Set Tag
  $data = Request("tag", $data);
      
  UpdateHUD($data);
}

//Set Mine

function SetMine($url)
{
  $data = GetHUD(); 
  $data = PrepareRequest($data, $url);
  $data = Request("mine", $data);
  UpdateHUD($data);
}

//Unleash WatchDog

function SetWatchDog($url)
{
  $data = GetHUD(); 
  $data = PrepareRequest($data, $url);
  $data = Request("watchdog", $data);
  UpdateHUD($data);
}

//Add URL to PMOG DB, get DP

function TrackURL($url)
{
  $data = GetHUD();
  $data = PrepareRequest($data, $url);
  $result = array();
  $data = Request("track", $data);
  UpdateHUD($data);
}

//Loot a crate

function LootCrate($url)
{
  TrackURL($url);
  $data = GetHUD();
  $data = PrepareRequest($data, $url);
  $data['crates']['0'] = get_object_vars($data['crates']['0']);
  $data = Request("loot", $data);
  UpdateHUD($data);
}

//Get 'Flash' message returned from PMOG
function GetFlash()
{
  $data = GetHUD();
  
  if($data['flash']!=null)
  {
    if($data['flash']['notice']!=null)
    {
      $fmsg = $data['flash']['notice'];
    }
    if($data['flash']['error']!=null)
    {
      $fmsg = $data['flash']['error'];
    }
  }
  
  return $fmsg;
}

//Stash a crate

function StashCrate($url, $dp, $comment, $armor, $crates, $lightposts, $mines, $portals, $nicks, $explode, $lock)
{
  $data = GetHUD();
  $data = PrepareRequest($data, $url);
  $data['crates']['0'] = get_object_vars($data['crates']['0']);
  $data['crate_contents'] = array();
  $data['crate_contents']['comment'] = $comment;
  $data['crate_contents']['tools'] = array();
  $data['crate_contents']['datapoints'] = $dp;
  $data['crate_contents']['tools']['armor'] = $armor;
  $data['crate_contents']['tools']['crates'] = $crates;
  $data['crate_contents']['tools']['lightposts'] = $lightposts;
  $data['crate_contents']['tools']['mines'] = $mines;
  $data['crate_contents']['tools']['portals'] = $portals;
  $data['crate_contents']['tools']['st_nicks'] = $nicks;
  $data['crates']['0']['upgrades'] = array();
  $data['crates']['0']['upgrades']['exploding'] = ($explode) ? 'true':'false';

  $data = Request("stash", $data);

  UpdateHUD($data);
}
//Draw a portal

function DrawPortal($title, $url, $destination, $nsfw, $abundant)
{
  $data = GetHUD();
  $data = PrepareRequest($data, $url);
  
  $data['portals'] = array();
  $data['portals']['title'] = $title;
  $data['portals']['destination'] = $destination;
  $data['portals']['nsfw'] = ($nsfw) ? 'true':'false';
  $data['portals']['give_dp'] = ($abundant) ? 'true':'false';

  $data = Request("portal", $data);

  UpdateHUD($data);
}
  
//Stash a DP Card

function StashDPCard($url)
{
  $data = GetHUD();
  $data = PrepareRequest($data, $url);
  $data = Request("giftcard", $data);

  UpdateHUD($data);
}

function LootDPCard($url)
{
  $data = GetHUD();
  $data = PrepareRequest($data, $url);
  $data['giftcards']['0'] = get_object_vars($data['giftcards']['0']);
  $data = Request("getcard", $data);
  UpdateHUD($data);
}

function Request($requestType, $pdata)
{
  global $headers;
  global $cookies;

  // Establish the connection and send the request
  switch($requestType) {
    case "login":
	  $fp = fsockopen("ext.pmog.com", 80);
	  fputs($fp,"POST /session.json?login=".$pdata['user']['login']."&password=".urlencode($pdata['user']['password'])." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Encoding: gzip,deflate\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: 0\r\n");
	  fputs($fp,"Content-Type: application/xml; charset=UTF-8\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          break;

    case "myevents":
         $yesterday = date("D, j M Y g:i:s ", mktime(5, 0, 0, date("m")  , date("d")-1, date("Y")));
	  $fp = fsockopen("pmog.com", 80);
	  fputs($fp, "GET /users/".$pdata['user']['login']."/messages.rss HTTP/1.1\r\n");
	  fputs($fp,"Host: pmog.com\r\n");
          fputs($fp, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n");

	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n\r\n");
          fputs($fp, "If-Modified-Since: ". $yesterday." GMT\r\n\r\n");
	  break;

    case "search":
          $getPhrase = "GET /locations/search.json";
          $getPhrase .= "?url=".$pdata;
	  $fp = fsockopen("ext.pmog.com", 80);
	  fputs($fp, $getPhrase." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"Accept: text/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Encoding: gzip,deflate\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
	  break;


    case "track":
	  $fp = fsockopen("ext.pmog.com", 80);
	  fputs($fp,"GET /track.json?version=0.5.11&auth_token=".$pdata['user']['auth_token']."&authenticity_token=".$pdata['user']['authenticity_token']."&url=".$pdata['url']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: 0\r\n");
	  fputs($fp,"Content-Type: application/xml; charset=UTF-8\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          break;


    case "mine":
	  $fp = fsockopen("ext.pmog.com", 80);
	  fputs($fp,"POST /locations/".$pdata['id']."/mines.json?auth_token=".$pdata['user']['auth_token']."&authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: 0\r\n");
	  fputs($fp,"Content-Type: application/xml; charset=UTF-8\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          break;

    case "loot":
	  $fp = fsockopen("ext.pmog.com", 80);
	  fputs($fp,"PUT /locations/".$pdata['id']."/crates/".$pdata['crates']['0']['id']."/loot.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: 0\r\n");
	  fputs($fp,"Content-Type: application/xml; charset=UTF-8\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n\r\n");
          break;

    case "getcard":
	  $fp = fsockopen("ext.pmog.com", 80);
	  fputs($fp,"PUT /locations/".$pdata['id']."/giftcards/".$pdata['giftcards']['0']['id']."/loot.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: 0\r\n");
	  fputs($fp,"Content-Type: application/xml; charset=UTF-8\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n\r\n");
          break;
          
    case "stash":
	  $fp = fsockopen("ext.pmog.com", 80);
          $stashurl = "POST /locations/".$pdata['id']."/crates.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n";
          $cratetext = "{\"crate\": {";
          if ($pdata['crates']['0']['upgrades']['exploding'] == 'false')
          {
              $cratetext .="\"datapoints\": \"".$pdata['crate_contents']['datapoints']."\", ";
          }
          $cratetext .="\"comments\": \"".$pdata['crate_contents']['comment']."\", \"tools\": {";
          if ($pdata['crates']['0']['upgrades']['exploding'] == 'false')
          {
            $cratetext .="\"armor\": \"".$pdata['crate_contents']['tools']['armor']."\", \"crates\": \"".$pdata['crate_contents']['tools']['crates']."\", \"lightposts\": \"".$pdata['crate_contents']['tools']['lightposts']."\", \"mines\": \"".$pdata['crate_contents']['tools']['mines']."\", \"portals\": \"".$pdata['crate_contents']['tools']['portals']."\", \"st_nicks\": \"".$pdata['crate_contents']['tools']['st_nicks']."\"}}";
          } else {
            $cratetext .="}}, \"upgrade\": {\"exploding\": \"true\"}";
          }
          $cratetext .="}";
	  fputs($fp,$stashurl);
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: ".strlen($cratetext)."\r\n");
	  fputs($fp,"Content-Type: application/json; charset=utf-8\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          fputs($fp,$cratetext);
          break;
          
    case "portal":
	  $fp = fsockopen("ext.pmog.com", 80);
          $cratetext = "{\"portal\": {";
          $cratetext .="\"title\": \"".$pdata['portals']['title']."\", ";
          $cratetext .="\"destination\": \"".$pdata['portals']['destination']."\", ";
          $cratetext .="\"nsfw\": ".$pdata['portals']['nsfw']."}, ";
          $cratetext .="\"upgrade\": {";
          $cratetext .="\"give_dp\": ".$pdata['portals']['give_dp']."}}";
	  fputs($fp,"POST /locations/".$pdata['id']."/portals.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: ".strlen($cratetext)."\r\n");
	  fputs($fp,"Content-Type: application/json; charset=utf-8\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          fputs($fp,$cratetext);
          break;
          
    case "giftcard":
          $fp = fsockopen("ext.pmog.com", 80);
          $stashurl = "POST /locations/".$pdata['id']."/giftcards.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n";
          $cratetext = "{\"crate\": {\"datapoints\": \"".$pdata['crate_contents']['datapoints']."\", \"comments\": \"".$pdata['crate_contents']['comment']."\", \"tools\": {\"armor\": \"".$pdata['crate_contents']['tools']['armor']."\", \"crates\": \"".$pdata['crate_contents']['tools']['crates']."\", \"lightposts\": \"".$pdata['crate_contents']['tools']['lightposts']."\", \"mines\": \"".$pdata['crate_contents']['tools']['mines']."\", \"portals\": \"".$pdata['crate_contents']['tools']['portals']."\", \"st_nicks\": \"".$pdata['crate_contents']['tools']['st_nicks']."\"}}}";
	  fputs($fp,$stashurl);
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: ".strlen($cratetext)."\r\n");
	  fputs($fp,"Content-Type: application/json; charset=utf-8\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          fputs($fp,$cratetext);
          break;

    case "watchdog":
          $fp = fsockopen("ext.pmog.com", 80);
	  fputs($fp,"POST /locations/".$pdata['id']."/watchdogs/attach.json?auth_token=".$pdata['user']['auth_token']."&authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: ".strlen($cratetext)."\r\n");
	  fputs($fp,"Content-Type: application/json; charset=utf-8\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          break;

    case "tag":
	  $fp = fsockopen("ext.pmog.com", 80);
          $tagurl = "POST ".$pdata['url']."/add_tag HTTP/1.1\r\n";
          $posttext = "authenticity_token=".$pdata['user']['authenticity_token']."&tag%5Bname%5D=".urlencode($pdata['tags'][0])."&commit=Tag&_method=put";
	  fputs($fp,$tagurl);
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: ".strlen($posttext)."\r\n");
	  fputs($fp,"Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          fputs($fp,$posttext);

          break;

    case "sendmail":

          $pmailtext = "{";
          foreach($pdata["messages"] as $key=>$value)
          {
            $pmailtext .= "\"";
            $pmailtext .= $key;
            $pmailtext .= "\": ";
            $pmailtext .= "\"";
            $pmailtext .= $value;
            $pmailtext .= "\", ";
          }
          $pmailtext = substr($pmailtext, 0, -2) . "}";
	  $fp = fsockopen("ext.pmog.com", 80);
	  fputs($fp,"POST /users/".$pdata['user']['login']."/messages.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.pmog.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: ".strlen($pmailtext)."\r\n");
	  fputs($fp,"Content-Type: application/json; charset=utf-8\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n");
	  fputs($fp,"Pragma: no-cache\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          fputs($fp, $pmailtext);
          break;


    case "profile":
	  $fp = fsockopen("pmog.com", 80);
	  fputs($fp, "GET /users/".$pdata['login'].".json HTTP/1.1\r\n");
	  fputs($fp,"Host: pmog.com\r\n");
          fputs($fp, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n\r\n");
	  break;
    }
	
  // Read response from server
  while(!feof($fp)) {
    // receive the results of the request
    $result .= fgets($fp, 128);
  }

 
  // Close the socket connection
  fclose($fp);

  //Debug
  //if($requestType=='stash')
  //{
  //print "<br />\r\n------".$requestType."----\r\n<br />";
  //print $result;
  //}


  // Separate headers from response
  $result = explode("\r\n\r\n", $result, 2);
  $header = isset($result[0]) ? $result[0] : '';
  $content = isset($result[1]) ? $result[1] : '';
  $headers = explode("\r\n", $header);

  //Update global headers/cookies
  ParseHeaders();
  
  return $content;
}

//Internal Functions

function ParseHeaders()
{
  global $headers, $cookies;
  
  // Empty global cookies
  $cookies = '';

  // Parse global headers for updates
  foreach($headers as $item)
  {
    $header = explode(":", $item);
      
    switch($header[0]) {

      case "Set-Cookie":
        if (strpos($header[1], 'last_seen') || strpos($header[1], 'session')) {
          $cookies .= $header[1].";";
        }
        $cookies = str_replace(" path=/;","", $cookies);
        break;

      default:
        break;
    }
  }
  $cookies.=" path=/";
}

function PrepareRequest($data, $url)
{
  global $json;
  $requestUID = array(
                       "url"=>$url,
                       "id"=>""
                 );

  list($requestUID["url"], $requestUID["id"]) = GetID($requestUID["url"]);

  $data["id"] = $requestUID["id"];
  $data["url"] = $requestUID["url"];
  
  return $data;
}

function GetHUD()
{
  global $HudData;
  if($HudData['user']['user_id'] == null)
  {
    Login();
  }
  return $HudData;
}

function UpdateHUD($data)
{
  global $json;
  global $HudData;

  $data = get_object_vars($json->decode($data));
  $data['user'] = get_object_vars($data['user']);
  
  //Message 
  if($data['flash'] != null)
  {
    $data['flash'] = get_object_vars($data['flash']);
  }
  
  //Crate
  if ($data['crate_contents'] != null)
  {
    $data['crate_contents'] = get_object_vars($data['crate_contents']);
  }
  $HudData = $data;
}  

function Login()
{
  // Declarations
  global $PMOGusername;
  global $PMOGpassword;
  $login = $PMOGusername;
  $pass = $PMOGpassword;

  $data = array();
  $data['user'] = array();
  
  // Setup data object for login
  $data['user']['login'] = $login;
  $data['user']['password'] = $pass;

  // Request Login
  $data = Request("login", $data);
  
  UpdateHUD($data);
}

// Grab UID for pages
// search.json
//
// Returns:
//    array['url']
//    array['id']

function GetID($url)
{
  // Declarations
  global $json;
  $data = '';
  $result = array();

  // Request Url Data
  $data = Request("search", $url);
  
  // parse JSON to array
  $result = get_object_vars($json->decode($data));
  return array($result["url"], $result["id"]);
}

?>