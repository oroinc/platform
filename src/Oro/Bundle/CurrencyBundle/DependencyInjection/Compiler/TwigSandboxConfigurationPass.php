<?php

namespace Oro\Bundle\CurrencyBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the "oro_format_price" Twig filter for the email templates rendering sandbox.
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritDoc}
     */
    protected function getFunctions(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilters(): array
    {
        return [
            'oro_format_price'
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
            'oro_currency.twig.currency'
        ];
    }
}
