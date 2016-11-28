<?php

namespace Oro\Bundle\ScopeBundle\Migration\Extension;

/**
 * ScopeExtensionAwareInterface should be implemented by migrations that depends on a ScopeExtension.
 */
interface ScopeExtensionAwareInterface
{
    /**
     * @param ScopeExtension $scopeExtension
     */
    public function setScopeExtension(ScopeExtension $scopeExtension);
}
