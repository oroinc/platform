<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

/**
 * Thrown when entity fallback provider was not found.
 */
class FallbackProviderNotFoundException extends \Exception
{
    /**
     * @param string $fallbackKey
     */
    public function __construct($fallbackKey)
    {
        $message = sprintf(
            'Fallback provider for fallback with identification key "%s" not found. 
            Please make sure to register a provider with tag: name:"oro_entity.fallback_provider" and id:"%s"',
            $fallbackKey,
            $fallbackKey
        );
        parent::__construct($message);
    }
}
