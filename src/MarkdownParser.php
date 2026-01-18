<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator;

/**
 * MarkdownParser
 *
 * @author Jakub Konečný
 * @internal
 */
final class MarkdownParser extends \xenocrat\markdown\GithubMarkdown
{
    public function __construct()
    {
        $this->html5 = true;
        $this->keepListStartNumber = true;
        $this->enableNewlines = true;
    }
}
