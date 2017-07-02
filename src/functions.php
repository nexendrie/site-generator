<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

function findVendorDirectory(): string {
  $recursionLimit = 10;
  $findVendor = function ($dirName = "vendor/bin", $dir = __DIR__) use (&$findVendor, &$recursionLimit) {
    if(!$recursionLimit--) {
      throw new \Exception("Cannot find vendor directory.");
    }
    $found = $dir . "/$dirName";
    if(is_dir($found) || is_file($found)) {
      return dirname($found);
    }
    return $findVendor($dirName, dirname($dir));
  };
  return $findVendor();
}
?>