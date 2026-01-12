<?php

namespace Oro\Bundle\FormBundle\Config;

/**
 * Defines the contract for form configuration objects.
 *
 * Implementations must provide a method to serialize the configuration to an array format
 * suitable for use in templates or API responses.
 */
interface FormConfigInterface
{
    public function toArray();
}
