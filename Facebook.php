<?php
class Facebook {
  public static $GRAPH_API_URL = "https://graph.facebook.com";
  public static $DATA_DIR = "data";

  public function graph($thing, $token, $nodecode = false) {
    $url = self::$GRAPH_API_URL . "/" . $thing . "&access_token=$token";
	$json = CurlWrapper::fetchPage($url);

	if (!$nodecode) {
	  $decoded = json_decode($json, true);
	  !is_null($decoded) or die("Invalid json returned: " . $json . "\n");
	} else {
	  $decoded = $json;
	}
	return $decoded;
  }

  public function getFriendList($token) {
    $decoded = $this->graph('me/friends', $token);

	$paging = $decoded['paging'];
	$nextPageRaw = CurlWrapper::fetchPage($paging['next'] . "&access_token=$token");
	$nextPageDecoded = json_decode($nextPageRaw, true);
	count($nextPageDecoded['data']) == 0 or die("Next page is not empty\n");
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

  public function entry() {
    $token = 'AAADwTARMqOcBAE3ZCMN8K9i0pxAEWHrCdbg6RxwZCO5fYNowmOCKZBqo1W9TC3EzQO82hEu9ppg8v1j0ZBOtnExXkjAOZCuMIjsJjs2OsqHARZAGvY1jLx';
	// echo CurlWrapper::fetchPage("https://graph.facebook.com/me/friends?access_token=$token");
	// var_dump($this->getFriendList($token));
	// $this->getFriendPicture('100002822665203', $token);
	$this->getAllFriendPicture($token);
  }
}
?>
