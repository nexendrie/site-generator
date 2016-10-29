<?php
require_once __DIR__ . "/functions.php";
require findVendorDirectory() . "/autoload.php";

(new Nexendrie\SiteGenerator\Generator)->generate();
?>