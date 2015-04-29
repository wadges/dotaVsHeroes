<?php

$lastRequestTime = 0;

function fetchUrl($uri, $try = 1)
{
  global $lastRequestTime;

  if ($try === 4)
    return null;
  $handle = curl_init();

  curl_setopt($handle, CURLOPT_URL, $uri);
  curl_setopt($handle, CURLOPT_POST, false);
  curl_setopt($handle, CURLOPT_BINARYTRANSFER, false);
  curl_setopt($handle, CURLOPT_HEADER, true);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

  $timeElapsed = microtime(true) - $lastRequestTime;
  if ($timeElapsed < 1000000)
    usleep(1000000 - $timeElapsed);

  $lastRequestTime = microtime(true);
  $response = curl_exec($handle);
  $hlength  = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
  $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
  $body     = substr($response, $hlength);

  if ($httpCode === 503)
    {
      if ($try === 1)
	echo "\n";
      echo "Received 503 error on try $try for $uri\n";
      return fetchUrl($uri, ++$try);
    }
  return $body;
}

?>
