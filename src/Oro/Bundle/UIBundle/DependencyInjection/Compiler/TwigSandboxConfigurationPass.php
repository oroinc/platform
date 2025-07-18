<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 *  Registers Twig filters for the email templates rendering sandbox:
 *   - oro_html_strip_tags
 *   - oro_html_sanitize_basic
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
            'oro_html_strip_tags',
            'oro_html_sanitize_basic'
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
