<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\ChannelDeleteProviderInterface;

class TestChannelDeleteProvider implements ChannelDeleteProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupport($channelType)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRelatedData(Channel $channel)
    {
    }
}
