<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\ConsoleGlobalOptionsCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class ConsoleGlobalOptionsCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $compilerPass = new ConsoleGlobalOptionsCompilerPass();

        $this->assertTaggedServicesRegistered(
            $compilerPass,
            [
                ConsoleGlobalOptionsCompilerPass::PROVIDER_REGISTRY,

            ],
            [
                ConsoleGlobalOptionsCompilerPass::PROVIDER_TAG,
            ],
            [
                'registerProvider',
            ]
        );
    }
}
