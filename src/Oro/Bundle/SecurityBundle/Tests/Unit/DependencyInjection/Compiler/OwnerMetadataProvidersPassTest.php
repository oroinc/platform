<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnerMetadataProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OwnerMetadataProvidersPassTest extends AbstractProvidersPassTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->compilerPass = new OwnerMetadataProvidersPass();
        $this->chainServiceId = OwnerMetadataProvidersPass::CHAIN_SERVICE_ID;
        $this->tagName = OwnerMetadataProvidersPass::TAG_NAME;
    }
}
