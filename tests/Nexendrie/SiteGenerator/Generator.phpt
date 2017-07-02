<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Tester\Assert,
    Nette\Utils\Finder;

require __DIR__ . "/../../bootstrap.php";


class GeneratorTest extends \Tester\TestCase {
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
    $filename = $this->generator->output . "/index.html";
    Assert::true(file_exists($filename));
    $index = file_get_contents($filename);
    Assert::matchFile(__DIR__ . "/pageExpectedNoTitle.html", $index);
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
    $filename = $this->generator->output . "/index.html";
    Assert::true(file_exists($filename));
    $index = file_get_contents($filename);
    Assert::matchFile(__DIR__ . "/pageExpected.html", $index);
  }
}

$test = new GeneratorTest;
$test->run();
?>