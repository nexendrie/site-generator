<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Dom\HTMLDocument;
use Nette\Utils\FileInfo;
use Nette\Utils\Finder;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nexendrie\SiteGenerator\Events\AfterGenerate;
use Nexendrie\SiteGenerator\Events\BeforeGenerate;
use Nexendrie\SiteGenerator\Events\PageGenerated;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use xenocrat\markdown\GithubMarkdown;

/**
 * Generator
 *
 * @author Jakub Konečný
 * @property string $source
 * @property string $output
 * @property-read Finder|FileInfo[] $filesToProcess
 * @property list<string> $ignoredFiles
 * @property list<string> $ignoredFolders
 */
final class Generator
{
    use \Nette\SmartObject;

    private string $templateFile = __DIR__ . "/template.html";
    /** @var list<string> */
    private array $ignoredFiles = [];
    /** @var list<string> */
    private array $ignoredFolders = [
        "vendor", ".git", "tests",
    ];
    private string $source;
    private string $output;
    /** @var Finder|FileInfo[] */
    private Finder $filesToProcess;
    /** @var list<string> */
    private array $assets = [];
    /** @var callable[] */
    private array $metaNormalizers = [];
    /**
     * @var callable[]
     * @deprecated Use a PSR-14 event dispatcher
     */
    public array $onBeforeGenerate = [];
    /**
     * @var callable[]
     * @deprecated Use a PSR-14 event dispatcher
     */
    public array $onCreatePage = [];
    /**
     * @var callable[]
     * @deprecated Use a PSR-14 event dispatcher
     */
    public array $onAfterGenerate = [];

    public function __construct(
        string $source,
        string $output,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->setSource($source);
        FileSystem::createDir($output);
        $this->setOutput($output);
        $this->onBeforeGenerate[] = $this->getFilesToProcess(...); // @phpstan-ignore property.deprecated
        $this->onBeforeGenerate[] = $this->clearOutputFolder(...); // @phpstan-ignore property.deprecated
        $this->onCreatePage[] = $this->processImages(...); // @phpstan-ignore property.deprecated
        $this->onAfterGenerate[] = $this->copyAssets(...); // @phpstan-ignore property.deprecated
        $this->addMetaNormalizer($this->normalizeTitle(...));
        $this->addMetaNormalizer($this->normalizeStyles(...));
        $this->addMetaNormalizer($this->normalizeScripts(...));
        $this->addMetaNormalizer($this->updateLinks(...));
        $this->addMetaNormalizer($this->addHtmlLanguage(...));
    }

    public function addMetaNormalizer(callable $callback): void
    {
        $this->metaNormalizers[] = $callback;
    }

    protected function getSource(): string
    {
        return $this->source;
    }

    protected function setSource(string $source): void
    {
        $this->source = (string) realpath($source);
    }

    protected function getOutput(): string
    {
        return $this->output;
    }

    protected function setOutput(string $output): void
    {
        $this->output = (string) realpath($output);
    }

    /**
     * @return list<string>
     */
    protected function getIgnoredFiles(): array
    {
        return $this->ignoredFiles;
    }

    /**
     * @param list<string> $ignoredFiles
     */
    protected function setIgnoredFiles(array $ignoredFiles): void
    {
        $this->ignoredFiles = array_map(strval(...), $ignoredFiles);
    }

    /**
     * @return list<string>
     */
    protected function getIgnoredFolders(): array
    {
        return $this->ignoredFolders;
    }

    /**
     * @param list<string> $ignoredFolders
     */
    protected function setIgnoredFolders(array $ignoredFolders): void
    {
        $this->ignoredFolders = array_map(strval(...), $ignoredFolders);
    }

    private function createMetaResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            "title" => "",
            "htmlLang" => "",
            "styles" => [],
            "scripts" => [],
        ]);
        $resolver->setAllowedTypes("title", "string");
        $resolver->setAllowedTypes("htmlLang", "string");
        $resolver->setAllowedTypes("styles", "string[]");
        $resolver->setAllowedTypes("scripts", "string[]");
        return $resolver;
    }

    private function getMetafileName(string $filename): string
    {
        return str_replace(".md", ".neon", $filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function getMeta(string $filename, string &$html): array
    {
        $resolver = $this->createMetaResolver();
        $metaFilename = $this->getMetafileName($filename);
        $meta = [];
        if (file_exists($metaFilename)) {
            $meta = Neon::decode((string) file_get_contents($metaFilename));
        }
        $result = $resolver->resolve($meta);
        foreach ($this->metaNormalizers as $normalizer) {
            $normalizer($result, $html, $filename);
        }
        return $result;
    }

    private function addAsset(string $asset): void
    {
        $asset = realpath($asset);
        if (is_string($asset) && !in_array($asset, $this->assets, true)) {
            $this->assets[] = $asset;
        }
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function normalizeTitle(array &$meta, string &$html, string $filename): void
    {
        if (strlen($meta["title"]) === 0) {
            unset($meta["title"]);
            $html = str_replace("
    <title>%%title%%</title>", "", $html);
        }
    }

    /**
     * @param list<string> $input
     */
    private function removeInvalidFiles(array &$input, string $basePath): void
    {
        // @phpstan-ignore parameterByRef.type
        $input = array_filter($input, function ($value) use ($basePath): bool {
            return file_exists("$basePath/$value");
        });
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function normalizeStyles(array &$meta, string &$html, string $filename): void
    {
        $basePath = dirname($filename);
        $this->removeInvalidFiles($meta["styles"], $basePath);
        if (count($meta["styles"]) === 0) {
            unset($meta["styles"]);
            $html = str_replace("
    %%styles%%", "", $html);
            return;
        }
        array_walk($meta["styles"], function (&$value) use ($basePath): void {
            $this->addAsset("$basePath/$value");
            $value = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$value\">";
        });
        $meta["styles"] = implode("\n    ", $meta["styles"]);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function normalizeScripts(array &$meta, string &$html, string $filename): void
    {
        $basePath = dirname($filename);
        $this->removeInvalidFiles($meta["scripts"], $basePath);
        if (count($meta["scripts"]) === 0) {
            unset($meta["scripts"]);
            $html = str_replace("
    %%scripts%%", "", $html);
            return;
        }
        array_walk($meta["scripts"], function (&$value) use ($basePath): void {
            $this->addAsset("$basePath/$value");
            $value = "<script type=\"text/javascript\" src=\"$value\"></script>";
        });
        $meta["scripts"] = implode("\n    ", $meta["scripts"]);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function updateLinks(array &$meta, string &$html, string $filename): void
    {
        set_error_handler(function ($errno): bool {
            return $errno === E_WARNING;
        });
        $dom = HTMLDocument::createFromString($html);
        restore_error_handler();
        $links = $dom->getElementsByTagName("a");
        foreach ($links as $link) {
            $oldContent = $dom->saveHtml($link);
            $needsUpdate = false;
            $target = $link->getAttribute("href");
            $target = dirname($filename) . "/" . $target;
            foreach ($this->filesToProcess as $file) {
                if ($target === $file->getRealPath() && str_ends_with($target, ".md")) {
                    $needsUpdate = true;
                    break;
                }
            }
            if (!$needsUpdate) {
                continue;
            }
            $link->setAttribute("href", str_replace(".md", ".html", (string) $link->getAttribute("href")));
            $newContent = $dom->saveHtml($link);
            $html = str_replace($oldContent, $newContent, $html);
        }
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function addHtmlLanguage(array &$meta, string &$html, string $filename): void
    {
        if (strlen($meta["htmlLang"]) > 0) {
            $html = str_replace("<html>", "<html lang=\"{$meta["htmlLang"]}\">", $html);
        }
    }

    private function createMarkdownParser(): \xenocrat\markdown\Markdown
    {
        $parser = new GithubMarkdown();
        $parser->html5 = true;
        $parser->keepListStartNumber = true;
        $parser->enableNewlines = true;
        return $parser;
    }

    private function createHtml(string $filename): string
    {
        $parser = $this->createMarkdownParser();
        $source = $parser->parse((string) file_get_contents($filename));
        $html = (string) file_get_contents($this->templateFile);
        $html = str_replace("%%source%%", $source, $html);
        return $html;
    }

    /**
     * @return Finder|FileInfo[]
     */
    protected function getFilesToProcess(): Finder
    {
        $this->filesToProcess = Finder::findFiles("*.md")
            ->exclude($this->ignoredFiles)
            ->from($this->source)
            ->exclude($this->ignoredFolders);
        return $this->filesToProcess;
    }

    private function clearOutputFolder(): void
    {
        FileSystem::delete($this->output);
    }

    private function copyAssets(): void
    {
        foreach ($this->assets as $asset) {
            $path = str_replace($this->source, "", $asset);
            $target = "$this->output$path";
            FileSystem::copy($asset, $target);
            echo "Copied $path";
        }
    }

    private function processImages(string $html, self $generator, string $filename): void
    {
        $dom = HTMLDocument::createFromString($html);
        $images = $dom->getElementsByTagName("img");
        foreach ($images as $image) {
            $path = dirname($filename) . "/" . $image->getAttribute("src");
            if (file_exists($path)) {
                $generator->addAsset($path);
            }
        }
    }

    /**
     * Generate the site
     */
    public function generate(): void
    {
        $this->onBeforeGenerate(); // @phpstan-ignore method.deprecated
        $this->eventDispatcher?->dispatch(new BeforeGenerate());
        foreach ($this->filesToProcess as $file) {
            $path = str_replace($this->source, "", dirname($file->getRealPath()));
            $html = $this->createHtml($file->getRealPath());
            $meta = $this->getMeta($file->getRealPath(), $html);
            foreach ($meta as $key => $value) {
                $html = str_replace("%%$key%%", $value, $html);
            }
            $basename = $file->getBasename(".md") . ".html";
            $filename = "$this->output$path/$basename";
            FileSystem::write($filename, $html);
            echo "Created $path/$basename\n";
            $this->onCreatePage($html, $this, $file->getRealPath()); // @phpstan-ignore method.deprecated
            $this->eventDispatcher?->dispatch(new PageGenerated($html, $this, $file->getRealPath()));
        }
        $this->onAfterGenerate(); // @phpstan-ignore method.deprecated
        $this->eventDispatcher?->dispatch(new AfterGenerate());
    }

    #[\Deprecated("use a PSR-14 event dispatcher")]
    public function onBeforeGenerate(): void
    {
        foreach ($this->onBeforeGenerate as $callback) {
            $callback();
        }
    }

    #[\Deprecated("use a PSR-14 event dispatcher")]
    public function onCreatePage(string $html, Generator $generator, string $filename): void
    {
        foreach ($this->onCreatePage as $callback) {
            $callback($html, $generator, $filename);
        }
    }

    #[\Deprecated("use a PSR-14 event dispatcher")]
    public function onAfterGenerate(): void
    {
        foreach ($this->onAfterGenerate as $callback) {
            $callback();
        }
    }
}
