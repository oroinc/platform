<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig filter to remove namespaces for a PHP class name:
 *   - basename
 */
class BasenameTwigExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('basename', [$this, 'basenameFilter'])
        ];
    }

    /**
     * @var string $value
     */
    public function basenameFilter(string $value): string
    {
        return basename(str_replace('\\', '/', $value));
    }
}
