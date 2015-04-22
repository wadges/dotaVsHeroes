<?php

if (!(file_exists('matchs') && is_dir('matchs')))
  shell_exec('mkdir matchs');
if (!(file_exists('historys') && is_dir('historys')))
  shell_exec('mkdir historys');

// Settings

$apikey = trim(file_get_contents('steamapikey'));
$steamId = 109943; // 64 BITS OR 32 BITS ID
$matchnumbers = 100;
$startTime = strtotime('01/04/2015');
$enddate = '';
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
$numberMatchs = 0;
$result = array();
$heroList = loadHeroesList();
echo "Analysing your match history now...\n";
$heroStats = array();
$win = 0;
$loss = 0;
foreach ($json['result']['matches'] as $match)
  {
    if ($match['start_time'] < $startTime)
      break;
    if (sizeof($match['players']) != 10)
      {
	echo 'Match '.$match['match_id'].' is not a standart 5v5'."\n";
	continue;
      }
    $array = parseMatch($match);
    $resultOfMatch = $array['win'] == true ? 'won' : 'lost';
    echo 'Match '.$match['match_id'].' is a match, you played '.getHeroNameById($array['myHeroId']).' and '.$resultOfMatch."\n";
    mergeArrays($heroStats, $array);
    if ($array['win'])
      $win++;
    else
      $loss++;
    $numberMatchs++;
  }
echo "$numberMatchs corresponding to your search!\n";
// Sorting by reverse order
arsort($heroStats);
// We make a nice printing !
$count = 1;
$winRate = $win / $numberMatchs * 100;
$numberOfHeroesFaced = sizeof($heroStats);
echo "You won $win and lost $loss games, for a winrate of $winRate\n";
echo "Now the heroes stats :\n";
echo "You faced a total of $numberOfHeroesFaced different heroes\n";
foreach ($heroStats as $key => $value)
  {
    $hero = getHeroNameById($key);
    $win = $heroStats[$key]['winWith'];
    $loss = $heroStats[$key]['lostAgainst'];
    $total = $win + $loss;
    echo "You faced $hero $ times gainst $hero\n";
    if ($count == $top)
      break;
    $count++;
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
	$heroStats[$heroId] = array('lostAgainst' => 0, 'winWith' => 0);
      if ($array['win'] && $array['isRadiant'])
	$heroStats[$heroId]['winWith']++;
      else
	$heroStats[$heroId]['lostAgainst']++;
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
  if ($ret['radiant'] && radiantWon($match['match_id']))
    $ret['win'] = true;
  else
    $ret['win'] = false;
  return $ret;
}

function getYourHeroId($match)
{
  global $steamId;

  foreach ($match['players'] as $player)
    {
      if ($player['account_id'] == $steamId)
	return $player['hero_id'];
    }
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
  return $json['result']['radiant_win'];
}

function fetchMatchDetails($id)
{
  global $apikey;

  echo "Fetching match $id details... ";
  $matchDetailRequest = "https://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/V001/?match_id=$id&key=$apikey";
  $rawJson = file_get_contents($matchDetailRequest);
  file_put_contents("matchs/$id", $rawJson);
  echo "Done\n";
  return json_decode($rawJson);
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

function getHeroNameById($id)
{
  global $heroList;

  return ($heroList[$id]);
}

function isRadiant($slot)
{
  return in_array($slot, array(0, 1, 2, 3, 4));
}

?>
