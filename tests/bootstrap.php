<?php
declare(strict_types=1);

require __DIR__ . "/../vendor/autoload.php";
require_once(__DIR__ . "/../src/functions.php");

Testbench\Bootstrap::setup(__DIR__ . '/_temp', function (\Nette\Configurator $configurator): void {
  $configurator->addStaticParameters(["appDir" => __DIR__,]);
  $configurator->addConfig(__DIR__ . "/tests.neon");
});
?>