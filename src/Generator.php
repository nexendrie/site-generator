<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use cebe\markdown\GithubMarkdown,
    Nette\Utils\Finder,
    Nette\Neon\Neon,
    Nette\Utils\FileSystem,
    Symfony\Component\OptionsResolver\OptionsResolver,
    Nette\Utils\Validators;

/**
 * Generator
 *
 * @author Jakub Konečný
 * @property string $source
 * @property string $output
 * @method void onBeforeGenerate()
 * @method void onAfterGenerate()
 */
class Generator {
  use \Nette\SmartObject;
  
  /** @var string */
  protected $source;
  /** @var string */
  protected $output;
  /** @var string[] */
  protected $assets = [];
  /** @var callable[] */
  public $onBeforeGenerate = [];
  /** @var callable[] */
  public $onAfterGenerate = [];
  
  public function __construct(string $source, string $output) {
    $this->setSource($source);
    FileSystem::createDir($output);
    $this->setOutput($output);
    $this->onBeforeGenerate[] = [$this, "clearOutputFolder"];
    $this->onAfterGenerate[] = [$this, "copyAssets"];
  }
  
  public function getSource(): string {
    return $this->source;
  }
  
  public function setSource(string $source) {
    if(is_dir($source)) {
      $this->source = realpath($source);
    }
  }
  
  public function getOutput(): string {
    return $this->output;
  }
  
  public function setOutput(string $output) {
    $this->output = realpath($output);
  }
  
  protected function getMeta(string $filename): array {
    $resolver = new OptionsResolver();
    $resolver->setDefaults([
      "title" => "",
      "styles" => [],
      "scripts" => [],
    ]);
    $isArrayOfStrings = function(array $value) {
      return Validators::everyIs($value, "string");
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
  
  protected function addAsset(string $asset): void {
    if(!in_array($asset, $this->assets)) {
      $this->assets[] = realpath($asset);
    }
  }
  
  protected function processAssets(array &$meta, string &$html, string $basePath): void {
    $meta["styles"] = array_filter($meta["styles"], function($value) use($basePath) {
      return file_exists("$basePath/$value");
    });
    $meta["scripts"] = array_filter($meta["scripts"], function($value) use($basePath) {
      return file_exists("$basePath/$value");
    });
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
    if(isset($meta["styles"])) {
      array_walk($meta["styles"], function(&$value) use($basePath) {
        $this->addAsset("$basePath/$value");
        $value = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$value\">";
      });
      $meta["styles"] = implode("\n  ", $meta["styles"]);
    }
    if(isset($meta["scripts"])) {
      array_walk($meta["scripts"], function(&$value) use($basePath) {
        $this->addAsset("$basePath/$value");
        $value = "<script type=\"text/javascript\" src=\"$value\"></script>";
      });
      $meta["scripts"] = implode("\n  ", $meta["scripts"]);
    }
  }
  
  protected function createHtml(string $filename): string {
    $parser = new GithubMarkdown();
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
   * @internal
   */
  public function clearOutputFolder(): void {
    FileSystem::delete($this->output);
  }
  
  /**
   * @internal
   */
  public function copyAssets(): void {
    foreach($this->assets as $asset) {
      $path = str_replace($this->source, "", $asset);
      $target = "$this->output$path";
      FileSystem::copy($asset, $target);
      echo "Copied $path";
    }
  }
  
  /**
   * Generate the site
   */
  public function generate(): void {
    $this->onBeforeGenerate();
    $files = Finder::findFiles("*.md")
      ->exclude("README.md")
      ->from($this->source)
      ->exclude("vendor", ".git", "tests");
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      $path = str_replace($this->source, "", dirname($file->getRealPath()));
      $html = $this->createHtml($file->getRealPath());
      $basename = $file->getBasename(".md") . ".html";
      $filename = "$this->output$path/$basename";
      FileSystem::write($filename, $html);
      echo "Created $path/$basename\n";
    }
    $this->onAfterGenerate();
  }
}
?>