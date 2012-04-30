#!/usr/bin/env php
<?php
require_once 'TokenGenerator.php';
require_once 'HTMLParser.php';

// $obj = new HTMLParser;
$obj = new TokenGenerator;
$obj->entry();
?>
