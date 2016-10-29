<?php
namespace Nexendrie\SiteGenerator;

use Tester\Assert,
    Nette\Utils\Finder;

require __DIR__ . "/../../bootstrap.php";


class GeneratorTest extends \Tester\TestCase {
  use \Testbench\TCompiledContainer;
  
  /** @var Generator */
  protected $generator;
  
  function setUp() {
    $this->generator = $this->getService(Generator::class);
  }
  
  function testGetSource() {
    $source = $this->generator->source;
    Assert::type("string", $source);
    $expected = realpath(dirname(\findVendorDirectory()));
    Assert::same($expected, $source);
  }
  
  function testGetOutput() {
    $output = $this->generator->output;
    Assert::type("string", $output);
    $expected = realpath(dirname(\findVendorDirectory()) . "/public");
    Assert::same($expected, $output);
  }
  
  protected function prepareSources() {
    $files = Finder::findFiles("*.md")
      ->from(dirname(\findVendorDirectory()) . "/tests/sources");
    $source = $this->generator->source;
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      copy($file->getRealPath(), $source . "/" . $file->getBasename());
    }
  }
  
  protected function cleanSources() {
    $files = Finder::findFiles("*.md")
      ->from(dirname(\findVendorDirectory()) . "/tests/sources");
    $source = $this->generator->source;
    /** @var \SplFileInfo $file */
    foreach($files as $file) {
      @unlink($source . "/" . $file->getBasename());
    }
  }
  
  function testGenerate() {
    $this->prepareSources();
    $this->generator->generate();
    Assert::true(file_exists($this->generator->output . "/index.html"));
    $this->cleanSources();
  }
}

$test = new GeneratorTest;
$test->run();
?>