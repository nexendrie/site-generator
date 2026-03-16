<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Nette\Utils\Finder;

final class GeneratorTest extends \MyTester\TestCase
{
    protected Generator $generator;

    public function setUp(): void
    {
        $this->generator = new Generator(__DIR__ . "/../../..", __DIR__ . "/../../../public");
    }

    public function testGetSource(): void
    {
        $source = $this->generator->source;
        $this->assertType("string", $source);
        $expected = realpath(__DIR__ . "/../../..");
        $this->assertSame($expected, $source);
    }

    public function testGetOutput(): void
    {
        $output = $this->generator->output;
        $this->assertType("string", $output);
        $expected = realpath(__DIR__ . "/../../../public");
        $this->assertSame($expected, $output);
    }

    public function testIgnoredFiles(): void
    {
        $originalValue = $this->generator->ignoredFiles;
        $this->generator->ignoredFiles = [];
        $this->assertCount(0, $this->generator->ignoredFiles);
        $this->generator->ignoredFiles = $originalValue;
    }

    public function testIgnoredFolders(): void
    {
        $originalValue = $this->generator->ignoredFolders;
        $this->generator->ignoredFolders = [];
        $this->assertCount(0, $this->generator->ignoredFolders);
        $this->generator->ignoredFolders = $originalValue;
    }

    protected function prepareSources(): void
    {
        $files = Finder::findFiles("*.md")
            ->from(__DIR__ . "/../../../tests/sources");
        $source = $this->generator->source;
        foreach ($files as $file) {
            copy($file->getRealPath(), $source . "/" . $file->getBasename());
        }
    }

    protected function cleanSources(): void
    {
        $files = Finder::findFiles("*.md")
            ->from(__DIR__ . "/../../../tests/sources");
        $source = $this->generator->source;
        foreach ($files as $file) {
            @unlink($source . "/" . $file->getBasename());
        }
    }

    public function testGenerate(): void
    {
        $this->prepareSources();
        $this->generator->generate();
        $files = [
            "index.html" => "pageExpectedNoTitle.html",
        ];
        foreach ($files as $actual => $expected) {
            $actual = "{$this->generator->output}/$actual";
            $expected = __DIR__ . "/$expected";
            $this->assertTrue(file_exists($actual));
            $this->assertSame((string) file_get_contents($expected), (string) file_get_contents($actual));
        }
        $this->cleanSources();
    }

    public function testGenerateWithCustomFolders(): void
    {
        $source = (string) realpath(__DIR__ . "/../../../tests/sources");
        $output = (string) realpath(__DIR__ . "/../../../public");
        $this->generator->source = $source;
        $this->assertSame($source, $this->generator->source);
        $this->generator->output = $output;
        $this->assertSame($output, $this->generator->output);
        $this->generator->generate();
        $files = [
            "index.html" => "pageExpected.html",
            "assets.html" => "pageAssets.html",
        ];
        foreach ($files as $actual => $expected) {
            $actual = "{$this->generator->output}/$actual";
            $expected = __DIR__ . "/$expected";
            $this->assertTrue(file_exists($actual));
            $this->assertSame((string) file_get_contents($expected), (string) file_get_contents($actual));
        }
        $this->assertTrue(file_exists("{$this->generator->output}/style.css"));
        $this->assertTrue(file_exists("{$this->generator->output}/script.js"));
        $this->assertTrue(file_exists("{$this->generator->output}/blank.jpg"));
        $this->assertFalse(file_exists("{$this->generator->output}/nonexisting.png"));
    }

    public function testGetFilesToProcess(): void
    {
        $this->generator->source = (string) realpath(__DIR__ . "/../../../tests/sources");
        $filesToProcess = $this->generator->filesToProcess;
        $this->assertType(Finder::class, $filesToProcess);
        $this->assertCount(2, $filesToProcess->collect());
    }
}
