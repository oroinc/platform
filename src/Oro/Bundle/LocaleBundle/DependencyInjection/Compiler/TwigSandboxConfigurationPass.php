<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the following Twig filters for the email templates rendering sandbox:
 * * oro_format_address
 * * oro_format_date
 * * oro_format_time
 * * oro_format_datetime
 * * oro_format_datetime_organization
 * * oro_format_name
 * * date
 * * oro_format_currency
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritDoc}
     */
    protected function getFilters(): array
    {
        return [
            'oro_format_address',
            'oro_format_date',
            'oro_format_time',
            'oro_format_datetime',
            'oro_format_datetime_organization',
            'oro_format_name',
            'date',
            'oro_format_currency'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFunctions(): array
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
            'oro_locale.twig.address',
            'oro_locale.twig.date_time',
            'oro_entity.twig.extension.entity',
            'oro_locale.twig.number',
            'oro_locale.twig.date_time_organization'
        ];
    }
}
