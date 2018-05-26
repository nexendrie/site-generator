<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Nette\CommandLine\Parser;

require_once __DIR__ . "/../src/functions.php";
require findVendorDirectory() . "/autoload.php";

$cmd = new Parser("", [
  "--source" => [
    Parser::REALPATH => true, Parser::VALUE => findVendorDirectory() . "/../",
    Parser::ARGUMENT => true,
  ],
  "--output" => [
    Parser::VALUE => findVendorDirectory() . "/../public/",
    Parser::ARGUMENT => true,
  ],
  "--ignoreFile" => [
    Parser::ARGUMENT => true,
    Parser::OPTIONAL => true,
    Parser::REPEATABLE => true,
  ],
  "--ignoreFolder" => [
    Parser::ARGUMENT => true,
    Parser::OPTIONAL => true,
    Parser::REPEATABLE => true,
  ],
]);
$options = $cmd->parse();

$generator = new Generator($options["--source"], $options["--output"]);
$ignoredFiles = $options["--ignoreFile"];
if(count($ignoredFiles) > 0) {
  $generator->ignoredFiles = $ignoredFiles;
}
$ignoredFolders = $options["--ignoreFolder"];
if(count($ignoredFolders) > 0) {
  $generator->ignoredFolders = $ignoredFolders;
}
$generator->generate();
?>