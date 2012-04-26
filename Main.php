<?php
class Main {
  private $userName = '282749445@qq.com';
  private $passwd = '1fb4GW';
  private $appId = '264209280313575';
  private $appSecret = 'f52ed5d2409780bbaf02cedfae6a8542';

  private $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0) Gecko/20100101 Firefox/4.0';

  // example: https://www.facebook.com/dialog/oauth?client_id=264209280313575&redirect_uri=http://localhost:3080/ignores/show-request.php&scope=email&response_type=token
  // need extra options to access this url because of server side browser detection and redirection.
  // A workable url is:
  //   curl -L -A 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0) Gecko/20100101 Firefox/4.0' <this url>
  public function genClientTokenFetchUrl($appId, $redirectUrl, $scopes) {
    $baseUrl = 'https://www.facebook.com/dialog/oauth';
	$scopeStr = implode(',', $scopes);
	$params = array(
	  'client_id=' . $this->appId,
	  'redirect_uri=' . $redirectUrl,
	  'scope=' . $scopeStr,
	  'response_type=' . 'token',
	);
	return $baseUrl . '?' . implode('&', $params);
  }

  public function fetchPage($url) {
    $handle = curl_init($url); 
	$handle !== false or die('Fail to invoke curl_init');

	$options = array(
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_USERAGENT => $this->userAgent,
	  CURLOPT_FOLLOWLOCATION => true,
	);
	curl_setopt_array($handle, $options) !== false or die('Fail to invoke curl_setopt_array');
	$response = curl_exec($handle);
	$response !== false or die('Fail to invoke curl_exec, error message is ' . curl_error($handle) . "\n");
	curl_close($handle);

	return $response;
  }

  // TODO not complemented
  public function obtainToken($appId, $redirectUrl, $scopes) {
    $url = $this->genClientTokenFetchUrl($appId, $redirectUrl, $scopes);
	echo $this->fetchPage($url);
  }

  // TODO tempcode
  public function entry() {
    $redirectUrl = 'localhost/redirect';
	$scopes = array(
	  'email',
	);
    $this->obtainToken($this->appId, $redirectUrl, $scopes);
  }
}
?>
