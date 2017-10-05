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
 * @method void onCreatePage(string $html, Generator $generator, string $filename)
 * @method void onAfterGenerate()
 */
class Generator {
  use \Nette\SmartObject;
  
  /** @var string */
  protected $templateFile = __DIR__ . "/template.html";
  /** @var string[] */
  protected $ignoredFiles = [
    "README.md",
  ];
  /** @var string[] */
  protected $ignoredFolders = [
    "vendor", ".git", "tests",
  ];
  /** @var string */
  protected $source;
  /** @var string */
  protected $output;
  /** @var string[] */
  protected $assets = [];
  /** @var callable[] */
  protected $metaNormalizers = [];
  /** @var callable[] */
  public $onBeforeGenerate = [];
  /** @var callable[] */
  public $onCreatePage = [];
  /** @var callable[] */
  public $onAfterGenerate = [];
  
  public function __construct(string $source, string $output) {
    $this->setSource($source);
    FileSystem::createDir($output);
    $this->setOutput($output);
    $this->onBeforeGenerate[] = [$this, "clearOutputFolder"];
    $this->onCreatePage[] = [$this, "processImages"];
    $this->onAfterGenerate[] = [$this, "copyAssets"];
    $this->addMetaNormalizer([$this, "normalizeTitle"]);
    $this->addMetaNormalizer([$this, "normalizeStyles"]);
    $this->addMetaNormalizer([$this, "normalizeScripts"]);
  }
  
  public function addMetaNormalizer(callable $callback): void {
    $this->metaNormalizers[] = $callback;
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
  
  protected function createMetaResolver(): OptionsResolver {
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
    return $resolver;
  }
  
  protected function getMeta(string $filename, string &$html): array {
    $resolver = $this->createMetaResolver();
    $metaFilename = str_replace(".md", ".neon", $filename);
    $meta = [];
    if(file_exists($metaFilename)) {
      $meta = Neon::decode(file_get_contents($metaFilename));
    }
    $result = $resolver->resolve($meta);
    foreach($this->metaNormalizers as $normalizer) {
      $normalizer($result, $html, $filename);
    }
    return $result;
  }
  
  protected function addAsset(string $asset): void {
    $asset = realpath($asset);
    if(!in_array($asset, $this->assets)) {
      $this->assets[] = $asset;
    }
  }
  
  protected function normalizeTitle(array &$meta, string &$html, string $filename): void {
    if(strlen($meta["title"]) === 0) {
      unset($meta["title"]);
      $html = str_replace("
  <title>%%title%%</title>", "", $html);
    }
  }
  
  protected function normalizeStyles(array &$meta, string &$html, string $filename): void {
    $basePath = dirname($filename);
    $meta["styles"] = array_filter($meta["styles"], function($value) use($basePath) {
      return file_exists("$basePath/$value");
    });
    if(!count($meta["styles"])) {
      unset($meta["styles"]);
      $html = str_replace("
  %%styles%%", "", $html);
      return;
    }
    array_walk($meta["styles"], function(&$value) use($basePath) {
      $this->addAsset("$basePath/$value");
      $value = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$value\">";
    });
    $meta["styles"] = implode("\n  ", $meta["styles"]);
  }
  
  protected function normalizeScripts(array &$meta, string &$html, string $filename): void {
    $basePath = dirname($filename);
    $meta["scripts"] = array_filter($meta["scripts"], function($value) use($basePath) {
      return file_exists("$basePath/$value");
    });
    if(!count($meta["scripts"])) {
      unset($meta["scripts"]);
      $html = str_replace("
  %%scripts%%", "", $html);
      return;
    }
    array_walk($meta["scripts"], function(&$value) use($basePath) {
      $this->addAsset("$basePath/$value");
      $value = "<script type=\"text/javascript\" src=\"$value\"></script>";
    });
    $meta["scripts"] = implode("\n  ", $meta["scripts"]);
  }
  
  protected function createMarkdownParser(): \cebe\markdown\Markdown {
    $parser = new class extends GithubMarkdown {
      public function parse($text): string {
        $markup = parent::parse($text);
        if(substr($markup, -1) === PHP_EOL) {
          $markup = substr($markup, 0, -1);
        }
        return $markup;
      }
    };
    $parser->html5 = $parser->keepListStartNumber = $parser->enableNewlines = true;
    return $parser;
  }
  
  protected function createHtml(string $filename): string {
    $parser = $this->createMarkdownParser();
    $source = $parser->parse(file_get_contents($filename));
    $html = file_get_contents($this->templateFile);
    $html = str_replace("%%source%%", $source, $html);
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
   * @internal
   */
  public function processImages(string $html, self $generator, string $filename): void {
    $dom = new \DOMDocument();
    $dom->loadHTML($html);
    $images = $dom->getElementsByTagName("img");
    /** @var \DOMElement $image */
    foreach($images as $image) {
      $path = dirname($filename) . "/" . $image->getAttribute("src");
      if(file_exists($path)) {
        $generator->addAsset($path);
      }
    }
  }
  
  /**
   * Generate the site
   */
  public function generate(): void {
    $this->onBeforeGenerate();
    $files = Finder::findFiles("*.md")
      ->exclude($this->ignoredFiles)
      ->from($this->source)
      ->exclude($this->ignoredFolders);
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      $path = str_replace($this->source, "", dirname($file->getRealPath()));
      $html = $this->createHtml($file->getRealPath());
      $meta = $this->getMeta($file->getRealPath(), $html);
      foreach($meta as $key => $value) {
        $html = str_replace("%%$key%%", $value, $html);
      }
      $basename = $file->getBasename(".md") . ".html";
      $filename = "$this->output$path/$basename";
      FileSystem::write($filename, $html);
      echo "Created $path/$basename\n";
      $this->onCreatePage($html, $this, $file->getRealPath());
    }
    $this->onAfterGenerate();
  }
}
?>