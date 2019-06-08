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

Just run **./vendor/bin/generate-site**. The script will go through all .md files in your project's root folder (and all its subfolders) and create html pages from them and place them under public folder. Both sources and output folders can be changed:

```bash
./vendor/bin/generate-site --source=doc --output=public
```

Alternative usage
=================

Alternatively, you can write your own script. Example:

```php
<?php
declare(strict_types=1);

require __DIR__ . "/vendor/autoload.php";

$source = "./sources";
$output = "./docs";
$generator = new Nexendrie\SiteGenerator\Generator($source, $output);
$generator->generate();
?>
```

Ignoring files and folders
==========================

By default, the generator ignores any .md files in folders named vendor, .git and tests. If you want to change that list, just set properties ignoredFiles and ignoredFolders on Generator instance. Bear in mind, that you are replacing the original list so you have to repeat things you want to keep. Example:


```php
<?php
declare(strict_types=1);

require __DIR__ . "/vendor/autoload.php";

$source = "./sources";
$output = "./docs";
$generator = new Nexendrie\SiteGenerator\Generator($source, $output);
$generator->ignoredFiles = ["abc.md"];
$generator->ignoredFolders = ["abc"];
$generator->generate();
?>
```

This will ignore in any files in folder abc and files named abc.md.

If you use the command line script, pass options --ignoreFile/--ignoreFolder for every file/folder you want to ignore. Example:


```bash
./vendor/bin/generate-site --source=doc --output=public --ignoreFile=abc.md --ignoreFile=def.md --ignoreFolder=abc --ignoreFolder=def
```

and any file in folders **abc** and **def** and files named **abc.md** and **def.md** will be ignored.


Advanced usage
==============

Site generator with every source file also looks for a meta file (file with same name but extension neon). You can set there some additional information for it there, like page's title.

```yaml
title: My page
```

It is possible to normalize the meta info and modify the generated page based on some meta info. Just add meta normalizer to Generator via method addMetaNormalizer. The method accepts a callback. The normalizer will receive 3 parameters: meta info (as array), html code (in string) and name of currently processed file (as string). Tip: declare first 2 parameters as passed by reference so you can modify them in your function.

Additional assets
=================

If your page needs additional assets (CSS stylesheets, JavaScript), just list them in the page's meta file and they will be copied to the output folder and the generated page will include them.

```yaml
styles:
    - style.css
scripts:
    - script.js
```

If you mention a local image in your page, the file will be copied to output folder.
