<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;

class TestChannelType implements ChannelInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel()
    {
        return 'testLabel';
    }
}
