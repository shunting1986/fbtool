<?php
class TokenGenerator {
  private $userName; 
  private $passwd; 

  private $appId; 
  private $appSecret; 
  private $siteUrl; 

  public function initConfig($configFile) {
    $fileContents = file_get_contents($configFile);
	$infoArr = json_decode($fileContents, true);
	is_array($infoArr) or die("Invalid config file format\n");
    
	foreach ($infoArr as $key => $value) {
	  $this->$key = $value;
	}
  }

  // example: https://www.facebook.com/dialog/oauth?client_id=264209280313575&redirect_uri=http://localhost:3080/ignores/show-request.php&scope=email&response_type=token
  // need extra options to access this url because of server side browser detection and redirection.
  // A workable url is:
  //   curl -k -H 'Accept-Language: en-us,en;q=0.5' -L -A 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0) Gecko/20100101 Firefox/4.0' <this url>
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

  private function loginFacebook($parser) {
    $loginInfo = $parser->getLoginInfo();
	$url = $loginInfo[0];
	$postData= $loginInfo[1];
	is_array($postData) or die("postData is not an array\n");

	$postData['email'] = $this->userName;
	$postData['pass'] = $this->passwd;
	return CurlWrapper::fetchPage($url, $postData);
  }

  private function isGrantPage($htmlStr) {
    return preg_match('/Go to App/', $htmlStr);
  }

  private function parseGoAgentErrorPage($htmlStr) {
    $matches = array();
    preg_match("/<p>GET '([^']*)'<\/p>/", $htmlStr, $matches) or die("Invalid GoAgent error page\n");
	$url = $matches[1];
	preg_match("/#(.*)/", $url, $matches) or die("Invalid redirect url\n");
	$paramStr = $matches[1];

    $info = array();
	$items = explode('&', $paramStr);
	foreach ($items as $item) {
	  $pieces = explode('=', $item);
	  $info[$pieces[0]] = $pieces[1];
	}

	// the info array usually contains 2 elements: access_token, expires_in
	return $info;
  }

  public function obtainToken($appId, $redirectUrl, $scopes) {
    $url = $this->genClientTokenFetchUrl($appId, $redirectUrl, $scopes);
	$response = CurlWrapper::fetchPage($url);

	$parser = new HTMLParser;
	$parser->loadStr($response);
    
	if (preg_match('/log in/i', $parser->getTitle())) {
	  $this->loginFacebook($parser);
	  return $this->obtainToken($appId, $redirectUrl, $scopes); // we have login, and try again
	}

	if ($this->isGrantPage($response)) { 
	  // grant
	  $parser->loadStr($response);
      $inputArr = $parser->find('input[value=Go to App]');
	  count($inputArr) == 1 or die("the number of input tag with value 'Go to App' is not exactly one\n");
	  $form = $parser->getAncestor($inputArr[0], 'form');
	  !is_null($form) or die("Can not found the surrounding form");

	  $formInfo = $parser->parseForm($form);
	  $url = $formInfo[0];
	  $postData = $formInfo[1];
	  is_array($postData) or die("No post data\n");
	  unset($postData['cancel_clicked']);

	  $response = CurlWrapper::fetchPage($url, $postData);
	}

    // if we reach here, we have already granted the app.
	// because we are using goagent, the proxy will return a error page, which contains the url that 
	// contains the token
    $tokenInfo = $this->parseGoAgentErrorPage($response);
	return $tokenInfo['access_token'];
  }

  public function entry() {
    $this->initConfig('config/credentials');
    $redirectUrl = $this->siteUrl . '/redirect';
	$scopes = array(
	  'email', 
	  'user_events',
	);
    echo $this->obtainToken($this->appId, $redirectUrl, $scopes) . "\n";
  }
}
?>
