<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class TwoWaySyncEnableEvent extends Event
{
    const NAME = 'oro_integration.two_way_sync.set_on';

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @param Channel $channel
     */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }
}
