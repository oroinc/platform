<?php

namespace Oro\Bundle\ActionBundle\Configuration;

/**
 * An interface for configuration providers.
 */
interface ConfigurationProviderInterface
{
    public function getConfiguration(): array;
}
