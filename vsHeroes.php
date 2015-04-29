<?php

require_once('sortFunctions.php');
require_once('fetchUrl.php');

if (!(file_exists('matchs') && is_dir('matchs')))
  shell_exec('mkdir matchs');
if (!(file_exists('historys') && is_dir('historys')))
  shell_exec('mkdir historys');

// Settings - Filters

$apikey = trim(file_get_contents('steamapikey'));
$steamId = 109943; // main account laxa
// be carefull to use good date format according to your region configuration
/* $startTime = strtotime('04/01/2015'); */
/* $endTime = strtotime('12/12/2016'); */
$maxMatchToCount = 4000;
$top = 10;
$offline = false;
$debug = true;
$verbose = false;
// Possible values :
// -1 : invalid
// 0 : public matchmaking
// 1 : practice
// 2 : Tournament
// 3 : Tutorial
// 4 : Co-op with bots
// 5 : Team match
// 6 : Solo queue
// 7 : Ranked
// 8 : Solo Mid 1v1
$lobbyType = array(7);
// List of heroes to display stats about by their Id
$heroFilter = array(2, 95, 35, 8, 25);
// Sorts :
// $AvailableSorts = array('Total', 'Against', 'With', 'AgainstWin', 'AgainstLost', 'WithWin', 'WithLost');
$sort = 'Total';
// order possible values = desc or null
$order = 'desc';


// Start of script
if ($offline)
  {
    if (!file_exists('historys/'.$steamId))
      {
	echo "Can't do offline without a history of the player requested\n";
	exit(0);
      }
    $json = json_decode(file_get_contents('historys/'.$steamId), true);
  }
else
  $json = fetchHistory();
$heroList = loadHeroesList();
echo "Getting your stats ready now...\n";
$heroStats = array();
$win = 0;
$loss = 0;
$numberMatchs = 0;
// Main loop
while (true)
  {
    if (isset($match))
      $json = fetchHistory($match['match_id']);
    if ($json['result']['results_remaining'] <= 0)
      break;
    foreach ($json['result']['matches'] as $match)
      {
	// filters
	if (isset($startTime) && $match['start_time'] < $startTime)
	  break 2;
	if (isset($endTime) && $match['start_time'] > $endTime)
	  continue;
	if (sizeof($lobbyType) > 0 && !in_array($match['lobby_type'], $lobbyType))
	  continue;
	if ($numberMatchs == $maxMatchToCount)
	  break 2;

	// get heroStats
	$array = parseMatch($match);
	if ($array == -1)
	  continue;
	$resultOfMatch = $array['win'] == true ? 'won' : 'lost';
	if ($verbose)
	  echo 'Match '.$match['match_id'].' you played '.$heroList[$array['myHeroId']].' and '.$resultOfMatch."\n";
	mergeArrays($heroStats, $array);
	if ($array['win'])
	  $win++;
	else
	  $loss++;
	$numberMatchs++;
	if ($verbose)
	  echo date('d/m/Y', $match['start_time'])."\n";
      }
    if ($offline)
      break;
  }
echo "$numberMatchs corresponding to your search!\n";
if ($numberMatchs == 0)
  exit(0);
// Sorting using $sort and $order
uasort($heroStats, 'heroStatsSort');
displayStats($heroStats);
// End of script

function displayStats($heroStats)
{
  global $top, $numberMatchs, $win, $loss, $heroFilter, $heroList;

  $count = 1;
  $winRate = round($win / $numberMatchs * 100, 2);
  $numberOfHeroesFaced = sizeof($heroStats);
  echo "You won $win and lost $loss games, for a winrate of $winRate%\n";
  echo "You faced a total of $numberOfHeroesFaced different heroes\n";
  foreach ($heroStats as $key => $value)
    {
      if (sizeof($heroFilter) > 0 && !in_array($key, $heroFilter))
	continue;
      $hero = $heroList[$key];
      $winWith = $heroStats[$key]['winWith'];
      $lostWith = $heroStats[$key]['lostWith'];
      $winAgainst = $heroStats[$key]['winAgainst'];
      $lostAgainst = $heroStats[$key]['lostAgainst'];
      $totalAgainst = $winAgainst + $lostAgainst;
      $totalWith = $winWith + $lostWith;
      $total = $totalAgainst + $totalWith;
      $rateAgainst = $totalAgainst > 0 ? round($winAgainst / $totalAgainst * 100, 2) : 0;
      $rateWith = $totalWith > 0 ? round($winWith / $totalWith * 100, 2) : 0;
      if ($totalAgainst > 0)
	echo "You faced $hero $totalAgainst times, lost $lostAgainst times, for a winning rate of $rateAgainst%\n";
      if ($totalWith > 0)
	echo "You played with a $hero $totalWith times, lost $lostWith times, for a winning rate of $rateWith%\n";
      if ($count == $top)
	break;
      $count++;
    }
}

function fetchHistory($startId = null)
{
  global $apikey;
  global $steamId;
  global $debug;

  $request = "https://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/V001/?account_id=$steamId&key=$apikey";
  if ($startId != null)
    $request .= "&start_at_match_id=$startId";
  if ($debug)
    echo $request."\n";
  echo "Fetching match history\n";
  $return = fetchUrl($request);
  file_put_contents('historys/'.$steamId, $return);
  $json = json_decode($return, true);
  if ($debug)
    echo $json['result']['num_results'].' results on '.$json['result']['total_results'].' '.$json['result']['results_remaining']." still results to fetch\n";
  return $json;
}

function mergeArrays(&$heroStats, $array)
{
  foreach ($array['radiant'] as $heroId)
    {
      if (!isset($heroStats[$heroId]))
	$heroStats[$heroId] = array('lostAgainst' => 0, 'winWith' => 0, 'lostWith' => 0, 'winAgainst' => 0);
      if ($array['win'] && $array['isRadiant'])
	$heroStats[$heroId]['winWith']++;
      else if (!$array['win'] && $array['isRadiant'])
	$heroStats[$heroId]['lostWith']++;
      else if ($array['win'] && !$array['isRadiant'])
	$heroStats[$heroId]['winAgainst']++;
      else if (!$array['win'] && !$array['isRadiant'])
	$heroStats[$heroId]['lostAgainst']++;
    }
  foreach ($array['dire'] as $heroId)
    {
      if (!isset($heroStats[$heroId]))
	$heroStats[$heroId] = array('lostAgainst' => 0, 'winWith' => 0, 'lostWith' => 0, 'winAgainst' => 0);
      if ($array['win'] && $array['isRadiant'])
	$heroStats[$heroId]['winAgainst']++;
      else if (!$array['win'] && $array['isRadiant'])
	$heroStats[$heroId]['lostAgainst']++;
      else if ($array['win'] && !$array['isRadiant'])
	$heroStats[$heroId]['winWith']++;
      else if (!$array['win'] && !$array['isRadiant'])
	$heroStats[$heroId]['lostWith']++;
    }
}

function parseMatch($match)
{
  global $steamId;

  $ret = array();
  $ret['radiant'] = array();
  for ($i = 0; $i < 5; $i++)
    {
      $ret['radiant'][] = $match['players'][$i]['hero_id'];
      if ($match['players'][$i]['account_id'] == $steamId)
	{
	  $ret['isRadiant'] = true;
	  $ret['myHeroId'] = $match['players'][$i]['hero_id'];
	}
    }
  $ret['dire'] = array();
  for ($i = 5; $i < 10; $i++)
    {
      $ret['dire'][] = $match['players'][$i]['hero_id'];
      if ($match['players'][$i]['account_id'] == $steamId)
	{
	  $ret['myHeroId'] = $match['players'][$i]['hero_id'];
	  $ret['isRadiant'] = false;
	}
    }
  if (($radiantWon = radiantWon($match['match_id'])) === -1)
    return -1;
  if (($ret['isRadiant'] && $radiantWon) || !$ret['isRadiant'] && !$radiantWon)
    $ret['win'] = true;
  else
    $ret['win'] = false;
  return $ret;
}

function fetchHeroesList()
{
  global $apikey;

  $request = "https://api.steampowered.com/IEconDOTA2_570/GetHeroes/v0001/?key=$apikey&language=en_us";
  $return = fetchUrl($request);
  file_put_contents('herolistjson', $return);
}

function radiantWon($matchId)
{
  if (!file_exists("matchs/$matchId"))
    $json = fetchMatchDetails($matchId);
  else
    $json = json_decode(file_get_contents("matchs/$matchId"), true);
  if (isset($json['result']['radiant_win']))
    return $json['result']['radiant_win'];
  return -1;
}

function fetchMatchDetails($id)
{
  global $apikey;

  echo "Fetching match $id...";
  $matchDetailRequest = "https://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/V001/?match_id=$id&key=$apikey";
  $rawJson = fetchUrl($matchDetailRequest);
  if ($rawJson === false || strlen($rawJson) == 0)
    {
      echo 'An error occured while fetching match '.$id."\n";
      return array();
    }
  file_put_contents("matchs/$id", $rawJson);
  echo "Done\n";
  return json_decode($rawJson, true);
}

function loadHeroesList()
{
  if (!file_exists('herolistjson'))
    fetchHeroesList();
  $heroList = file_get_contents('herolistjson');
  $json = json_decode($heroList, true);
  $heroList = array();
  foreach ($json['result']['heroes'] as $hero)
    $heroList[$hero['id']] = $hero['localized_name'];
  return $heroList;
}

?>
