<?php

namespace Oro\Component\Config\Resolver;

/**
 * Defines the contract for resolving configuration with context.
 *
 * Implementations of this interface are responsible for processing configuration
 * arrays and applying context-specific transformations or validations.
 */
interface ResolverInterface
{
    /**
     * @param array $config
     * @param array $context
     *
     * @return array
     */
    public function resolve(array $config, array $context = array());
}
