<?php

namespace Oro\Bundle\ReminderBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the following Twig functions for the email templates rendering sandbox:
 * * url
 * * path
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritDoc}
     */
    protected function getFunctions(): array
    {
        return [
            'url',
            'path'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTags(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            'twig.extension.routing'
        ];
    }
}
