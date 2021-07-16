<?php

namespace Oro\Bundle\SearchBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;

/**
 * Abstract class of provider for search mapping configuration that is loaded from files.
 */
abstract class MappingConfigurationProviderAbstract extends PhpArrayConfigProvider
{
    /**
     * Gets website search mapping configuration.
     */
    abstract public function getConfiguration(): array;
}
