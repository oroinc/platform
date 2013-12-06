<?php

namespace Oro\Bundle\IntegrationBundle\Guesser;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\TransportTypeInterface;

class TransportGuesser
{
    /** @var TypesRegistry */
    protected $registry;

    public function __construct(TypesRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Try to find transport for given channel instance
     *
     * @param Channel $channel
     *
     * @return TransportTypeInterface
     */
    public function guess(Channel $channel)
    {
        return $this->registry->getTransportTypeBySettingEntity($channel->getTransport(), $channel->getType());
    }
}
