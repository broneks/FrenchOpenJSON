<?php

class Parse {
  private static $types = array(
    "womSin" => "Women's Singles",
    "womDub" => "Women's Doubles",
    "menSin" => "Men's Singles",
    "menDub" => "Men's Doubles",
    "mixed"  => "Mixed"
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
    $types     = array_values(self::$types);

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

    return $round;
  }

  //
  // Match Info
  //

  private static function getName($side) {
    return $side->item(0)->getElementsByTagName('a')->item(0)->textContent;
  }

  private static function getCountry($side) {
    return substr($side->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3);
  }

  private static function getBasicMatchInfo($node) {
    $matchNode = $node->childNodes->item(1)->firstChild->childNodes;
    
    $court  = $node->firstChild->firstChild->textContent;
    $status = $matchNode->item(1)->firstChild->textContent;
    $status = $status === 'Complete' ? 'Completed' : $status;

    $side1  = $matchNode->item(0)->childNodes;
    $side2  = $matchNode->item(2)->childNodes;

    return array(
      'court'  => $court,
      'status' => $status,
      'side1'  => $side1,
      'side2'  => $side2,
      'sets'   => array()
    );
  }

  private static function formatScore($score) {
    if (strlen($score) > 1) {
      return substr($score, 0, 1) . '(' . substr($score, 1) . ')';
    }

    return $score;
  }

  private static function getSets($side1Win, $side1, $side2) {
    $sets = array();

    for ($i = 3; $i < 8; $i++) {
      $side1Score = $side1->item($i)->firstChild->textContent;
      $side2Score = $side2->item($i)->firstChild->textContent;
      
      // no more sets
      if ($side1Score === '' && $side2Score === '') break;

      $side1Score = self::formatScore($side1Score);
      $side2Score = self::formatScore($side2Score);

      if ($side1Win) {
        $sets[] = array($side1Score, $side2Score);
      } 
      else {
        $sets[] = array($side2Score, $side1Score);
      }
    }

    return $sets;
  }

  public static function isMatchType($text, $key) {
    return strpos($text, self::$types[$key]) !== false;
  }

  public static function getSingle($node) {
    $match = self::getBasicMatchInfo($node);
    $sides = array();

    if (strpos($match['side1']->item(1)->getAttribute('class'), 'winner') !== false) {
      $side1Win = true;

      $sides['winner'] = array(
        'name'    => self::getName($match['side1']),
        'country' => self::getCountry($match['side1'])
      );
      $sides['loser'] = array(
        'name'    => self::getName($match['side2']),
        'country' => self::getCountry($match['side2'])
      );
    } 
    else {
      $side1Win = false;

      $sides['winner'] = array(
        'name'    => self::getName($match['side2']),
        'country' => self::getCountry($match['side2'])
      );
      $sides['loser']  = array(
        'name'    => self::getName($match['side1']),
        'country' => self::getCountry($match['side1'])
      );
    }

    $sides['sets'] = self::getSets($side1Win, $match['side1'], $match['side2']);

    return array_merge($match, $sides);
  }

  public static function getDouble($node) {
    //
    // TO BE COMPLETED
    //


    // $match = self::getBasicMatchInfo($node);
    // $sides = array();

    // if (strpos($match['side1']->item(1)->getAttribute('class'), 'winner') !== false) {
    //  $t1win = true;

    //  $winners = array(
    //    'names' => array(
    //      $team1->item(0)->getElementsByTagName('a')->item(0)->textContent,
    //      $team1->item(0)->getElementsByTagName('a')->item(1)->textContent
    //    ),
    //    'countries' => array(
    //      substr($team1->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3),
    //      substr($team1->item(0)->getElementsByTagName('span')->item(1)->textContent, 1, 3)
    //    )
    //  );

    //  $losers = array(
    //    'names' => array(
    //      $team2->item(0)->getElementsByTagName('a')->item(0)->textContent,
    //      $team2->item(0)->getElementsByTagName('a')->item(1)->textContent
    //    ),
    //    'countries' => array(
    //      substr($team2->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3),
    //      substr($team2->item(0)->getElementsByTagName('span')->item(1)->textContent, 1, 3)
    //    )
    //  );
    // } else {
    //  $winners = array(
    //    'names' => array(
    //      $team2->item(0)->getElementsByTagName('a')->item(0)->textContent,
    //      $team2->item(0)->getElementsByTagName('a')->item(1)->textContent
    //    ),
    //    'countries' => array(
    //      substr($team2->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3),
    //      substr($team2->item(0)->getElementsByTagName('span')->item(1)->textContent, 1, 3)
    //    )
    //  );

    //  $losers = array(
    //    'names' => array(
    //      $team1->item(0)->getElementsByTagName('a')->item(0)->textContent,
    //      $team1->item(0)->getElementsByTagName('a')->item(1)->textContent
    //    ),
    //    'countries' => array(
    //      substr($team1->item(0)->getElementsByTagName('span')->item(0)->textContent, 1, 3),
    //      substr($team1->item(0)->getElementsByTagName('span')->item(1)->textContent, 1, 3)
    //    )
    //  );
    // }
  }
}