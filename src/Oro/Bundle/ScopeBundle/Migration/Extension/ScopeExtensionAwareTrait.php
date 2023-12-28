<?php

namespace Oro\Bundle\ScopeBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see ScopeExtensionAwareInterface}.
 */
trait ScopeExtensionAwareTrait
{
    /** @var ScopeExtension */
    protected $scopeExtension;

    public function setScopeExtension(ScopeExtension $scopeExtension)
    {
        $this->scopeExtension = $scopeExtension;
    }
}
