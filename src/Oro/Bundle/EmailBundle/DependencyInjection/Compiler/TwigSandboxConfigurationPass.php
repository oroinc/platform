<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

/**
 * Registers the following Twig functions and filters for the email templates rendering sandbox:
 * * oro_config_value
 * * oro_get_absolute_url
 * * oro_get_email_template
 * * oro_format
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFunctions(): array
    {
        return [
            'oro_config_value',
            'oro_get_absolute_url',
            'oro_get_email_template'
        ];
    }

    #[\Override]
    protected function getFilters(): array
    {
        return [
            'oro_format'
        ];
    }

    #[\Override]
    protected function getTags(): array
    {
        return [];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            'oro_config.twig.config_extension',
            'oro_email.twig.extension.email',
            'oro_ui.twig.extension.formatter'
        ];
    }
}
