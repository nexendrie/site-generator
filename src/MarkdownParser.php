<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

/**
 * MarkdownParser
 *
 * @author Jakub Konečný
 * @internal
 */
final class MarkdownParser extends \xenocrat\markdown\GithubMarkdown {
  public function __construct() {
    $this->html5 = true;
    $this->keepListStartNumber = true;
    $this->enableNewlines = true;
  }

  public function parse($text): string {
    $markup = parent::parse($text);
    if(substr($markup, -1) === PHP_EOL) {
      $markup = substr($markup, 0, -1);
    }
    return $markup;
  }
}
?>