<?php

function loadHeroesList()
{
  if (!file_exists('json/heroList.json'))
    fetchHeroesList();
  $heroList = file_get_contents('json/heroList.json');
  $json = json_decode($heroList, true);
  $heroList = array();
  foreach ($json['result']['heroes'] as $hero)
    $heroList[$hero['id']] = $hero['localized_name'];
  return $heroList;
}

function fetchHeroesList()
{
  global $apikey;

  echo "Fetching heroes list...";
  $request = "https://api.steampowered.com/IEconDOTA2_570/GetHeroes/v0001/?key=$apikey&language=en_us";
  $data = fetchUrl($request);
  file_put_contents('json/heroList.json', $data);
  echo "Done\n";
}

?>
