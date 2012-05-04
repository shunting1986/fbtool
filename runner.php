#!/usr/bin/env php
<?php
require_once 'TokenGenerator.php';
require_once 'HTMLParser.php';
require_once 'CurlWrapper.php';
require_once 'Facebook.php';

// $obj = new HTMLParser;
$obj = new TokenGenerator;
# $obj = new Facebook;
$obj->entry();
?>
