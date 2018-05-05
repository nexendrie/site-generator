<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Tester\Assert,
    Nette\Utils\Finder;

require __DIR__ . "/../../bootstrap.php";

final class GeneratorTest extends \Tester\TestCase {
  use \Testbench\TCompiledContainer;
  
  /** @var Generator */
  protected $generator;
  
  protected function setUp() {
    $this->generator = $this->getService(Generator::class);
  }
  
  public function testGetSource() {
    $source = $this->generator->source;
    Assert::type("string", $source);
    $expected = realpath(dirname(findVendorDirectory()));
    Assert::same($expected, $source);
  }
  
  public function testGetOutput() {
    $output = $this->generator->output;
    Assert::type("string", $output);
    $expected = realpath(dirname(findVendorDirectory()) . "/public");
    Assert::same($expected, $output);
  }
  
  protected function prepareSources() {
    $files = Finder::findFiles("*.md")
      ->from(dirname(findVendorDirectory()) . "/tests/sources");
    $source = $this->generator->source;
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      copy($file->getRealPath(), $source . "/" . $file->getBasename());
    }
  }
  
  protected function cleanSources() {
    $files = Finder::findFiles("*.md")
      ->from(dirname(findVendorDirectory()) . "/tests/sources");
    $source = $this->generator->source;
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      @unlink($source . "/" . $file->getBasename());
    }
  }
  
  public function testGenerate() {
    $this->prepareSources();
    $this->generator->generate();
    $files = [
      "index.html" => "pageExpectedNoTitle.html",
    ];
    foreach($files as $actual => $expected) {
      $actual = "{$this->generator->output}/$actual";
      $expected = __DIR__ . "/$expected";
      Assert::true(file_exists($actual));
      Assert::matchFile($expected, file_get_contents($actual));
    }
    $this->cleanSources();
  }
  
  public function testGenerateWithCustomFolders() {
    $source = realpath(dirname(findVendorDirectory()) . "/tests/sources");
    $output = realpath(dirname(findVendorDirectory()) . "/public");
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
      Assert::matchFile($expected, file_get_contents($actual));
    }
    Assert::true(file_exists("{$this->generator->output}/style.css"));
    Assert::true(file_exists("{$this->generator->output}/script.js"));
    Assert::true(file_exists("{$this->generator->output}/blank.jpg"));
    Assert::false(file_exists("{$this->generator->output}/nonexisting.png"));
  }
  
  public function testGetFilesToProcess() {
    $filesToProcess = $this->generator->filesToProcess;
    Assert::type(Finder::class, $filesToProcess);
    Assert::count(2, $filesToProcess);
  }
}

$test = new GeneratorTest();
$test->run();
?>