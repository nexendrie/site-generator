<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Nette\Utils\Finder;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Nette\Utils\Validators;
use Nette\Utils\Strings;

/**
 * Generator
 *
 * @author Jakub Konečný
 * @property string $source
 * @property string $output
 * @property-read Finder|\SplFileInfo[] $filesToProcess
 * @property string[] $ignoredFiles
 * @property string[] $ignoredFolders
 * @method void onBeforeGenerate()
 * @method void onCreatePage(string $html, Generator $generator, string $filename)
 * @method void onAfterGenerate()
 */
final class Generator {
  use \Nette\SmartObject;

  private string $templateFile = __DIR__ . "/template.html";
  /** @var string[] */
  private array $ignoredFiles = [];
  /** @var string[] */
  private array $ignoredFolders = [
    "vendor", ".git", "tests",
  ];
  private string $source;
  private string $output;
  /** @var Finder|\SplFileInfo[] */
  private $filesToProcess;
  /** @var string[] */
  private array $assets = [];
  /** @var callable[] */
  private array $metaNormalizers = [];
  /** @var callable[] */
  public array $onBeforeGenerate = [];
  /** @var callable[] */
  public array $onCreatePage = [];
  /** @var callable[] */
  public array $onAfterGenerate = [];
  
  public function __construct(string $source, string $output) {
    $this->setSource($source);
    FileSystem::createDir($output);
    $this->setOutput($output);
    $this->onBeforeGenerate[] = [$this, "getFilesToProcess"];
    $this->onBeforeGenerate[] = [$this, "clearOutputFolder"];
    $this->onCreatePage[] = [$this, "processImages"];
    $this->onAfterGenerate[] = [$this, "copyAssets"];
    $this->addMetaNormalizer([$this, "normalizeTitle"]);
    $this->addMetaNormalizer([$this, "normalizeStyles"]);
    $this->addMetaNormalizer([$this, "normalizeScripts"]);
    $this->addMetaNormalizer([$this, "updateLinks"]);
    $this->addMetaNormalizer([$this, "addHtmlLanguage"]);
  }
  
  public function addMetaNormalizer(callable $callback): void {
    $this->metaNormalizers[] = $callback;
  }
  
  protected function getSource(): string {
    return $this->source;
  }
  
  protected function setSource(string $source): void {
    if(is_dir($source)) {
      $this->source = (string) realpath($source);
    }
  }

  protected function getOutput(): string {
    return $this->output;
  }

  protected function setOutput(string $output): void {
    $this->output = (string) realpath($output);
  }
  
  /**
   * @return string[]
   */
  protected function getIgnoredFiles(): array {
    return $this->ignoredFiles;
  }

  /**
   * @param string[] $ignoredFiles
   */
  protected function setIgnoredFiles(array $ignoredFiles): void {
    $this->ignoredFiles = [];
    foreach($ignoredFiles as $ignoredFile) {
      $this->ignoredFiles[] = (string) $ignoredFile;
    }
  }
  
  /**
   * @return string[]
   */
  protected function getIgnoredFolders(): array {
    return $this->ignoredFolders;
  }

  /**
   * @param string[] $ignoredFolders
   */
  protected function setIgnoredFolders(array $ignoredFolders): void {
    $this->ignoredFolders = [];
    foreach($ignoredFolders as $ignoredFolder) {
      $this->ignoredFolders[] = (string) $ignoredFolder;
    }
  }
  
  protected function createMetaResolver(): OptionsResolver {
    $resolver = new OptionsResolver();
    $resolver->setDefaults([
      "title" => "",
      "htmlLang" => "",
      "styles" => [],
      "scripts" => [],
    ]);
    $isArrayOfStrings = function(array $value): bool {
      return Validators::everyIs($value, "string");
    };
    $resolver->setAllowedTypes("title", "string");
    $resolver->setAllowedTypes("htmlLang", "string");
    $resolver->setAllowedTypes("styles", "array");
    $resolver->setAllowedValues("styles", $isArrayOfStrings);
    $resolver->setAllowedTypes("scripts", "array");
    $resolver->setAllowedValues("scripts", $isArrayOfStrings);
    return $resolver;
  }
  
  protected function getMetafileName(string $filename): string {
    return str_replace(".md", ".neon", $filename);
  }
  
  protected function getMeta(string $filename, string &$html): array {
    $resolver = $this->createMetaResolver();
    $metaFilename = $this->getMetafileName($filename);
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
    if(is_string($asset) && !in_array($asset, $this->assets, true)) {
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
  
  protected function removeInvalidFiles(array &$input, string $basePath): void {
    $input = array_filter($input, function($value) use($basePath): bool {
      return file_exists("$basePath/$value");
    });
  }
  
  protected function normalizeStyles(array &$meta, string &$html, string $filename): void {
    $basePath = dirname($filename);
    $this->removeInvalidFiles($meta["styles"], $basePath);
    if(count($meta["styles"]) === 0) {
      unset($meta["styles"]);
      $html = str_replace("
  %%styles%%", "", $html);
      return;
    }
    array_walk($meta["styles"], function(&$value) use($basePath): void {
      $this->addAsset("$basePath/$value");
      $value = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$value\">";
    });
    $meta["styles"] = implode("\n  ", $meta["styles"]);
  }
  
  protected function normalizeScripts(array &$meta, string &$html, string $filename): void {
    $basePath = dirname($filename);
    $this->removeInvalidFiles($meta["scripts"], $basePath);
    if(count($meta["scripts"]) === 0) {
      unset($meta["scripts"]);
      $html = str_replace("
  %%scripts%%", "", $html);
      return;
    }
    array_walk($meta["scripts"], function(&$value) use($basePath): void {
      $this->addAsset("$basePath/$value");
      $value = "<script type=\"text/javascript\" src=\"$value\"></script>";
    });
    $meta["scripts"] = implode("\n  ", $meta["scripts"]);
  }
  
  protected function updateLinks(array &$meta, string &$html, string $filename): void {
    $dom = new \DOMDocument();
    set_error_handler(function($errno): bool {
      return $errno === E_WARNING;
    });
    $dom->loadHTML($html);
    restore_error_handler();
    $links = $dom->getElementsByTagName("a");
    /** @var \DOMElement $link */
    foreach($links as $link) {
      $oldContent = (string) $dom->saveHTML($link);
      $needsUpdate = false;
      $target = $link->getAttribute("href");
      $target = dirname($filename) . "/" . $target;
      foreach($this->filesToProcess as $file) {
        if($target === $file->getRealPath() && Strings::endsWith($target, ".md")) {
          $needsUpdate = true;
          continue;
        }
      }
      if(!$needsUpdate) {
        continue;
      }
      $link->setAttribute("href", str_replace(".md", ".html", $link->getAttribute("href")));
      $newContent = (string) $dom->saveHTML($link);
      $html = str_replace($oldContent, $newContent, $html);
    }
  }

  protected function addHtmlLanguage(array &$meta, string &$html, string $filename): void {
    if(strlen($meta["htmlLang"]) > 0) {
      $html = str_replace("<html>", "<html lang=\"{$meta["htmlLang"]}\">", $html);
    }
  }
  
  protected function createMarkdownParser(): \xenocrat\markdown\Markdown {
    return new MarkdownParser();
  }
  
  protected function createHtml(string $filename): string {
    $parser = $this->createMarkdownParser();
    $source = $parser->parse(file_get_contents($filename));
    $html = (string) file_get_contents($this->templateFile);
    $html = str_replace("%%source%%", $source, $html);
    return $html;
  }
  
  /**
   * @internal
   * @return Finder|\SplFileInfo[]
   * @todo make protected when we drop support for nette/utils 2.5
   */
  public function getFilesToProcess(): Finder {
    $this->filesToProcess = Finder::findFiles("*.md")
      ->exclude(...$this->ignoredFiles)
      ->from($this->source)
      ->exclude(...$this->ignoredFolders);
    return $this->filesToProcess;
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
    foreach($this->filesToProcess as $file) {
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