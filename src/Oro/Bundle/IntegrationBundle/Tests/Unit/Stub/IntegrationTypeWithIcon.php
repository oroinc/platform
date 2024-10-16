<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface as IntegrationInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class IntegrationTypeWithIcon implements IntegrationInterface, IconAwareIntegrationInterface
{
    #[\Override]
    public function getLabel()
    {
        return 'oro.type1.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/acmedemo/img/logo.png';
    }
}
