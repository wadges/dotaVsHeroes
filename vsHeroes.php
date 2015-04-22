<?php

if (!(file_exists('matchs') && is_dir('matchs')))
  shell_exec('mkdir matchs');
if (!(file_exists('historys') && is_dir('historys')))
  shell_exec('mkdir historys');

// Settings - Filters

$apikey = trim(file_get_contents('steamapikey'));
$steamId = 109943; // main account laxa
// be carefull to use good date format according to your region configuration
$startTime = strtotime('04/01/2015');
$endTime = null;
$gameCountToStat = 0;
$maxMaxNumbers = 100;
$top = 10;


if (!file_exists('historys/'.$steamId))
  {
    $request = "https://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/V001/?account_id=$steamId&key=$apikey";
    echo "Request call : $request\n";
    $return = file_get_contents($request);
    file_put_contents('historys/'.$steamId, $return);
  }
else
  {
    $return = file_get_contents('historys/'.$steamId);
  }
$json = json_decode($return, true);
$heroList = loadHeroesList();
echo "Analysing your match history now...\n";
$heroStats = array();
$win = 0;
$loss = 0;
$numberMatchs = 0;
foreach ($json['result']['matches'] as $match)
  {
    if (isset($startTime) && $match['start_time'] < $startTime)
      break;
    if (sizeof($match['players']) != 10)
      {
	echo 'Match '.$match['match_id'].' is not a standart 5v5'."\n";
	continue;
      }
    $array = parseMatch($match);
    if ($array == -1)
      continue;
    $resultOfMatch = $array['win'] == true ? 'won' : 'lost';
    echo 'Match '.$match['match_id'].' you played '.$heroList[$array['myHeroId']].' and '.$resultOfMatch."\n";
    mergeArrays($heroStats, $array);
    if ($array['win'])
      $win++;
    else
      $loss++;
    $numberMatchs++;
  }
echo "$numberMatchs corresponding to your search!\n";
// Sorting by reverse order
uasort($heroStats, 'heroStatsSort');
// We make a nice printing !
$count = 1;
$winRate = round($win / $numberMatchs * 100, 2);
$numberOfHeroesFaced = sizeof($heroStats);
echo "You won $win and lost $loss games, for a winrate of $winRate\n";
echo "You faced a total of $numberOfHeroesFaced different heroes\n";
foreach ($heroStats as $key => $value)
  {
    $hero = $heroList[$key];
    $winWith = $heroStats[$key]['winWith'];
    $lostWith = $heroStats[$key]['lostWith'];
    $winAgainst = $heroStats[$key]['winAgainst'];
    $lostAgainst = $heroStats[$key]['lostAgainst'];
    $totalAgainst = $winAgainst + $lostAgainst;
    $totalWith = $winWith + $lostWith;
    $total = $totalAgainst + $totalWith;
    $rateAgainst = $totalAgainst > 0 ? round($lostAgainst / $totalAgainst * 100, 2) : 0;
    $rateWith = $totalWith > 0 ? round($winWith / $totalWith * 100, 2) : 0;
    if ($totalAgainst > 0)
      echo "You faced $hero $totalAgainst times, lost $lostAgainst times, for a loosing rate of $rateAgainst%\n";
    if ($totalWith > 0)
      echo "You played with a $hero $totalWith times, lost $lostWith times, for a winning rate of $rateWith%\n";
    if ($count == $top)
      break;
    $count++;
  }

function heroStatsSort($a, $b)
{
  // Add custom sort there
  $totalA = 0;
  $totalB = 0;
  foreach ($a as $value)
    $totalA += $value;
  foreach ($b as $value)
    $totalB += $value;
  // DESC sort
  return ($totalB - $totalA);
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
  $apikey = trim(file_get_contents('steamapikey'));
  $request = "https://api.steampowered.com/IEconDOTA2_570/GetHeroes/v0001/?key=$apikey&language=en_us";
  $return = file_get_contents($request);
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

  echo "Fetching match $id ... ";
  $matchDetailRequest = "https://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/V001/?match_id=$id&key=$apikey";
  $rawJson = file_get_contents($matchDetailRequest);
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
    {
      $heroList[$hero['id']] = $hero['localized_name'];
    }
  return $heroList;
}

?>
