<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DefaultOwnerSetEvent extends Event
{
    const NAME = 'oro_integration.default_owner.set';

    /** @var Channel */
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

    /**
     * @return User
     */
    public function getDefaultUserOwner()
    {
        return $this->channel->getDefaultUserOwner();
    }
}
