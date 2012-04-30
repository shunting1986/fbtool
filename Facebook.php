<?php
class Facebook {
  public static $GRAPH_API_URL = "https://graph.facebook.com";

  public function graph($thing, $token) {
    $url = self::$GRAPH_API_URL . "/" . $thing . "&access_token=$token";
	$json = CurlWrapper::fetchPage($url);
	$decoded = json_decode($json, true);
	!is_null($decoded) or die("Invalid json returned\n");
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

  public function entry() {
    $token = 'AAADwTARMqOcBAE3ZCMN8K9i0pxAEWHrCdbg6RxwZCO5fYNowmOCKZBqo1W9TC3EzQO82hEu9ppg8v1j0ZBOtnExXkjAOZCuMIjsJjs2OsqHARZAGvY1jLx';
	// echo CurlWrapper::fetchPage("https://graph.facebook.com/me/friends?access_token=$token");
	var_dump($this->getFriendList($token));
  }
}
?>
