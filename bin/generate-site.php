<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Nette\CommandLine\Parser;

function findVendorDirectory(): string
{
    if (isset($GLOBALS["_composer_autoload_path"]) && is_string($GLOBALS["_composer_autoload_path"])) {
        return dirname($GLOBALS["_composer_autoload_path"]);
    }
    $recursionLimit = 10;
    $findVendor = function ($dirName = "vendor/bin", $dir = __DIR__) use (&$findVendor, &$recursionLimit) {
        $recursionLimit--;
        if ($recursionLimit < 1) {
            throw new \Exception("Cannot find vendor directory.");
        }
        $found = $dir . "/$dirName";
        if (is_dir($found) || is_file($found)) {
            return dirname($found);
        }
        return $findVendor($dirName, dirname($dir));
    };
    return $findVendor();
}

require findVendorDirectory() . "/autoload.php";

$cmd = new Parser("", [
    "--source" => [
        Parser::RealPath => true, Parser::Default => findVendorDirectory() . "/../",
        Parser::Argument => true,
    ],
    "--output" => [
        Parser::Default => findVendorDirectory() . "/../public/",
        Parser::Argument => true,
    ],
    "--ignoreFile" => [
        Parser::Argument => true,
        Parser::Optional => true,
        Parser::Repeatable => true,
    ],
    "--ignoreFolder" => [
        Parser::Argument => true,
        Parser::Optional => true,
        Parser::Repeatable => true,
    ],
]);
$options = $cmd->parse();

$generator = new Generator($options["--source"], $options["--output"]);
$ignoredFiles = $options["--ignoreFile"];
if (count($ignoredFiles) > 0) {
    $generator->ignoredFiles = $ignoredFiles;
}
$ignoredFolders = $options["--ignoreFolder"];
if (count($ignoredFolders) > 0) {
    $generator->ignoredFolders = $ignoredFolders;
}
$generator->generate();
