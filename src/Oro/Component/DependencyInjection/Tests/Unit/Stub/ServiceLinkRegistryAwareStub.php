<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Stub;

use Oro\Component\DependencyInjection\ServiceLinkRegistry;
use Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface;

class ServiceLinkRegistryAwareStub implements ServiceLinkRegistryAwareInterface
{
    public function setServiceLinkRegistry(ServiceLinkRegistry $serviceLinkAliasRegistry)
    {
        // a stub
    }
}
