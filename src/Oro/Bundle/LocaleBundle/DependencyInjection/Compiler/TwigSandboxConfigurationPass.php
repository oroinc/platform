<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Compiler pass that collects extensions by `oro_email.email_renderer` tag
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';
    const DATE_FORMAT_EXTENSION_SERVICE_KEY = 'oro_locale.twig.date_time';
    const NAME_FORMAT_EXTENSION_SERVICE_KEY = 'oro_entity.twig.extension.entity';
    const INTL_EXTENSION_SERVICE_KEY = 'twig.extension.intl';
    const LOCALE_ADDRESS = 'oro_locale.twig.address';
    const DATETIME_ORGANIZATION_FORMAT_EXTENSION_SERVICE_KEY = 'oro_locale.twig.date_time_organization';
    const NUMBER_EXTENSION_SERVICE_KEY = 'oro_locale.twig.number';

    /**
     * {@inheritDoc}
     */
    protected function getFilters()
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
    protected function getFunctions()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        return [
            self::LOCALE_ADDRESS,
            self::DATE_FORMAT_EXTENSION_SERVICE_KEY,
            self::NAME_FORMAT_EXTENSION_SERVICE_KEY,
            self::NUMBER_EXTENSION_SERVICE_KEY,
            self::INTL_EXTENSION_SERVICE_KEY, // Register Intl twig extension required for our date format extension
            self::DATETIME_ORGANIZATION_FORMAT_EXTENSION_SERVICE_KEY
        ];
    }
}
