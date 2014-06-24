<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface as IntegrationInterface;

class IntegrationTypeWithoutIcon implements IntegrationInterface
{
    const TYPE = 'type2';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.type2.label';
    }
}
