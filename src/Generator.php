<?php
namespace Nexendrie\SiteGenerator;

require_once(__DIR__ . "/functions.php");

use cebe\markdown\GithubMarkdown,
    Nette\Utils\Finder,
    Nette\Neon\Neon,
    Nette\Utils\FileSystem;

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
      $source = \findVendorDirectory() . "/../";
    }
    $this->setSource($source);
    if(is_null($output)) {
      $output = \findVendorDirectory() . "/../public/";
    }
    FileSystem::createDir($output);
    $this->setOutput($output);
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
      $this->source = realpath($source);
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
      $this->output = realpath($output);
    }
  }
  
  /**
   * @param string $filename
   * @return array
   */
  protected function getMeta($filename) {
    $metaFilename = str_replace(".md", ".neon", $filename);
    if(file_exists($metaFilename)) {
      $meta = Neon::decode(file_get_contents($metaFilename));
    } else {
      $meta = [
        "title" => ""
      ];
    }
    return $meta;
  }
  
  /**
   * @param string $filename
   * @return string
   */
  protected function createHtml($filename) {
    $parser = new GithubMarkdown;
    $parser->html5 = $parser->keepListStartNumber = $parser->enableNewlines = true;
    $source = $parser->parse(file_get_contents($filename));
    $meta = $this->getMeta($filename);
    $html = file_get_contents(__DIR__ . "/template.html");
    $meta["source"] = $source;
    foreach($meta as $key => $value) {
      $html = str_replace("%%$key%%", $value, $html);
    }
    return $html;
  }
  
  /**
   * Generate the site
   *
   * @return void
   */
  function generate() {
    FileSystem::delete($this->output);
    $files = Finder::findFiles("*.md")
      ->exclude("README.md")
      ->from($this->source)
      ->exclude("vendor", ".git", "tests");
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      $path = dirname($file->getRealPath());
      $path = str_replace($this->source, "", $path);
      $html = $this->createHtml($file->getRealPath());
      $filename = "$this->output$path/{$file->getBasename(".md")}.html";
      FileSystem::write($filename, $html);
      echo "Created $path/{$file->getBasename(".md")}.html\n";
    }
  }
}
?>