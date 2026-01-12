<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Defines the contract for integration types that provide custom icons for UI display.
 *
 * Integration types implementing this interface can supply a custom icon path that will be
 * displayed in the user interface to visually represent the integration type. This enhances
 * the user experience by providing recognizable branding for different integration types.
 */
interface IconAwareIntegrationInterface
{
    /**
     * Returns icon path for UI, should return value like 'bundles/acmedemo/img/logo.png'
     * Relative path to assets helper
     *
     * @return string
     */
    public function getIcon();
}
