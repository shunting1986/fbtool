<?php
class Facebook {
  public static $GRAPH_API_URL = "https://graph.facebook.com";
  public static $DATA_DIR = "data";

  private function graphInternal($thing, $token, $postData, $nodecode) {
    $url = self::$GRAPH_API_URL . "/" . $thing . "&access_token=$token";
	$json = CurlWrapper::fetchPage($url, $postData);

	if (!$nodecode) {
	  $decoded = json_decode($json, true);
	  !is_null($decoded) or die("Invalid json returned: " . $json . "\n");
	} else {
	  $decoded = $json;
	}
	return $decoded;
  }

  public function graph($thing, $token, $nodecode = false) {
    return $this->graphInternal($thing, $token, null, $nodecode);
  }

  public function graphPost($thing, $token, $postData, $nodecode = false) {
    return $this->graphInternal($thing, $token, $postData, $nodecode);
  }

  public function getFriendList($token) {
    $decoded = $this->graph('me/friends', $token);

	$paging = $decoded['paging'];
	$nextPageRaw = CurlWrapper::fetchPage($paging['next'] . "&access_token=$token");
	$nextPageDecoded = json_decode($nextPageRaw, true);
	count($nextPageDecoded['data']) == 0 or die("Next page is not empty\n");
	return $decoded['data'];
  }
  
  public function getGraphApiVictims($token) {
    $friendlistId = '230022850437022';
	$decoded = $this->graph("$friendlistId/members", $token);
	// TODO handle paging
	return $decoded['data'];
  }

  public function getAllEvents($token) {
    $decoded = $this->graph('me/events', $token);
	// TODO should we consider paging here?
	return $decoded['data'];
  }

  public function pickOneLuckyEvent($token) {
    $events = $this->getAllEvents($token);
	$luckydog = $events[0];
	return $luckydog['id'];
  }

  public function getEventProfile($eventId, $token) {
    return $this->graph("$eventId", $token);
  }

  public function getAllEventGuests($eventId, $token) {
    $decoded = $this->graph("$eventId/invited", $token);
	// TODO process paging
	return $decoded['data'];
  }

  public function getFriendPicture($id, $token) {
    $pic = $this->graph("$id/picture", $token, true);
	$fh = fopen(self::$DATA_DIR . "/$id.pic", "w"); 
	$fh !== false or die("Fail to open file for writing\n");
	fwrite($fh, $pic) !== false or die("Fail to write file\n");
	fclose($fh);
  }

  public function getAllFriendPicture($token) {
    $list = $this->getFriendList($token);
	foreach ($list as $friend) {
	  echo "= fetch picture of " . $friend['name'] . "\n";
	  $this->getFriendPicture($friend['id'], $token);
	}
  }

  public function createEvent($eventInfo, $token) {
    return $this->graphPost('me/events', $token, $eventInfo);
  }

  public function eventInvite($eventId, $guestId, $token) {
    die("eventInvite not implemented\n"); // TODO
  }

  // for illustration purpose
  public function createDumbEvent($token) {
    return $this->createEvent(array(
	  'name' => 'createDumbEvent',
	  'start_time' => '2012-12-20 09:09:09+08:00'
	), $token);
  }

  public function entry() {
    $token = 'AAADwTARMqOcBADXzIl6gGwLVlWtNVOonPKFnGTSGdZBSDLZB6GiSnGmiLcXBRCZCGDVIZBrmj5Iaembtp9CDzqZASzYQvWKGT91sX8QZCdyiz9Pfgq3cVa';
	$me = $this->graph('me', $token);
	if (isset($me['error'])) { 
	  $tokenGen = new TokenGenerator;
	  $tokenInfo = $tokenGen->obtainTokenWrapper();
	  var_dump($tokenInfo);
	  $token = $tokenInfo['access_token'];
	}

	// var_dump($this->graph('me/permissions', $token));
	// echo CurlWrapper::fetchPage("https://graph.facebook.com/me?access_token=$token");
	// var_dump($this->getFriendList($token));
	// $this->getFriendPicture('100002822665203', $token);
	// $this->getAllFriendPicture($token);
	// var_dump($this->createDumbEvent($token));
	// var_dump($this->getAllEvents($token));
	// var_dump($this->getAllEventGuests($this->pickOneLuckyEvent($token), $token));
	// var_dump($this->getGraphApiVictims($token)); // cheng yuan: 100002760144965
	$this->eventInvite(269618983134319, 100002760144965, $token);
  }
}
?>
