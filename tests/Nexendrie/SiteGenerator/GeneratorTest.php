<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Tester\Assert;
use Nette\Utils\Finder;

require __DIR__ . "/../../bootstrap.php";

final class GeneratorTest extends \Tester\TestCase {
  use \Testbench\TCompiledContainer;
  
  /** @var Generator */
  protected $generator;
  
  protected function setUp(): void {
    $this->generator = $this->getService(Generator::class); // @phpstan-ignore assign.propertyType
  }
  
  public function testGetSource(): void {
    $source = $this->generator->source;
    Assert::type("string", $source);
    $expected = realpath(dirname(findVendorDirectory()));
    Assert::same($expected, $source);
  }
  
  public function testGetOutput(): void {
    $output = $this->generator->output;
    Assert::type("string", $output);
    $expected = realpath(dirname(findVendorDirectory()) . "/public");
    Assert::same($expected, $output);
  }
  
  public function testIgnoredFiles(): void {
    $originalValue = $this->generator->ignoredFiles;
    $this->generator->ignoredFiles = [];
    Assert::count(0, $this->generator->ignoredFiles);
    $this->generator->ignoredFiles = $originalValue;
  }
  
  public function testIgnoredFolders(): void {
    $originalValue = $this->generator->ignoredFolders;
    $this->generator->ignoredFolders = [];
    Assert::count(0, $this->generator->ignoredFolders);
    $this->generator->ignoredFolders = $originalValue;
  }
  
  protected function prepareSources(): void {
    $files = Finder::findFiles("*.md")
      ->from(dirname(findVendorDirectory()) . "/tests/sources");
    $source = $this->generator->source;
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      copy($file->getRealPath(), $source . "/" . $file->getBasename());
    }
  }
  
  protected function cleanSources(): void {
    $files = Finder::findFiles("*.md")
      ->from(dirname(findVendorDirectory()) . "/tests/sources");
    $source = $this->generator->source;
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      @unlink($source . "/" . $file->getBasename());
    }
  }
  
  public function testGenerate(): void {
    $this->prepareSources();
    $this->generator->generate();
    $files = [
      "index.html" => "pageExpectedNoTitle.html",
    ];
    foreach($files as $actual => $expected) {
      $actual = "{$this->generator->output}/$actual";
      $expected = __DIR__ . "/$expected";
      Assert::true(file_exists($actual));
      Assert::matchFile($expected, (string) file_get_contents($actual));
    }
    $this->cleanSources();
  }
  
  public function testGenerateWithCustomFolders(): void {
    $source = (string) realpath(dirname(findVendorDirectory()) . "/tests/sources");
    $output = (string) realpath(dirname(findVendorDirectory()) . "/public");
    $this->generator->source = $source;
    Assert::same($source, $this->generator->source);
    $this->generator->output = $output;
    Assert::same($output, $this->generator->output);
    $this->generator->generate();
    $files = [
      "index.html" => "pageExpected.html",
      "assets.html" => "pageAssets.html",
    ];
    foreach($files as $actual => $expected) {
      $actual = "{$this->generator->output}/$actual";
      $expected = __DIR__ . "/$expected";
      Assert::true(file_exists($actual));
      Assert::matchFile($expected, (string) file_get_contents($actual));
    }
    Assert::true(file_exists("{$this->generator->output}/style.css"));
    Assert::true(file_exists("{$this->generator->output}/script.js"));
    Assert::true(file_exists("{$this->generator->output}/blank.jpg"));
    Assert::false(file_exists("{$this->generator->output}/nonexisting.png"));
  }
  
  public function testGetFilesToProcess(): void {
    $filesToProcess = $this->generator->filesToProcess;
    Assert::type(Finder::class, $filesToProcess);
    Assert::same(2, iterator_count($filesToProcess->getIterator()));
  }
}

$test = new GeneratorTest();
$test->run();
?>