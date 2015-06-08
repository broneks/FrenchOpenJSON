<?php

include 'parse.php';

$day          = 15;
$isTournament = true;
$writePath    = 'data/2015/';
$filePrefix   = $isTournament ? 'day' : 'q';

//
// Load Document
//

$doc = new DOMDocument();
@$doc->loadHTMLFile('http://www.rolandgarros.com/en_FR/scores/completed_matches/day' . ($isTournament ? $day + 5 : $day) . '.html');

$finder = new DOMXPath($doc);

//
// Target Nodes
//

$tables = $finder->query('//*[contains(@class, "scoringtable")]');
$events = $finder->query('//*[contains(@class, "eventinfo")]');

//
// Variables
//

$eventType   = '';
$matchType   = '';
$womSinRound = '';
$womDubRound = '';
$menSinRound = '';
$menDubRound = '';
$mixedRound  = '';

$count = 0;

$output = array();

//
// Begin Parsing
//

foreach ($events as $event) {
  $eventSummary = $event->textContent;

  $count = Parse::countEvents($count, $event);

  if (!$eventType) {
    $eventType = Parse::getEventType($event);
  }

  if (!$womSinRound && Parse::isMatchType($eventSummary, 'womSin')) {
    $womSinRound = Parse::getRound($event);
  }
  else if (!$womDubRound && Parse::isMatchType($eventSummary, 'womDub')) {
    $womDubRound = Parse::getRound($event);
  }
  else if (!$menSinRound && Parse::isMatchType($eventSummary, 'menSin')) {
    $menSinRound = Parse::getRound($event);
  }
  else if (!$menDubRound && Parse::isMatchType($eventSummary, 'menDub')) {
    $menDubRound = Parse::getRound($event);
  }
  else if (!$mixedRound && Parse::isMatchType($eventSummary, 'mixed')) {;
    $mixedRound = Parse::getRound($event);
  }
}

//
// Create JSON Structure
//

$output['day']    = $day;
$output['events'] = $count;
$output['type']   = $eventType;

$output['data'] = array(
  'womens' => array(
    'singles' => array(
      'round' => $womSinRound
    ),
    'doubles' => array(
      'round' => $womDubRound
    )
  ),
  'mens' => array(
    'singles' => array(
      'round' => $menSinRound
    ),
    'doubles' => array(
      'round' => $menDubRound
    )
  ),
  'mixed' => array(
    'round' => $mixedRound
  )
);

foreach ($tables as $table) {
  $matchSummary = $table->firstChild->childNodes->item(1)->textContent;

  if (Parse::isMatchType($matchSummary, 'womSin')) {
    $gender    = 'womens';
    $matchType = 'singles';
    $match     = Parse::getSingle($table);
  }
  else if (Parse::isMatchType($matchSummary, 'womDub')) {
    $gender    = 'womens';
    $matchType = 'doubles';
    $match     = Parse::getDouble($table);
  }
  else if (Parse::isMatchType($matchSummary, 'menSin')) {
    $gender    = 'mens';
    $matchType = 'singles';
    $match     = Parse::getSingle($table);
  }
  else if (Parse::isMatchType($matchSummary, 'menDub')) {
    $gender    = 'mens';
    $matchType = 'doubles';
    $match     = Parse::getDouble($table);
  }
  else if (Parse::isMatchType($matchSummary, 'mixed')) {
    $matchType = 'mixed';
    $match     = Parse::getDouble($table);
  }
  else {
    // not a valid match type
    continue;
  }

  if ($matchType == 'doubles' || $matchType == 'mixed') {
    $matchArray = array(
      'status'  => $match['status'],
      'court'   => $match['court'],
      'winners' => $match['winners'],
      'losers'  => $match['losers'],
      'sets'    => $match['sets']
    );
  }
  else {
    $matchArray = array(
      'status' => $match['status'],
      'court'  => $match['court'],
      'winner' => $match['winner'],
      'loser'  => $match['loser'],
      'sets'   => $match['sets']
    );
  }


  if ($matchType == 'mixed') {
    $output['data']['mixed']['matches'][] = $matchArray;
  }
  else {
    $output['data'][$gender][$matchType]['matches'][] = $matchArray;
  }
}

//
// Write to JSON File
//

header('content-type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT);

$fp = fopen($writePath . $filePrefix . $day . '.json', 'w');
fwrite($fp, json_encode($output, JSON_PRETTY_PRINT));
fclose($fp);
