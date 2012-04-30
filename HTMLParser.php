<?php

require_once 'simplehtmldom/simple_html_dom.php';

class HTMLParser {
  private $shd;

  public function __construct() {
    $this->shd = new simple_html_dom();
  }


  public function loadStr($str) {
    $this->shd->load($str);
  }
  
  public function getTitle() {
    $titleArr = $this->shd->find('title');
	count($titleArr) <= 1 or die('The number of title is more than 1' . "\n");
	if (count($titleArr) == 0) {
	  return "";
	} else {
	  return $titleArr[0]->innertext();
	}
  }

  public function getLoginInfo() {
    $formArr = $this->shd->find('form');
	count($formArr) == 1 or die("The number of form tag is not 1\n");

	return $this->parseForm($formArr[0]);
  }

  public function parseForm($form) {
    $actionPage = $form->attr['action'];
    $formMethod = $form->attr['method'];
	$formMethod == 'post' or die("The method of form is not post\n");

    $postData = array();
	foreach ($form->find('input') as $input) {
	  $postData[$input->attr['name']] = $input->attr['value'];
	}
	return array($actionPage, $postData);
  }

  public function find($selector) {
    return $this->shd->find($selector);
  }

  public function getAncestor($node, $tag) {
    while (!is_null($node) && $node->tag != $tag) {
	  $node = $node->parent();
	}
	return $node;
  }

  public function travel($node = null, $indent = 0) {
    if (is_null($node)) {
	  $node = $this->shd->root;
	}

    echo str_repeat('-', $indent);
    echo $node->tag . "\n";
    
	foreach ($node->children as $child) {
	  $this->travel($child, $indent + 2);
	}
  }

  // TODO
  private $simpleHTML = '<html><body>Hello!</body></html>';
  public function entry() {
    $file = file_get_contents('/tmp/zz.html');
    // $this->shd->load($this->simpleHTML);
    $this->shd->load($file);

	/*
	$this->travel($this->shd->root);
	exit;
	*/

	$forms = $this->shd->find('form');
	isset($forms[0]) or die('no form tag found');
	$this->travel($forms[0]);

	$this->postData['email'] = '282749445@qq.com';
    $this->postData['pass'] = '1fb4GW';
	/*
	var_dump($this->postData);
	var_dump($this->formMethod);
	var_dump($this->actionPage);
	*/

	$main = new Main;
	echo $main->fetchPage($this->actionPage, $this->postData);
  }
}
?>
