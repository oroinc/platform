<?php

namespace Oro\Component\Config\Cache;

/**
 * The base class for configuration that is an array and should be stored in a PHP file.
 */
abstract class PhpArrayConfigProvider extends PhpConfigProvider
{
    /**
     * {@inheritdoc}
     */
    protected function assertLoaderConfig($config): void
    {
        if (!\is_array($config)) {
            throw new \LogicException('Expected an array.');
        }
    }
}
