<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormHandlerCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class FormHandlerCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new FormHandlerCompilerPass(),
            FormHandlerCompilerPass::REGISTRY_SERVICE,
            FormHandlerCompilerPass::PROVIDER_TAG,
            'addHandler'
        );
    }
}
