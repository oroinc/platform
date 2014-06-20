<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;


use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;

class IntegrationTypeWithoutIcon implements ChannelInterface
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
