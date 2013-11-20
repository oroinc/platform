<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\IntegrationBundle\Provider\ChannelTypeInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportTypeInterface;

class TypesRegistry
{
    /** @var ArrayCollection|ChannelTypeInterface[] */
    protected $channelTypes = [];

    /** @var array|ArrayCollection[] */
    protected $transportTypes = [];

    public function __construct()
    {
        $this->channelTypes = new ArrayCollection();
    }

    /**
     * Set registered types
     *
     * @param string               $typeName
     * @param ChannelTypeInterface $type
     *
     * @throws \LogicException
     * @return $this
     */
    public function addChannelType($typeName, ChannelTypeInterface $type)
    {
        if (!$this->channelTypes->containsKey($typeName)) {
            $this->channelTypes->set($typeName, $type);
        } else {
            throw new \LogicException(sprintf('Trying to redeclare channel type "%s".', $typeName));
        }

        return $this;
    }

    /**
     * Return registered types
     *
     * @return ArrayCollection|ChannelTypeInterface[]
     */
    public function getRegisteredChannelTypes()
    {
        return $this->channelTypes;
    }

    /**
     * Register transport for channel type
     *
     * @param string                 $typeName
     * @param string                 $channelTypeName
     * @param TransportTypeInterface $type
     *
     * @return $this
     * @throws \LogicException
     */
    public function addTransportType($typeName, $channelTypeName, TransportTypeInterface $type)
    {
        if (!isset($this->transportTypes[$channelTypeName])) {
            $this->transportTypes[$channelTypeName] = new ArrayCollection();
        }

        if ($this->transportTypes[$channelTypeName]->containsKey($typeName)) {
            throw new \LogicException(sprintf(
                'Trying to redeclare transport type "%s" for "%s" channel type.',
                $typeName,
                $channelTypeName
            ));
        }

        $this->transportTypes[$channelTypeName]->set($typeName, $type);

        return $this;
    }

    /**
     * Returns registered transports for channel by type
     *
     * @param string $channelType
     *
     * @return ArrayCollection
     * @throws \LogicException
     */
    public function getRegisteredTransportTypes($channelType)
    {
        if ($this->channelTypes->containsKey($channelType)) {
            return $this->transportTypes[$channelType];
        }

        throw  new \LogicException(sprintf('Channel type "%s" not found.', $channelType));
    }

    /**
     * @param string $channelType
     * @param string $transportType
     *
     * @return TransportTypeInterface
     * @throws \LogicException
     */
    public function getTransportType($channelType, $transportType)
    {
        if (!isset($this->transportTypes[$channelType])) {
            throw new \LogicException(sprintf('Transports not found for channel "%s".', $channelType));
        } elseif (!$this->transportTypes[$channelType]->containsKey($transportType)) {
            throw new \LogicException(sprintf(
                'Transports type "%s"  not found for channel "%s".',
                $transportType,
                $channelType
            ));
        }

        return $this->transportTypes[$channelType]->get($transportType);
    }
}
