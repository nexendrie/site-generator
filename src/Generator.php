<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

require_once(__DIR__ . "/functions.php");

use cebe\markdown\GithubMarkdown,
    Nette\Utils\Finder,
    Nette\Neon\Neon,
    Nette\Utils\FileSystem,
    Symfony\Component\OptionsResolver\OptionsResolver,
    Nette\Utils\Arrays;

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
  
  public function __construct(string $source = NULL, string $output = NULL) {
    if(is_null($source)) {
      $source = findVendorDirectory() . "/../";
    }
    $this->setSource($source);
    if(is_null($output)) {
      $output = findVendorDirectory() . "/../public/";
    }
    FileSystem::createDir($output);
    $this->setOutput($output);
  }
  
  public function getSource(): string {
    return $this->source;
  }
  
  public function setSource(string $source) {
    if(is_string($source) AND is_dir($source)) {
      $this->source = realpath($source);
    }
  }
  
  public function getOutput(): string {
    return $this->output;
  }
  
  public function setOutput(string $output) {
    if(is_string($output)) {
      $this->output = realpath($output);
    }
  }
  
  protected function getMeta(string $filename): array {
    $resolver = new OptionsResolver;
    $resolver->setDefaults([
      "title" => "",
      "styles" => [],
      "scripts" => [],
    ]);
    $isArrayOfStrings = function(array $value) {
      foreach($value as $item) {
        if(!is_string($item)) {
          return false;
        }
      }
      return true;
    };
    $resolver->setAllowedTypes("title", "string");
    $resolver->setAllowedTypes("styles", "array");
    $resolver->setAllowedValues("styles", $isArrayOfStrings);
    $resolver->setAllowedTypes("scripts", "array");
    $resolver->setAllowedValues("scripts", $isArrayOfStrings);
    $metaFilename = str_replace(".md", ".neon", $filename);
    $meta = [];
    if(file_exists($metaFilename)) {
      $meta = Neon::decode(file_get_contents($metaFilename));
    }
    return $resolver->resolve($meta);
  }
  
  protected function processAssets(array &$meta, string &$html, string $basePath): void {
    foreach($meta["styles"] as $index => $style) {
      if(!file_exists("$basePath/$style")) {
        unset($meta["styles"][$index]);
        continue;
      }
    }
    foreach($meta["scripts"] as $index => $script) {
      if(!file_exists("$basePath/$script")) {
        unset($meta["scripts"][$index]);
        continue;
      }
    }
    if(!count($meta["styles"])) {
      unset($meta["styles"]);
      $html = str_replace("
  %%styles%%", "", $html);
    }
    if(!count($meta["scripts"])) {
      unset($meta["scripts"]);
      $html = str_replace("
  %%scripts%%", "", $html);
    }
    if(!isset($meta["styles"]) AND !isset($meta["scripts"])) {
      return;
    }
    $assets = array_merge(Arrays::get($meta, "styles", []), Arrays::get($meta, "scripts", []));
    foreach($assets as $asset) {
      $path = str_replace($this->source, "", realpath($asset));
      $target = "$this->output$path/$asset";
      FileSystem::copy("$basePath/$asset", $target);
    }
    foreach($meta["styles"] as $index => $style) {
      $meta["styles"][$index] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$style\">";
    }
    foreach($meta["scripts"] as $index => $script) {
      $meta["scripts"][$index] = "<script type=\"text/javascript\" src=\"$script\"></script>";
    }
    $meta["styles"] = implode("\n  ", $meta["styles"]);
    $meta["scripts"] = implode("\n  ", $meta["scripts"]);
  }
  
  protected function createHtml(string $filename): string {
    $parser = new GithubMarkdown;
    $parser->html5 = $parser->keepListStartNumber = $parser->enableNewlines = true;
    $source = $parser->parse(file_get_contents($filename));
    $meta = $this->getMeta($filename);
    $html = file_get_contents(__DIR__ . "/template.html");
    if(substr($source, -1) === PHP_EOL) {
      $source = substr($source, 0, -1);
    }
    if(strlen($meta["title"]) === 0) {
      unset($meta["title"]);
      $html = str_replace("
  <title>%%title%%</title>", "", $html);
    }
    $this->processAssets($meta, $html, dirname($filename));
    $meta["source"] = $source;
    foreach($meta as $key => $value) {
      $html = str_replace("%%$key%%", $value, $html);
    }
    return $html;
  }
  
  /**
   * Generate the site
   */
  public function generate(): void {
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