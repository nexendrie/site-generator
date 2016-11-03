<?php
use Nette\CommandLine\Parser;

require_once __DIR__ . "/functions.php";
require findVendorDirectory() . "/autoload.php";

$cmd = new Parser("", [
  "--source" => [Parser::REALPATH => true, Parser::VALUE => NULL, Parser::ARGUMENT => true],
  "--output" => [Parser::REALPATH => true, Parser::VALUE => NULL, Parser::ARGUMENT => true],
]);
$options = $cmd->parse();

$generator = new Nexendrie\SiteGenerator\Generator($options["--source"], $options["--output"]);
$generator->generate();
?>