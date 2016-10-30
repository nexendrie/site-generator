<?php
/**
 * @return string
 */
function findVendorDirectory() {
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

/**
 * Recursively remove a directory
 *
 * @param string $dir
 * @return void
 */
function rrmdir($dir) {
  if(is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if($object != "." && $object != "..") {
        if(filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object);
        else unlink($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
}
?>