<?php

namespace Oro\Bundle\ActionBundle\Configuration;

/**
 * An interface for configuration providers.
 */
interface ConfigurationProviderInterface
{
    /**
     * @return array
     */
    public function getConfiguration(): array;
}
