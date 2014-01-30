<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

class TypesRegistry
{
    /** @var ArrayCollection|ChannelInterface[] */
    protected $channelTypes = [];

    /** @var array|ArrayCollection[] */
    protected $transportTypes = [];

    /** @var array|ArrayCollection[] */
    protected $connectorTypes = [];

    public function __construct()
    {
        $this->channelTypes = new ArrayCollection();
    }

    /**
     * Set registered types
     *
     * @param string           $typeName
     * @param ChannelInterface $type
     *
     * @throws \LogicException
     * @return $this
     */
    public function addChannelType($typeName, ChannelInterface $type)
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
     * @return ArrayCollection|ChannelInterface[]
     */
    public function getRegisteredChannelTypes()
    {
        return $this->channelTypes;
    }

    /**
     * Collect available types for choice field
     *
     * @return array
     */
    public function getAvailableChannelTypesChoiceList()
    {
        $registry = $this;
        $types    = $registry->getRegisteredChannelTypes();
        $types    = $types->partition(
            function ($key, ChannelInterface $type) use ($registry) {
                return !$registry->getRegisteredTransportTypes($key)->isEmpty();
            }
        );

        /** @var ArrayCollection $types */
        $types  = $types[0];
        $keys   = $types->getKeys();
        $values = $types->map(
            function (ChannelInterface $type) {
                return $type->getLabel();
            }
        )->toArray();

        return array_combine($keys, $values);
    }

    /**
     * Register transport for channel type
     *
     * @param string             $typeName
     * @param string             $channelTypeName
     * @param TransportInterface $type
     *
     * @return $this
     * @throws \LogicException
     */
    public function addTransportType($typeName, $channelTypeName, TransportInterface $type)
    {
        if (!isset($this->transportTypes[$channelTypeName])) {
            $this->transportTypes[$channelTypeName] = new ArrayCollection();
        }

        if ($this->transportTypes[$channelTypeName]->containsKey($typeName)) {
            throw new \LogicException(
                sprintf(
                    'Trying to redeclare transport type "%s" for "%s" channel type.',
                    $typeName,
                    $channelTypeName
                )
            );
        }

        $this->transportTypes[$channelTypeName]->set($typeName, $type);

        return $this;
    }

    /**
     * @param string $channelType
     * @param string $transportType
     *
     * @return TransportInterface
     * @throws \LogicException
     */
    public function getTransportType($channelType, $transportType)
    {
        if (!isset($this->transportTypes[$channelType])) {
            throw new \LogicException(sprintf('Transports not found for channel "%s".', $channelType));
        } elseif (!$this->transportTypes[$channelType]->containsKey($transportType)) {
            throw new \LogicException(
                sprintf(
                    'Transports type "%s"  not found for channel "%s".',
                    $transportType,
                    $channelType
                )
            );
        }

        return $this->transportTypes[$channelType]->get($transportType);
    }

    /**
     * Returns registered transports for channel by type
     *
     * @param string $channelType
     *
     * @return ArrayCollection
     */
    public function getRegisteredTransportTypes($channelType)
    {
        if (!isset($this->transportTypes[$channelType])) {
            $this->transportTypes[$channelType] = new ArrayCollection();
        }

        return $this->transportTypes[$channelType];
    }

    /**
     * Collect available types for choice field
     *
     * @param string $channelType
     *
     * @return array
     */
    public function getAvailableTransportTypesChoiceList($channelType)
    {
        $types  = $this->getRegisteredTransportTypes($channelType);
        $keys   = $types->getKeys();
        $values = $types->map(
            function (TransportInterface $type) {
                return $type->getLabel();
            }
        )->toArray();

        return array_combine($keys, $values);
    }

    /**
     * @param Transport $transportEntity
     * @param string    $channelType
     * @param bool      $typeNameOnly
     *
     * @throws \LogicException
     * @return string|TransportInterface
     */
    public function getTransportTypeBySettingEntity(Transport $transportEntity, $channelType, $typeNameOnly = false)
    {
        $class = ClassUtils::getClass($transportEntity);
        $types = $this->getRegisteredTransportTypes($channelType)->filter(
            function (TransportInterface $transport) use ($transportEntity, $class) {
                return $transport->getSettingsEntityFQCN() === $class;
            }
        );
        $keys  = $types->getKeys();
        $key   = reset($keys);

        if ($key === false) {
            throw new \LogicException(sprintf('Transport not found for channel type "%s".', $channelType));
        }
        if ($typeNameOnly) {
            return $key;
        }

        return $types->first();
    }

    /**
     * Register connector for channel type
     *
     * @param string             $typeName
     * @param string             $channelTypeName
     * @param ConnectorInterface $type
     *
     * @throws \LogicException
     * @return $this
     */
    public function addConnectorType($typeName, $channelTypeName, ConnectorInterface $type)
    {
        if (!isset($this->connectorTypes[$channelTypeName])) {
            $this->connectorTypes[$channelTypeName] = new ArrayCollection();
        }

        if ($this->connectorTypes[$channelTypeName]->containsKey($typeName)) {
            throw new \LogicException(
                sprintf(
                    'Trying to redeclare connector type "%s" for "%s" channel type.',
                    $typeName,
                    $channelTypeName
                )
            );
        }

        $this->connectorTypes[$channelTypeName]->set($typeName, $type);

        return $this;
    }

    /**
     * @param string $channelType
     * @param string $type
     *
     * @return ConnectorInterface
     * @throws \LogicException
     */
    public function getConnectorType($channelType, $type)
    {
        if (!isset($this->connectorTypes[$channelType])) {
            throw new \LogicException(sprintf('Connectors not found for channel "%s".', $channelType));
        } elseif (!$this->connectorTypes[$channelType]->containsKey($type)) {
            throw new \LogicException(
                sprintf(
                    'Connector type "%s"  not found for channel "%s".',
                    $type,
                    $channelType
                )
            );
        }

        return $this->connectorTypes[$channelType]->get($type);
    }

    /**
     * Returns registered connectors for channel by type
     *
     * @param string        $channelType
     * @param null|\Closure $filterClosure
     *
     * @return ArrayCollection
     */
    public function getRegisteredConnectorsTypes($channelType, $filterClosure = null)
    {
        if (!isset($this->connectorTypes[$channelType])) {
            $this->connectorTypes[$channelType] = new ArrayCollection();
        }

        if (is_callable($filterClosure)) {
            return $this->connectorTypes[$channelType]->filter($filterClosure);
        } else {
            return $this->connectorTypes[$channelType];
        }
    }

    /**
     * Collect available types for choice field
     *
     * @param string        $channelType
     * @param null|\Closure $filterClosure
     *
     * @return array
     */
    public function getAvailableConnectorsTypesChoiceList($channelType, $filterClosure = null)
    {
        $types  = $this->getRegisteredConnectorsTypes($channelType, $filterClosure);
        $keys   = $types->getKeys();
        $values = $types->map(
            function (ConnectorInterface $type) {
                return $type->getLabel();
            }
        )->toArray();

        return array_combine($keys, $values);
    }
}
