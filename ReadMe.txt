PMOG PHP4 API v2.2
-----------------------------
Author: Stephen Kraushaar
Email: skraushaar@gmail.com
3:42 PM 5/21/2009


This API was created to allow a web server to act as a user on PMOG.

Changes
-----------------------------

Using Login() is no longer necessary, the API will log in automatically if it does not have HudData.

No creation of silly arrays anymore. All commands should be straight forward, see example.php for examples.


Installation:
-----------------------------
Open securelogin.php and enter the appropriate login credentials.

Copy securelogin.php, pmog-api.php, and json.php to your server.

File example.php is a short example on how to stash a crate, along with a way to show HudData.


Basics:
-----------------------------

The following includes should be on every page you plan to use the API on:

require_once("pmog-api.php");


Available Functions:

!!!IMPORTANT THIS FUNCTION HAS CHANGED!!!!
StashCrate(string $url, int $dp, int $pings, string $comment, int $armor, int $crates, int $lightposts, int $mines, int $portals, int $nicks, $skeleton_keys, int $grenades, int $watchdogs, bool $explode, bool $lock)

Stash a crate. Locked crates not yet implemented.

LootCrate(string $url)
Loot the first crate on $url.

StashDPCard(string $url)
Stash a DP Card

LootDPCard(string $url)
Loot the first DP Card on $url.

SetMine(string $url)
Deploy a mine

SetWatchDog(string $url)
Unleash a Watch Dog

DrawPortal(string $portaltitle, string $portalurl, string $destination, bool $nsfw, bool $abundant)
Draw a portal

TrackURL(string $url)
Visit Url. This triggers tool events and awards DP.

SendMail(string $recipient, string $message)
Send a PMail, no '@' needed.

SetPlayerTag(string $playername, string $tag)
Set a tag on a player's profile

GetFlash()
Returns 'Flash' message returned from PMOG.
Strings such as "Crate Stashed!" and most server Error messages




GetEvents()
Returns an array containing user events/pmail


GetProfile(string $playername)
Returns a HudData-like Array object containing the requested player's profile


ToDo Next Release
-----------------------------
Give friendlier interface to HudData