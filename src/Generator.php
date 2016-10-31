<?php
namespace Nexendrie\SiteGenerator;

require_once(__DIR__ . "/functions.php");

use cebe\markdown\GithubMarkdown,
    Nette\Utils\Finder;

/**
 * Generator
 *
 * @author Jakub Konečný
 * @property string $source
 * @property string $output
 */
class Generator {
  use \Nette\SmartObject;
  
  /** @var string */
  protected $source;
  /** @var string */
  protected $output;
  
  function __construct($source = NULL, $output = NULL) {
    if(is_null($source)) {
      $source = realpath(\findVendorDirectory() . "/../");
    }
    $this->setSource($source);
    if(is_null($output)) {
      $output = \findVendorDirectory() . "/../public/";
    }
    @mkdir($output, 0777, true);
    $this->setOutput(realpath($output));
  }
  
  /**
   * @return string
   */
  function getSource() {
    return $this->source;
  }
  
  /**
   * @param string $source
   */
  function setSource($source) {
    if(is_string($source) AND is_dir($source)) {
      $this->source = $source;
    }
  }
  
  /**
   * @return string
   */
  function getOutput() {
    return $this->output;
  }
  
  /**
   * @param string $output
   */
  function setOutput($output) {
    if(is_string($output)) {
      $this->output = $output;
    }
  }
  
  /**
   * @param $filename
   * @return string
   */
  protected function createHtml($filename) {
    $parser = new GithubMarkdown;
    $parser->html5 = $parser->keepListStartNumber = $parser->enableNewlines = true;
    $source = $parser->parse(file_get_contents($filename));
    $html = "<!DOCTYPE HTML>
<html>
<head>
  <meta charset=\"utf-8\">
</head>
<body>
$source
</body>
</html>";
    return $html;
  }
  
  /**
   * Generate the site
   *
   * @return void
   */
  function generate() {
    \rrmdir($this->output);
    mkdir($this->output);
    $files = Finder::findFiles("*.md")
      ->exclude("README.md")
      ->from($this->source)
      ->exclude("vendor", ".git", "tests", "public");
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      $path = dirname($file->getRealPath());
      $path = str_replace($this->source, "", $path);
      $html = $this->createHtml($file->getRealPath());
      @mkdir("$this->output$path", 0777, true);
      file_put_contents("$this->output$path/{$file->getBasename(".md")}.html", $html);
      echo "Created $path/{$file->getBasename(".md")}.html\n";
    }
  }
}
?>