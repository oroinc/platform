<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelUpdateEvent extends Event
{
    const NAME = 'oro_integration.channel_update';

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var Channel
     */
    protected $oldState;

    /**
     * @param Channel $channel
     * @param Channel $oldState
     */
    public function __construct(Channel $channel, Channel $oldState)
    {
        $this->channel = $channel;
        $this->oldState = $oldState;
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return Channel
     */
    public function getOldState()
    {
        return $this->oldState;
    }
}
