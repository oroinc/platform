<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\IntegrationBundle\Provider\ChannelTypeInterface;

class ChannelTypeManager
{
    /** @var ArrayCollection|ChannelTypeInterface[] */
    protected $channelTypes = [];

    public function __construct(array $channelTypes)
    {
        $this->channelTypes = new ArrayCollection($channelTypes);
    }

    /**
     * Return registered types
     *
     * @return ArrayCollection|ChannelTypeInterface[]
     */
    public function getRegisteredTypes()
    {
        return $this->channelTypes;
    }
}
