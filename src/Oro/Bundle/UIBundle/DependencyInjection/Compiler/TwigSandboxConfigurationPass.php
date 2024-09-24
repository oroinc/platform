<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the "oro_html_strip_tags" Twig filter for the email templates rendering sandbox.
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFunctions(): array
    {
        return [];
    }

    #[\Override]
    protected function getFilters(): array
    {
        return [
            'oro_html_strip_tags'
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
            'oro_ui.twig.html_tag'
        ];
    }
}
