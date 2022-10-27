<?php

namespace Oro\Bundle\ScopeBundle\Migration\Extension;

/**
 * ScopeExtensionAwareInterface should be implemented by migrations that depends on a ScopeExtension.
 */
interface ScopeExtensionAwareInterface
{
    public function setScopeExtension(ScopeExtension $scopeExtension);
}
