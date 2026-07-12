<?php
declare(strict_types=1);

namespace Nexendrie\SiteGenerator\Events;

use Nexendrie\SiteGenerator\Generator;

final readonly class PageGenerated
{
    public function __construct(public string $html, public Generator $generator, public string $filename)
    {
    }
}
