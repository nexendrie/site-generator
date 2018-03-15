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
]);
$options = $cmd->parse();

$generator = new Generator($options["--source"], $options["--output"]);
$generator->generate();
?>