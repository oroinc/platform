<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Behat\Stub\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;

class TestChannelType implements ChannelInterface
{
    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'Test Channel';
    }
}
