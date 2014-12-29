<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

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
