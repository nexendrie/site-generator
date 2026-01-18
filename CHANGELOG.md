Version 0.9.0
- use dark mode in generated pages if requested by browser

Version 0.8.0
- raised minimal version of PHP to 8.1
- allowed symfony/options-resolver 6 and 7

Version 0.7.0
- dropped support for Nette 2.4
- raised minimal version of PHP to 7.4

Version 0.6.2
- allowed symfony/options-resolver 5

Version 0.6.1
- fixed Generator::$filesToProcess for nette/utils 2.5

Version 0.6.0
- README.md file is not ignored by default
- made it possible to add html lang attribute to generated pages
- generated pages contain doctype and viewport
- raised minimal required version of symfony/options-resolver to 4.3.0

Version 0.5.0
- raised minimal version of PHP to 7.2
- allowed Nette 3

Version 0.4.0
- raised minimal required version of symfony/options-resolver to 4.0.0
- marked class Generator as final
- files to process are now stored in publicly readable property Generator::$filesToProcess
- links to other .md files in sources are updated to link to generated .html pages
- made it possible to change list of ignored files and folders
- added options --ignoreFile and --ignoreFolder for command line script

Version 0.3.0
- raised minimal version of PHP to 7.1
- added dependency on symfony/options-resolver
- it is now possible to include stylesheets and scripts in generated pages
- Generator no longer tries to guess source and output folders
- added events onBeforeGenerate, onAfterGenerate and onCreatePage to Generator
- introduced meta normalizers for Generator
- local images mentioned in pages are now copied to output folder

Version 0.2.0
- raised minimal version of PHP to 7
- empty line at end of source files is ignored now
- generated pages no longer contain empty <title> if the title is not defined

Version 0.1.0
- initial version
