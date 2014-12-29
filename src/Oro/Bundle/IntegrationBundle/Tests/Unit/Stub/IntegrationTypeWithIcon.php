<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface as IntegrationInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class IntegrationTypeWithIcon implements IntegrationInterface, IconAwareIntegrationInterface
{
    const TYPE = 'type1';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.type1.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/acmedemo/img/logo.png';
    }
}
