<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler\Stub;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

class TwigSandboxConfigurationPassStub extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritDoc}
     */
    protected function getFilters()
    {
        return [
            'filter1',
            'filter2'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFunctions()
    {
        return [
            'function1',
            'function2'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        return [
            'extension1',
            'extension2'
        ];
    }
}
