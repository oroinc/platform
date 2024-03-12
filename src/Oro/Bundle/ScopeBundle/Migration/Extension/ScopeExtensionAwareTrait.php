<?php

namespace Oro\Bundle\ScopeBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see ScopeExtensionAwareInterface}.
 */
trait ScopeExtensionAwareTrait
{
    private ScopeExtension $scopeExtension;

    public function setScopeExtension(ScopeExtension $scopeExtension): void
    {
        $this->scopeExtension = $scopeExtension;
    }
}
