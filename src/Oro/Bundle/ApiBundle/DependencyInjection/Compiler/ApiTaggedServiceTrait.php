<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;

/**
 * Provides a set of methods to simplify processing tagged services.
 */
trait ApiTaggedServiceTrait
{
    use TaggedServiceTrait;

    /**
     * Gets a value of the "requestType" attribute.
     */
    private function getRequestTypeAttribute(array $attributes): ?string
    {
        return $this->getAttribute($attributes, ApiContext::REQUEST_TYPE);
    }
}
