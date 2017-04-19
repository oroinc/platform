<?php

namespace Oro\Bundle\IntegrationBundle\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelActionEvent;

interface ChannelActionEventFactoryInterface
{
    /**
     * @param Channel $channel
     *
     * @return ChannelActionEvent
     */
    public function create(Channel $channel);
}
