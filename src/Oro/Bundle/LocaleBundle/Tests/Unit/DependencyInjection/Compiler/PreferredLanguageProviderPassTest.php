<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\PreferredLanguageProviderPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class PreferredLanguageProviderPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new PreferredLanguageProviderPass(),
            PreferredLanguageProviderPass::CHAIN_PROVIDER_ID,
            PreferredLanguageProviderPass::TAG,
            'addProvider'
        );
    }
}
