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
  
  function __construct() {
    $this->source = realpath(\findVendorDirectory() . "/../");
    @mkdir(\findVendorDirectory() . "/../public/", 0777, true);
    $this->output = realpath(\findVendorDirectory() . "/../public/");
  }
  
  /**
   * @return string
   */
  function getSource() {
    return $this->source;
  }
  
  /**
   * @return string
   */
  function getOutput() {
    return $this->output;
  }
  
  /**
   * Generate the site
   *
   * @return void
   */
  function generate() {
    \rrmdir($this->output);
    mkdir($this->output);
    $parser = new GithubMarkdown;
    $parser->html5 = $parser->keepListStartNumber = $parser->enableNewlines = true;
    $files = Finder::findFiles("*.md")
      ->exclude("README.md")
      ->from($this->source)
      ->exclude("vendor", ".git", "tests", "public");
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      $path = dirname($file->getRealPath());
      $path = str_replace($this->source, "", $path);
      $html = $parser->parse(file_get_contents($file->getRealPath()));
      @mkdir("$this->output$path", 0777, true);
      file_put_contents("$this->output$path/{$file->getBasename(".md")}.html", $html);
      echo "Created $path/{$file->getBasename(".md")}.html\n";
    }
  }
}
?>