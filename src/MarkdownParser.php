<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

/**
 * MarkdownParser
 *
 * @author Jakub Konečný
 * @internal
 */
final class MarkdownParser extends \cebe\markdown\GithubMarkdown {
  /** @var bool */
  public $html5 = true;
  /** @var bool */
  public $keepListStartNumber = true;
  /** @var bool */
  public $enableNewlines = true;

  /**
   * @param string $text
   */
  public function parse($text): string {
    $markup = parent::parse($text);
    if(substr($markup, -1) === PHP_EOL) {
      $markup = substr($markup, 0, -1);
    }
    return $markup;
  }
}
?>