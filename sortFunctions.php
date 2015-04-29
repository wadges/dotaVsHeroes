<?php

function heroStatsSort($a, $b)
{
  global $sort;
  global $order;

  $totalA = 0;
  $totalB = 0;
  $func = 'sorting'.$sort;
  $func($a, $b, $totalA, $totalB);
  if ($order === 'desc')
    return ($totalB - $totalA);
  else
    return ($totalA - $totalB);
}

function sortingTotal($a, $b, &$totalA, &$totalB)
{
  foreach ($a as $value)
    $totalA += $value;
  foreach ($b as $value)
    $totalB += $value;
}

function sortingAgainst($a, $b, &$totalA, &$totalB)
{
  $totalA = $a['winAgainst'] + $a['lostAgainst'];
  $totalB = $b['winAgainst'] + $b['lostAgainst'];
}

function sortingWith($a, $b, &$totalA, &$totalB)
{
  $totalA = $a['winWith'] + $a['lostWith'];
  $totalB = $b['winWith'] + $b['lostWith'];
}

function sortingAgainstWin($a, $b, &$totalA, &$totalB)
{
  $totalA = $a['winAgainst'];
  $totalB = $b['winAgainst'];
}

function sortingAgainstLost($a, $b, &$totalA, &$totalB)
{
  $totalA = $a['lostAgainst'];
  $totalB = $b['lostAgainst'];
}

function sortingWithWin($a, $b, &$totalA, &$totalB)
{
  $totalA = $a['winWith'];
  $totalB = $b['winWith'];
}

function sortingLostWith($a, $b, &$totalA, &$totalB)
{
  $totalA = $a['lostWith'];
  $totalB = $b['lostWith'];
}

?>
