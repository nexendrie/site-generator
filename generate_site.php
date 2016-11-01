<?php
require __DIR__ . "/vendor/autoload.php";

$generator = new Nexendrie\SiteGenerator\Generator("./doc");
$generator->generate();
?>