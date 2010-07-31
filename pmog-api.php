<?php

//This code not including json.php authored by Stephen Kraushaar
//Gimme some props, yo.
//v2.1

//Includes
require_once("json.php");
require_once("securelogin.php");

if (PHP_VERSION>='5')
 require_once('dom4to5.php');


//Globals
$headers = '';
$cookies = '';
$json = new Services_JSON();
$HudData = array();
$HudData['user'] = array();
$Debug = false;
//$LastTimeStamp = gmdate('D, d M Y H:i:s T', mktime(5, 0, 0, gmdate("m")  , gmdate("d")-1, gmdate("Y")));;

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
  $currentpage=0;
  $eventcount=0;
  $data = array();


  do {
   
   //Increment current page and set $pdata
   $currentpage+=1;
   $pdata['page'] = $currentpage;
   
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
   
   $countdiff = count($events) - $eventcount;
   $eventcount = count($events);
   
  } while ($countdiff > 0);
  
  unset($pdata['page']);
  return $events;
}

function GetContacts()
{
  global $json;
  $contacts = array();
  $data = GetHUD();
  $contacts = Request("contacts", $data);
  $contacts = get_object_vars($json->decode($contacts));

  foreach($contacts['allies'] as $allykey => $ally)
    $contacts['allies'][$allykey] = get_object_vars($ally);
  foreach($contacts['rivals'] as $rivalkey => $rival)
    $contacts['rivals'][$rivalkey] = get_object_vars($rival);
  foreach($contacts['recently_active'] as $rakey => $ra)
    $contacts['recently_active'][$rakey] = get_object_vars($ra);
    
  return $contacts;
}


function PollMessages()
{  
  $data = GetHUD();
  $data = Request("messages", $data);
  UpdateHUD($data);
}

function ReadMsg()
{
  $data = GetHUD();
  $data = Request("read", $data);
  UpdateHUD($data);
  usleep(100);  //Artificial rate limiter. TNN's servers can't always keep up.
}

function DeleteMsg($events)
{
  $data = GetHUD();
  foreach($events as $event)
  {
    $data['messages'] = $event;
    $tmpdata = Request("delete", $data);
  }
  return count($events);
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
  if (isset($data['crates'][0]))
  {
    $data['crates'][0] = get_object_vars($data['crates'][0]);
    $cratedata = $data['crates'][0];
    $data = Request("loot", $data);
    UpdateHUD($data);
    $data = GetHUD();
    if(isset($data['crate_contents']))
    {
      $data['crate_contents']['id'] = $cratedata['id'];
      return $data['crate_contents'];
    }
  }
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

function LogOff()
{
  $data = GetHUD(); 
  $data = Request("logoff", $data);
  UpdateHUD($data);
}



function ChangeUser($username)
{
  global $PMOGusername;
  global $PMOGpassword;
  global $PMOGCredentials;
  
  foreach($PMOGCredentials as $credential)
  {
    if($credential['login']==$username)
    {
      $PMOGusername = $credential['login'];
      $PMOGpassword = $credential['password'];
	break;
    }
  }
}


///////////////////////////////////////////////////////////////////
//Methods listed below were not designed to be called directly.
///////////////////////////////////////////////////////////////////



function Request($requestType, $pdata)
{
  global $headers;
  global $cookies;
  global $LastTimeStamp;
  global $Debug;

  // Establish the connection and send the request
  switch($requestType) {
    case "login":
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"POST /session.json?login=".$pdata['user']['login']."&password=".urlencode($pdata['user']['password'])." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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

    case "logoff":
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"POST /session?_method=delete&auth_token=".$pdata['user']['auth_token']."&authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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

    case "contacts":
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"GET /acquaintances/".$pdata['user']['login'].".json HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"Content-Length: 0\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n\r\n");
	  fputs($fp,"Cache-Control: no-cache\r\n\r\n");
          break;
          
    case "myevents":
	  $fp = fsockopen("thenethernet.com", 80);
	  fputs($fp, "GET /users/".$pdata['user']['login']."/messages.rss?page=".$pdata['page']." HTTP/1.1\r\n");
	  fputs($fp,"Host: thenethernet.com\r\n");
          fputs($fp, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n");

	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n\r\n");
          //fputs($fp, "If-Modified-Since: ". $LastTimeStamp ."\r\n\r\n");
	  break;
	  
	  
     case "messages":
	  $fp = fsockopen("thenethernet.com", 80);
	  fputs($fp, "GET /users/".$pdata['user']['login']."/messages.json?version=0.6.1&auth_token=".$pdata['user']['auth_token']."&authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: thenethernet.com\r\n");
          fputs($fp, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: text/json, text/javascript, */*\r\n");

	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n\r\n");
          //fputs($fp, "If-Modified-Since: ". $LastTimeStamp ."\r\n\r\n");
	  break;

    case "read":
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"PUT /users/".$pdata['user']['login']."/messages/".$pdata['messages']['0']['id']."/read.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
          
    case "delete":
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  $postphrase = "POST /users/".$pdata['user']['login']."/messages/".substr($pdata['messages']['guid'], -36, 36)."?_method=delete&authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n";
	  fputs($fp, $postphrase);
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
	  fputs($fp,"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3\r\n");
	  fputs($fp,"Accept: application/json, text/javascript, */*\r\n");
	  fputs($fp,"Accept-Language: en-us,en;q=0.5\r\n");
	  fputs($fp,"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n");
	  fputs($fp,"Connection: close\r\n");
	  fputs($fp,"X-Requested-With: XMLHttpRequest\r\n");
	  fputs($fp,"Content-Length: 0\r\n");
	  fputs($fp,"Cookie:".$cookies."\r\n\r\n");
          break;

    case "search":
          $getPhrase = "GET /locations/search.json";
          $getPhrase .= "?url=".$pdata;
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp, $getPhrase." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"GET /track.json?version=0.5.11&auth_token=".$pdata['user']['auth_token']."&authenticity_token=".$pdata['user']['authenticity_token']."&url=".$pdata['url']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"POST /locations/".$pdata['id']."/mines.json?auth_token=".$pdata['user']['auth_token']."&authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"PUT /locations/".$pdata['id']."/crates/".$pdata['crates'][0]['id']."/loot.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"PUT /locations/".$pdata['id']."/giftcards/".$pdata['giftcards']['0']['id']."/loot.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("ext.thenethernet.com", 80);
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
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("ext.thenethernet.com", 80);
          $cratetext = "{\"portal\": {";
          $cratetext .="\"title\": \"".$pdata['portals']['title']."\", ";
          $cratetext .="\"destination\": \"".$pdata['portals']['destination']."\", ";
          $cratetext .="\"nsfw\": ".$pdata['portals']['nsfw']."}, ";
          $cratetext .="\"upgrade\": {";
          $cratetext .="\"give_dp\": ".$pdata['portals']['give_dp']."}}";
	  fputs($fp,"POST /locations/".$pdata['id']."/portals.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
          $fp = fsockopen("ext.thenethernet.com", 80);
          $stashurl = "POST /locations/".$pdata['id']."/giftcards.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n";
          $cratetext = "{\"crate\": {\"datapoints\": \"".$pdata['crate_contents']['datapoints']."\", \"comments\": \"".$pdata['crate_contents']['comment']."\", \"tools\": {\"armor\": \"".$pdata['crate_contents']['tools']['armor']."\", \"crates\": \"".$pdata['crate_contents']['tools']['crates']."\", \"lightposts\": \"".$pdata['crate_contents']['tools']['lightposts']."\", \"mines\": \"".$pdata['crate_contents']['tools']['mines']."\", \"portals\": \"".$pdata['crate_contents']['tools']['portals']."\", \"st_nicks\": \"".$pdata['crate_contents']['tools']['st_nicks']."\"}}}";
	  fputs($fp,$stashurl);
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
          $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"POST /locations/".$pdata['id']."/watchdogs/attach.json?auth_token=".$pdata['user']['auth_token']."&authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("ext.thenethernet.com", 80);
          $tagurl = "POST ".$pdata['url']."/add_tag HTTP/1.1\r\n";
          $posttext = "authenticity_token=".$pdata['user']['authenticity_token']."&tag%5Bname%5D=".urlencode($pdata['tags'][0])."&commit=Tag&_method=put";
	  fputs($fp,$tagurl);
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("ext.thenethernet.com", 80);
	  fputs($fp,"POST /users/".$pdata['user']['login']."/messages.json?authenticity_token=".$pdata['user']['authenticity_token']." HTTP/1.1\r\n");
	  fputs($fp,"Host: ext.thenethernet.com\r\n");
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
	  $fp = fsockopen("thenethernet.com", 80);
	  fputs($fp, "GET /users/".$pdata['login'].".json HTTP/1.1\r\n");
	  fputs($fp,"Host: thenethernet.com\r\n");
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
  if($Debug)
  {
    print "<br />\r\n------".$requestType."----\r\n<br />";
    if(isset($postphrase))
      print $postphrase."<br />";
    print $result;
  }
  


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
  global $PMOGusername;
  if($HudData['user']['user_id'] != $PMOGusername)
  {
    Login();
  }
  return $HudData;
}

function UpdateHUD($data)
{
  global $json;
  global $HudData;

  $msgs = array();
  $msgdata = array();
  $msgdata["body"] = array();
  $data = get_object_vars($json->decode($data));
  $data['user'] = get_object_vars($data['user']);
  
  //Messages
  if($data['messages']!=null)
  {
    foreach($data['messages'] as $pkey => $pmessage)
    {
      $msgdata = get_object_vars($pmessage);
      $msgdata["body"] = get_object_vars($json->decode($msgdata["body"]));
      $msgs[] = $msgdata;
    }
    $data['messages'] = $msgs;
  }
  
  //Flash Message 
  if($data['flash'] != null)
  {
    $data['flash'] = get_object_vars($data['flash']);
  }
  
  //Crate
  if($data['crate_contents'] != null)
  {
    $data['crate_contents'] = get_object_vars($data['crate_contents']);
  }
  $HudData = $data;
}  

function Login($login = '', $pass = '')
{
  // Declarations
  global $PMOGusername;
  global $PMOGpassword;
  if($login=='')
  {
    $login = $PMOGusername;
    $pass = $PMOGpassword;
  }

  $data = array();
  $data['user'] = array();
  
  // Setup data object for login
  $data['user']['login'] = $login;
  $data['user']['password'] = $pass;

  // Request Login
  $data = Request("login", $data);
  
  UpdateHUD($data);
  
}


?>