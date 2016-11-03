<?php
use Nette\CommandLine\Parser,
    Nexendrie\SiteGenerator\Generator;

require_once __DIR__ . "/functions.php";
require findVendorDirectory() . "/autoload.php";

$cmd = new Parser("", [
  "--source" => [Parser::REALPATH => true, Parser::VALUE => NULL, Parser::ARGUMENT => true],
  "--output" => [Parser::REALPATH => true, Parser::VALUE => NULL, Parser::ARGUMENT => true],
]);
$options = $cmd->parse();

$generator = new Generator($options["--source"], $options["--output"]);
$generator->generate();
?>