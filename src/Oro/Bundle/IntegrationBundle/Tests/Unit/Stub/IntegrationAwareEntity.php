<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MagentoBundle\Entity\IntegrationAwareInterface;

class IntegrationAwareEntity implements IntegrationAwareInterface
{
    /** @var Channel */
    protected $channel;

    /**
     * {@inheritdoc}
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannelName()
    {
        return $this->channel->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setChannel(Channel $integration)
    {
       $this->channel = $integration;

       return $this;
    }
}
