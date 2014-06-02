<?php

$day = 9;

$doc = new DOMDocument();
@$doc->loadHTMLFile('http://www.rolandgarros.com/en_FR/scores/completed_matches/day' . ($day + 5) . '.html');
$finder = new DOMXPath($doc);

$tables = $finder->query('//*[contains(@class, "scoringtable")]');
$events = $finder->query('//*[contains(@class, "eventinfo")]');

$type        = '';
$womSinRound = '';
$womDubRound = '';
$menSinRound = '';
$menDubRound = '';
$mixedRound  = '';

$count = 0;

$obj = array();

// parsing begins

foreach ($events as $event) {
	if (!$type && (strpos($event->textContent, 'Qualifications') !== false)) $type = 'Qualifications';

	if (strpos($event->textContent, 'Women\'s')	!== false ||
		strpos($event->textContent, 'Men\'s')	!== false ||
		strpos($event->textContent, 'Mixed')	!== false) {
		$count++;
	}

	if (!$womSinRound && (strpos($event->textContent, 'Women\'s Singles') !== false)) {
		$dash = strpos($event->textContent, '-');
		$womSinRound = substr($event->textContent, $dash + 2);
	} 
	else if (!$womDubRound && (strpos($event->textContent, 'Women\'s Doubles') !== false)) {
		$dash = strpos($event->textContent, '-');
		$womDubRound = substr($event->textContent, $dash + 2);
	}
	else if (!$menSinRound && (strpos($event->textContent, 'Men\'s Singles') !== false)) {
		$dash = strpos($event->textContent, '-');
		$menSinRound = substr($event->textContent, $dash + 2);
	}
	else if (!$menDubRound && (strpos($event->textContent, 'Men\'s Doubles') !== false)) {
		$dash = strpos($event->textContent, '-');
		$menDubRound = substr($event->textContent, $dash + 2);
	}
	else if (!$mixedRound && (strpos($event->textContent, 'Mixed') !== false)) {
		$dash = strpos($event->textContent, '-');
		$mixedRound = substr($event->textContent, $dash + 2);
	}
}

$obj['day']    = (int) substr($doc->getElementsByTagName('h1')->item(0)->nodeValue, -1);
$obj['events'] = $count;
$obj['type']   = ($type) ? $type : 'Tournament';

$obj['data'] = array(
	'womens' => array(
		'singles' => array(
			'round' => $womSinRound
		),
		'doubles' => array(
			'round' => $womDubRound
		)
	),
	'mens'   => array(
		'singles' => array(
			'round' => $menSinRound
		),
		'doubles' => array(
			'round' => $menDubRound
		)
	),
	'mixed'  => array(
		'round' => $mixedRound
	)
);

foreach ($tables as $node) {
	$type = '';
	
	if (strpos($node->firstChild->textContent, 'Women\'s Singles') !== false) {
		$gender = 'womens';
		$type   = 'singles';
		$s = getSingle($node);
	}  
	else if (strpos($node->firstChild->textContent, 'Women\'s Doubles') !== false) {
		$gender = 'womens';
		$type   = 'doubles';
		$d = getDouble($node);
	}  
	else if (strpos($node->firstChild->textContent, 'Men\'s Singles') !== false) {
		$gender = 'mens';
		$type   = 'singles';	
		$s = getSingle($node);
	}  
	else if (strpos($node->firstChild->textContent, 'Men\'s Doubles') !== false) {
		$gender = 'mens';
		$type   = 'doubles';
		$d = getDouble($node);
	}  
	else if (strpos($node->firstChild->textContent, 'Mixed') !== false) {
		$type   = 'mixed';	
		$m = getDouble($node);
	}  

	switch ($type) {
		case 'singles':
			$obj['data'][$gender][$type]['matches'][] = array(
				'status' => $s['status'],
				'court'  => $s['court'],
				'winner' => $s['winner'],
				'loser'  => $s['loser'],
				'sets'   => $s['sets']
			);
			break;

		case 'doubles':
			$obj['data'][$gender][$type]['matches'][] = array(
				'status'  => $d['status'],
				'court'   => $d['court'],
				'winners' => $d['winners'],
				'losers'  => $d['losers'],
				'sets'    => $d['sets']
			);
			break;

		case 'mixed':
			$obj['data']['mixed']['matches'][] = array(
				'status'  => $m['status'],
				'court'   => $m['court'],
				'winners' => $m['winners'],
				'losers'  => $m['losers'],
				'sets'    => $m['sets']
			);
			break;
	}
}

function getSingle($node) {
	$trimmedStatus = trim($node->childNodes->item(3)->childNodes->item(1)->textContent, chr(0xC2).chr(0xA0));
	$status  = ($trimmedStatus) ? $trimmedStatus : 'Completed';
	$court   = $node->childNodes->item(1)->textContent;
	$sets    = array();
	$player1 = $node->childNodes->item(3)->firstChild->childNodes;
	$player2 = $node->childNodes->item(3)->childNodes->item(2)->childNodes;

	// players
	// if player1 is the winner
	if (substr($player1->item(1)->getAttribute('class'), 8) === 'winner') {
		$p1win = true;

		$winner = array(
			'name'    => $player1->item(0)->getElementsByTagName('a')->item(0)->textContent,
			'country' => substr($player1->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3)
		);

		$loser = array(
			'name'    => $player2->item(0)->getElementsByTagName('a')->item(0)->textContent,
			'country' => substr($player2->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3)
		);
	} 
	else {
		$winner = array(
			'name'    => $player2->item(0)->getElementsByTagName('a')->item(0)->textContent,
			'country' => substr($player2->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3)
		);

		$loser = array(
			'name'    => $player1->item(0)->getElementsByTagName('a')->item(0)->textContent,
			'country' => substr($player1->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3)
		);
	}

	// sets
	for ($i = 3; $i < 8; $i++) {
		if ($player1->item($i)->childNodes->item(0)->textContent === '') break;
		
		$p1score = $player1->item($i)->childNodes->item(0)->textContent;
		$p2score = $player2->item($i)->childNodes->item(0)->textContent;
		
		if (isset($p1win)) {
			$sets[] = array(
				(strlen($p1score) > 1 && substr($p1score, 0, 1) !== '[') ? substr($p1score, 0, 1) . '(' . substr($p1score, 1) . ')' : $p1score,
				(strlen($p2score) > 1 && substr($p1score, 0, 1) !== '[') ? substr($p2score, 0, 1) . '(' . substr($p2score, 1) . ')' : $p2score
			);
		} else {
			$sets[] = array(
				(strlen($p2score) > 1 && substr($p1score, 0, 1) !== '[') ? substr($p2score, 0, 1) . '(' . substr($p2score, 1) . ')' : $p2score,
				(strlen($p1score) > 1 && substr($p1score, 0, 1) !== '[') ? substr($p1score, 0, 1) . '(' . substr($p1score, 1) . ')' : $p1score
			);
		}
	}

	return array(
		'status' => $status,
		'court'  => $court,
		'winner' => $winner, 
		'loser'  => $loser,
		'sets'   => $sets
	);
}

function getDouble($node) {
	$trimmedStatus = trim($node->childNodes->item(3)->childNodes->item(1)->textContent, chr(0xC2).chr(0xA0));
	$status  = ($trimmedStatus) ? $trimmedStatus : 'Completed';
	$court  = $node->childNodes->item(1)->textContent;
	$sets   = array();
	$team1 = $node->childNodes->item(3)->firstChild->childNodes;
	$team2 = $node->childNodes->item(3)->childNodes->item(2)->childNodes;

	// teams
	// if team 1 is the winner
	if (substr($team1->item(1)->getAttribute('class'), 8) === 'winner') {
		$t1win = true;

		$winners = array(
			'names' => array(
				$team1->item(0)->getElementsByTagName('a')->item(0)->textContent,
				$team1->item(0)->getElementsByTagName('a')->item(1)->textContent
			),
			'countries' => array(
				substr($team1->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3),
				substr($team1->item(0)->getElementsByTagName('span')->item(1)->textContent, 1, 3)
			)
		);

		$losers = array(
			'names' => array(
				$team2->item(0)->getElementsByTagName('a')->item(0)->textContent,
				$team2->item(0)->getElementsByTagName('a')->item(1)->textContent
			),
			'countries' => array(
				substr($team2->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3),
				substr($team2->item(0)->getElementsByTagName('span')->item(1)->textContent, 1, 3)
			)
		);
	} else {
		$winners = array(
			'names' => array(
				$team2->item(0)->getElementsByTagName('a')->item(0)->textContent,
				$team2->item(0)->getElementsByTagName('a')->item(1)->textContent
			),
			'countries' => array(
				substr($team2->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3),
				substr($team2->item(0)->getElementsByTagName('span')->item(1)->textContent, 1, 3)
			)
		);

		$losers = array(
			'names' => array(
				$team1->item(0)->getElementsByTagName('a')->item(0)->textContent,
				$team1->item(0)->getElementsByTagName('a')->item(1)->textContent
			),
			'countries' => array(
				substr($team1->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3),
				substr($team1->item(0)->getElementsByTagName('span')->item(1)->textContent, 1, 3)
			)
		);
	}

	// sets
	for ($i = 3; $i < 8; $i++) {
		if ($team1->item($i)->childNodes->item(0)->textContent === '') break;

		$t1score = $team1->item($i)->childNodes->item(0)->textContent;
		$t2score = $team2->item($i)->childNodes->item(0)->textContent;
		
		if (isset($t1win)) {
			$sets[] = array(
				(strlen($t1score) > 1 && substr($t1score, 0, 1) !== '[') ? substr($t1score, 0, 1) . '(' . substr($t1score, 1) . ')' : $t1score,
				(strlen($t2score) > 1 && substr($t1score, 0, 1) !== '[') ? substr($t2score, 0, 1) . '(' . substr($t2score, 1) . ')' : $t2score
			);
		} 
		else {
			$sets[] = array(
				(strlen($t2score) > 1 && substr($t1score, 0, 1) !== '[') ? substr($t2score, 0, 1) . '(' . substr($t2score, 1) . ')' : $t2score,
				(strlen($t1score) > 1 && substr($t1score, 0, 1) !== '[') ? substr($t1score, 0, 1) . '(' . substr($t1score, 1) . ')' : $t1score
			);
		}
	}

	return array(
		'status'  => $status,
 		'court'   => $court,
		'winners' => $winners, 
		'losers'  => $losers,
		'sets'    => $sets
	);
}

header('content-Type: application/json');
echo json_encode($obj, JSON_PRETTY_PRINT);

$fp = fopen('data/day' . $day . '.json', 'w');
fwrite($fp, json_encode($obj, JSON_PRETTY_PRINT));
fclose($fp);