<?php

class Parse {
  private static $matchTypes = array(
    "womSin" => "Women's Singles",
    "womDub" => "Women's Doubles",
    "menSin" => "Men's Singles",
    "menDub" => "Men's Doubles",
    "mixed"  => "Mixed Doubles"
  );

  //
  // Event Info
  //

  public static function getEventType($event) {
    if (strpos($event->textContent, 'Qualifications') !== false) {
      return 'Qualifications';
    }

    return 'Tournament';
  }

  public static function countEvents($count, $event) {
    $eventText = $event->textContent;
    $types     = array_values(self::$matchTypes);

    foreach ($types as $type) {
      if (strpos($eventText, $type) !== false) {
        $count++;
      }
    }

    return $count;
  }

  public static function getRound($event) {
    $dash  = strpos($event->textContent, '-');
    $round = substr($event->textContent, $dash + 2);

    return trim($round);
  }

  //
  // Match Info
  //

  private static function getName($side, $index = 0) {
    return $side->item(0)->getElementsByTagName('a')->item($index)->textContent;
  }

  private static function getCountry($side, $index = 0) {
    return substr($side->item(0)->getElementsByTagName('span')->item($index)->textContent, 1, 3);
  }

  private static function getMatchBase($node) {
    $matchNode = $node->childNodes->item(1)->firstChild->childNodes;

    $court  = $node->firstChild->firstChild->textContent;
    $status = $matchNode->item(1)->firstChild->textContent;
    $status = $status === 'Complete' ? 'Completed' : $status;

    $sideOne  = $matchNode->item(0)->childNodes;
    $sideTwo  = $matchNode->item(2)->childNodes;

    return array(
      'court'   => $court,
      'status'  => $status,
      'sideOne' => $sideOne,
      'sideTwo' => $sideTwo,
      'sets'    => array()
    );
  }

  private static function getMatchInfo($node, $match, $sideOne, $sideTwo, $isDoubles = false) {
    $sides = array();

    $terms = array(
      'win'  => $isDoubles ? 'winners' : 'winner',
      'loss' => $isDoubles ? 'losers'  : 'loser'
    );

    if (self::isWinner($match['sideOne']->item(1)->getAttribute('class'))) {
      $sideOneWin = true;
      $sides[$terms['win']]  = $sideOne;
      $sides[$terms['loss']] = $sideTwo;
    }
    else {
      $sideOneWin = false;
      $sides[$terms['win']]  = $sideTwo;
      $sides[$terms['loss']] = $sideOne;
    }

    $sides['sets'] = self::getSets($sideOneWin, $match['sideOne'], $match['sideTwo']);

    return array_merge($match, $sides);
  }

  private static function formatScore($score) {
    if (strlen($score) > 1) {
      $super = substr($score, 1);

      if (is_numeric($super)) {
        return substr($score, 0, 1) . '(' . $super . ')';
      }
    }

    return $score;
  }

  private static function getSets($sideOneWin, $sideOne, $sideTwo) {
    $sets = array();

    for ($i = 3; $i < 8; $i++) {
      $sideOneScore = $sideOne->item($i)->firstChild->textContent;
      $sideTwoScore = $sideTwo->item($i)->firstChild->textContent;

      // no more sets
      if ($sideOneScore === '' && $sideTwoScore === '') break;

      $sideOneScore = self::formatScore($sideOneScore);
      $sideTwoScore = self::formatScore($sideTwoScore);

      if ($sideOneWin) {
        $sets[] = array($sideOneScore, $sideTwoScore);
      }
      else {
        $sets[] = array($sideTwoScore, $sideOneScore);
      }
    }

    return $sets;
  }

  private static function isWinner($text) {
    return strpos($text, 'winner') !== false;
  }

  public static function isMatchType($text, $key) {
    $type = split(' - ', $text)[0];

    return $type == self::$matchTypes[$key];
  }

  public static function getSingle($node) {
    $match = self::getMatchBase($node);

    $sideOne = array(
      'name'    => self::getName($match['sideOne']),
      'country' => self::getCountry($match['sideOne'])
    );
    $sideTwo = array(
      'name'    => self::getName($match['sideTwo']),
      'country' => self::getCountry($match['sideTwo'])
    );

    return self::getMatchInfo($node, $match, $sideOne, $sideTwo);
  }

  public static function getDouble($node) {
    $match = self::getMatchBase($node);

    $sideOne = array(
      'names' => array(
        self::getName($match['sideOne']),
        self::getName($match['sideOne'], 1)
      ),
      'countries' => array(
        self::getCountry($match['sideOne']),
        self::getCountry($match['sideOne'], 1)
      )
    );
    $sideTwo = array(
      'names' => array(
        self::getName($match['sideTwo']),
        self::getName($match['sideTwo'], 1)
      ),
      'countries' => array(
        self::getCountry($match['sideTwo']),
        self::getCountry($match['sideTwo'], 1)
      )
    );

    return self::getMatchInfo($node, $match, $sideOne, $sideTwo, true);
  }
}
