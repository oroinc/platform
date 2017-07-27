<?php

namespace Oro\Bundle\ConfigBundle\Provider\Value;

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
