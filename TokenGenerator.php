<?php
class TokenGenerator {
  private $userName; 
  private $passwd; 

  private $appId; 
  // private $appSecret; 
  private $siteUrl; 

  private $proxy = null;

  public function initConfig($configFile) {
    $fileContents = file_get_contents($configFile);
	$infoArr = json_decode($fileContents, true);
	is_array($infoArr) or die("Invalid config file format\n");
    
	foreach ($infoArr as $key => $value) {
	  $this->$key = $value;
	}

	if (!is_null($this->proxy)) {
	  putenv("http_proxy=" . $this->proxy) or die("Fail to set http_proxy\n");
	  putenv("https_proxy=" . $this->proxy) or die("Fail to set https_proxy\n");
	}
  }

  // example: https://www.facebook.com/dialog/oauth?client_id=264209280313575&redirect_uri=http://localhost:3080/ignores/show-request.php&scope=email,create_event&response_type=token
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

  private function isGotoAppPage($htmlStr) {
    return preg_match('/Go to App/', $htmlStr);
  }

  private function isAllowPage($htmlStr) {
    return preg_match('/would (also )?like permission to/', $htmlStr)
	  && preg_match('/Allow/', $htmlStr)
	  && preg_match('/Skip/', $htmlStr);
  }

  private function parseTokenInfoFromUrl($url) {
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

  private function parseGoAgentErrorPage($htmlStr) {
    $matches = array();
    preg_match("/<p>GET '([^']*)'<\/p>/", $htmlStr, $matches) or die("Invalid GoAgent error page: " . $htmlStr . "\n");
	$url = $matches[1];

	return $this->parseTokenInfoFromUrl($url);
  }

  private function parseCurlLogFile($redirectUrl, $filename) {
    $fcont = file_get_contents($filename);
	$fcont !== false or die("Fail to read the curl log file\n");
	$matches = array();
	if (preg_match("!> GET ($redirectUrl.*#.*access_token=.*) HTTP.1[.][01]!", $fcont, $matches)) {
	  return $this->parseTokenInfoFromUrl($matches[1]);
	} else {
	  die("The curl log file does not contains the access token\n");
	}
  }

  private function commitFormAllow($parser, $allowValue, $cancelNameArr, $verbose = false) {
    $inputArr = $parser->find('input[value=' . $allowValue . ']');
	count($inputArr) == 1 or die("the number of input tag with value 'Go to App' is not exactly one\n");
	$form = $parser->getAncestor($inputArr[0], 'form');
	!is_null($form) or die("Can not found the surrounding form");

	$formInfo = $parser->parseForm($form);
	$url = $formInfo[0];
	$postData = $formInfo[1];
	is_array($postData) or die("No post data\n");

	foreach ($cancelNameArr as $cancelName) {
	  unset($postData[$cancelName]);
	}

	if ($verbose) {
	  echo "= commitFormAllow, url = $url\nPost data is:\n";
	  var_dump($postData);
	}
	$response = CurlWrapper::fetchPage($url, $postData);
    return $response;
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

	if ($this->isGotoAppPage($response)) {  // first time see this app
	  // grant
	  $parser->loadStr($response);
      $response = $this->commitFormAllow($parser, 'Go to App', array('cancel_clicked'));
	}

	if ($this->isAllowPage($response)) { // this app requires more permissions 
	  $parser->loadStr($response);
	  // $response = $this->commitFormAllow($parser, 'Allow', 'skip_clicked', true);
	  $response = $this->commitFormAllow($parser, 'Allow', array('cancel_clicked', 'skip_clicked'), false); 
	}

    // if we reach here, we have already granted the app.
	// because we are using goagent, the proxy will return a error page, which contains the url that 
	// contains the token

    # // the proxy is goagent and the site url is unreachable
    # $tokenInfo = $this->parseGoAgentErrorPage($response);
	#
	$tokenInfo = $this->parseCurlLogFile($redirectUrl, CurlWrapper::$stderrFile);
    
	# echo "Final resp is: $response\n";
	return $tokenInfo;
  }

  public function obtainTokenWrapper() {
    $this->initConfig('config/config');
    $redirectUrl = $this->siteUrl . '/redirect';
	$scopes = array(
	  'email', 
	  'user_events',
	  'create_event',
	  'read_friendlists',
	  'publish_stream',
	);
    return $this->obtainToken($this->appId, $redirectUrl, $scopes);
  }

  public function entry() {
    if (!is_dir("temp")) { // the temp dir is needed to store the log/cookie files and so on
	  mkdir("temp") or die("Fail to create the 'temp' dir\n");
	}

    $tokenInfo = $this->obtainTokenWrapper();
	echo $tokenInfo['access_token'] . "\n";
  }
}
?>
