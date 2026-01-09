<?php

namespace Oro\Bundle\ConfigBundle\Provider\Value;

/**
 * Defines the contract for providing dynamic configuration values.
 *
 * Implementations of this interface supply configuration values that are computed or
 * retrieved dynamically at runtime rather than being statically defined. Providers must
 * return values of allowed types: scalar values, booleans, or arrays. This interface is
 * used to support configuration fields that need to derive their values from external
 * sources such as database entities, system state, or other dynamic data.
 */
interface ValueProviderInterface
{
    /**
     * Should return only types allowed in config
     * Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder::ALLOWED_TYPES
     * 'scalar', 'boolean', 'array'
     *
     * @return mixed|null
     */
    public function getValue();
}
