<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\ChannelDeleteProviderInterface;

class TestChannelDeleteProvider implements ChannelDeleteProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedChannelType()
    {
        return 'test';
    }

    /**
     * {@inheritdoc}
     */
    public function processDelete(Channel $channel)
    {
        return true;
    }
}
