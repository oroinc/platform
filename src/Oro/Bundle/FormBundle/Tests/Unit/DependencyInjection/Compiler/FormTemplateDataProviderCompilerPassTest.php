<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormTemplateDataProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class FormTemplateDataProviderCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new FormTemplateDataProviderCompilerPass(),
            FormTemplateDataProviderCompilerPass::REGISTRY_SERVICE,
            FormTemplateDataProviderCompilerPass::PROVIDER_TAG,
            'addProvider'
        );
    }
}
