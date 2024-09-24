<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler\Stub;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

class TwigSandboxConfigurationPassStub extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFilters(): array
    {
        return [
            'filter1',
            'filter2'
        ];
    }

    #[\Override]
    protected function getFunctions(): array
    {
        return [
            'function1',
            'function2'
        ];
    }

    #[\Override]
    protected function getTags(): array
    {
        return [
            'tag1',
            'tag2'
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            'extension1',
            'extension2'
        ];
    }
}
