<?php

namespace Oro\Bundle\IntegrationBundle\Action;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface ChannelActionHandlerInterface
{
    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function handleAction(Channel $channel);
}
