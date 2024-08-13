<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

use Nette\CommandLine\Parser;

require_once __DIR__ . "/../src/functions.php";
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
if(count($ignoredFiles) > 0) {
  $generator->ignoredFiles = $ignoredFiles;
}
$ignoredFolders = $options["--ignoreFolder"];
if(count($ignoredFolders) > 0) {
  $generator->ignoredFolders = $ignoredFolders;
}
$generator->generate();
?>