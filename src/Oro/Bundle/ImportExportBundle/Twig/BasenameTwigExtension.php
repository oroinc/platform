<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

class BasenameTwigExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('basename', [$this, 'basenameFilter'])
        ];
    }

    /**
     * @var string $value
     *
     * @return string
     */
    public function basenameFilter(string $value): string
    {
        return basename(str_replace('\\', '/', $value));
    }
}
