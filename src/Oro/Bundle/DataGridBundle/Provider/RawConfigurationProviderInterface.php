<?php

namespace Oro\Bundle\DataGridBundle\Provider;

/**
 * Interface for datagrid raw configuration providers
 */
interface RawConfigurationProviderInterface
{
    public const ROOT_SECTION = 'datagrids';

    public function getRawConfiguration(string $gridName): ?array;
}
