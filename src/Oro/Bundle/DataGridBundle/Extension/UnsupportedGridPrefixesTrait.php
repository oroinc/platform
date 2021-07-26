<?php

namespace Oro\Bundle\DataGridBundle\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The trait for data grid extensions that provide a possibility to configure a list of name prefixes
 * for data grids for which the extension should be disabled.
 */
trait UnsupportedGridPrefixesTrait
{
    /** @var string[] */
    protected $unsupportedGridPrefixes = [];

    /**
     * @param string $prefix
     */
    public function addUnsupportedGridPrefix($prefix)
    {
        $this->unsupportedGridPrefixes[] = $prefix;
    }

    /**
     * Checks if configuration is for supported grid prefix
     *
     * @param DatagridConfiguration $config
     *
     * @return bool
     */
    protected function isUnsupportedGridPrefix(DatagridConfiguration $config)
    {
        $gridName = $config->getName();

        return ArrayUtil::some(
            function ($prefix) use ($gridName) {
                return str_starts_with($gridName, $prefix);
            },
            $this->unsupportedGridPrefixes
        );
    }
}
