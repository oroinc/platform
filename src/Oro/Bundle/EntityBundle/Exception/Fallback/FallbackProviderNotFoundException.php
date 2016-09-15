<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityFallbackCompilerPass;

class FallbackProviderNotFoundException extends \Exception
{
    /**
     * @param string $fallbackKey
     */
    public function __construct($fallbackKey)
    {
        $message = sprintf(
            'Fallback provider for fallback with identification key "%s" not found. 
            Please make sure to register a provider with tag: name:"%s" and id:"%s"',
            $fallbackKey,
            EntityFallbackCompilerPass::PROVIDER_TAG,
            $fallbackKey
        );
        parent::__construct($message);
    }
}
