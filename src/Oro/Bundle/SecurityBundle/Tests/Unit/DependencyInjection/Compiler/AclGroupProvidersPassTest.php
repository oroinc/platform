<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclGroupProvidersPass;

class AclGroupProvidersPassTest extends AbstractProvidersPassTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->compilerPass = new AclGroupProvidersPass();
        $this->chainServiceId = AclGroupProvidersPass::CHAIN_SERVICE_ID;
        $this->tagName = AclGroupProvidersPass::TAG_NAME;
    }
}
