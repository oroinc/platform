<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

/**
 * Registers the following Twig functions and filters for the email templates rendering sandbox:
 * * oro_config_value
 * * oro_get_absolute_url
 * * oro_format
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritDoc}
     */
    protected function getFunctions(): array
    {
        return [
            'oro_config_value',
            'oro_get_absolute_url'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilters(): array
    {
        return [
            'oro_format'
        ];
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
            'oro_config.twig.config_extension',
            'oro_email.twig.extension.email',
            'oro_ui.twig.extension.formatter'
        ];
    }
}
