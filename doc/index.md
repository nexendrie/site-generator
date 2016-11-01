Site Generator
==============

Static site generator which turns Markdown into HTML.

Links
=====

Primary repository: https://gitlab.com/nexendrie/site-generator
Github repository: https://github.com/nexendrie/site-generator
Packagist: https://packagist.org/packages/nexendrie/site-generator

Installation
============

The best way to install it is via Composer. Just add nexendrie/site-generator to your dependencies.

Usage
=====

Just run **./vendor/bin/generate-site**. The script will go through all .md files in your project's root folder (and all its subfolders) and create html pages from them and place them under public folder.

Alternative usage
=================

Alternatively, you can write your own script and specify another folders for sources and output. Example:

```php
<?php
require __DIR__ . "/vendor/autoload.php";

$source = "./sources";
$output = "./doc";
$generator = new Nexendrie\SiteGenerator\Generator($source, $output);
$generator->generate();
?>
```

Advanced usage
==============

Site generator with every source file also looks for a meta file (file with same name but extension neon). You can set there some additional information for it there, like page's title.

```
title: My page
```