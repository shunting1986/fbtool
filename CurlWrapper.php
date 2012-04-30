<?php
class CurlWrapper {
  public static $headerLoc = "temp/curl.httpheaders";
  public static $cookieLoc = "temp/curl.cookies";

  private static $curlVerbose = false;
  private static $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0) Gecko/20100101 Firefox/4.0';

  public static function fetchPage($url, $postData = null) {
    $handle = curl_init($url); 
	$handle !== false or die('Fail to invoke curl_init');

    $fh = fopen(self::$headerLoc, "w");
	$fh !== false or die('Fail to open the file to store http headers');
	$options = array(
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_USERAGENT => self::$userAgent,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_SSL_VERIFYPEER => false,
	  CURLOPT_WRITEHEADER => $fh,
	  CURLOPT_HTTPHEADER => array(
	    'Accept-Language: en-us,en;q=0.5', // solve the chinese problem
	  ),
	  CURLOPT_COOKIEJAR => self::$cookieLoc,
	  CURLOPT_COOKIEFILE => self::$cookieLoc,
	  CURLOPT_VERBOSE => self::$curlVerbose,
	);

	if (is_array($postData)) {
	  $options[CURLOPT_POST] = true;
	  $options[CURLOPT_POSTFIELDS] = $postData;
	}

	curl_setopt_array($handle, $options) !== false or die('Fail to invoke curl_setopt_array');
	$response = curl_exec($handle);
	$response !== false or die('Fail to invoke curl_exec, error message is ' . curl_error($handle) . "\n");
	curl_close($handle);
	fclose($fh) or die('Fail to close the file that stores http headers');
	return $response;
  }
}
?>
