<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

use ArrayObject;

/**
 * Container for runtime context data passed between runtime configuration providers.
 * Extends ArrayObject to allow both array and property-style access to context data.
 */
class RuntimeContext extends ArrayObject
{
    public function __construct(array $data = [])
    {
        parent::__construct($data, \ArrayObject::ARRAY_AS_PROPS);
    }
}
