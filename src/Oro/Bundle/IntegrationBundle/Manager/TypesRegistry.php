<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface as IntegrationInterface;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\DefaultOwnerTypeAwareInterface;
use Oro\Bundle\IntegrationBundle\Provider\ForceConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

class TypesRegistry
{
    /** @var ArrayCollection|IntegrationInterface[] */
    protected $integrationTypes = [];

    /** @var array|ArrayCollection[] */
    protected $transportTypes = [];

    /** @var array|ArrayCollection[] */
    protected $connectorTypes = [];

    public function __construct()
    {
        $this->integrationTypes = new ArrayCollection();
    }

    /**
     * Set registered types
     *
     * @param string               $typeName
     * @param IntegrationInterface $type
     *
     * @throws \LogicException
     * @return $this
     */
    public function addChannelType($typeName, IntegrationInterface $type)
    {
        if (!$this->integrationTypes->containsKey($typeName)) {
            $this->integrationTypes->set($typeName, $type);
        } else {
            throw new LogicException(sprintf('Trying to redeclare integration type "%s".', $typeName));
        }

        return $this;
    }

    /**
     * Return registered types
     *
     * @return ArrayCollection|IntegrationInterface[]
     */
    public function getRegisteredChannelTypes()
    {
        return $this->integrationTypes;
    }

    /**
     * @param string $typeName
     *
     * @return IntegrationInterface
     */
    public function getIntegrationByType($typeName)
    {
        if ($this->integrationTypes->containsKey($typeName)) {
            return $this->integrationTypes->get($typeName);
        } else {
            throw new LogicException(
                sprintf(
                    'Integration type "%s" not found.',
                    $typeName
                )
            );
        }
    }

    /**
     * Collect available types for choice field
     *
     * @return array
     */
    public function getAvailableChannelTypesChoiceList()
    {
        /** @var ArrayCollection $types */
        $types  = $this->getAvailableIntegrationTypes();
        $values = $types->getKeys();
        $labels = $types->map(
            function (IntegrationInterface $type) {
                return $type->getLabel();
            }
        )->toArray();

        return array_combine($labels, $values);
    }

    /**
     * Collect available types for choice field with icon
     *
     * @return array
     */
    public function getAvailableIntegrationTypesDetailedData()
    {
        /** @var ArrayCollection $types */
        $types  = $this->getAvailableIntegrationTypes();
        $keys   = $types->getKeys();
        $values = $types->map(
            function (IntegrationInterface $type) {
                $result = ['label' => $type->getLabel()];
                if ($type instanceof IconAwareIntegrationInterface) {
                    $result['icon'] = $type->getIcon();
                }
                return $result;
            }
        )->toArray();

        return array_combine($keys, $values);
    }

    /**
     * Register transport for integration type
     *
     * @param string             $typeName
     * @param string             $integrationTypeName
     * @param TransportInterface $type
     *
     * @return $this
     * @throws \LogicException
     */
    public function addTransportType($typeName, $integrationTypeName, TransportInterface $type)
    {
        if (!isset($this->transportTypes[$integrationTypeName])) {
            $this->transportTypes[$integrationTypeName] = new ArrayCollection();
        }

        if ($this->transportTypes[$integrationTypeName]->containsKey($typeName)) {
            throw new LogicException(
                sprintf(
                    'Trying to redeclare transport type "%s" for "%s" integration type.',
                    $typeName,
                    $integrationTypeName
                )
            );
        }

        $this->transportTypes[$integrationTypeName]->set($typeName, $type);

        return $this;
    }

    /**
     * @param string $integrationTypeName
     * @param string $transportType
     *
     * @return TransportInterface
     * @throws \LogicException
     */
    public function getTransportType($integrationTypeName, $transportType)
    {
        if (!isset($this->transportTypes[$integrationTypeName])) {
            throw new LogicException(sprintf('Transports not found for integration "%s".', $integrationTypeName));
        } elseif (!$this->transportTypes[$integrationTypeName]->containsKey($transportType)) {
            throw new LogicException(
                sprintf(
                    'Transports type "%s"  not found for integration "%s".',
                    $transportType,
                    $integrationTypeName
                )
            );
        }

        return $this->transportTypes[$integrationTypeName]->get($transportType);
    }

    /**
     * Returns registered transports for integration by type
     *
     * @param string $integrationType
     *
     * @return ArrayCollection
     */
    public function getRegisteredTransportTypes($integrationType)
    {
        if (!isset($this->transportTypes[$integrationType])) {
            $this->transportTypes[$integrationType] = new ArrayCollection();
        }

        return $this->transportTypes[$integrationType];
    }

    /**
     * Collect available types for choice field
     *
     * @param string $integrationType
     *
     * @return array
     */
    public function getAvailableTransportTypesChoiceList($integrationType)
    {
        $types  = $this->getRegisteredTransportTypes($integrationType);
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
     * @param string    $integrationType
     * @param bool      $typeNameOnly
     *
     * @throws \LogicException
     * @return string|TransportInterface
     */
    public function getTransportTypeBySettingEntity(Transport $transportEntity, $integrationType, $typeNameOnly = false)
    {
        $class = ClassUtils::getClass($transportEntity);
        $types = $this->getRegisteredTransportTypes($integrationType)->filter(
            function (TransportInterface $transport) use ($transportEntity, $class) {
                return $transport->getSettingsEntityFQCN() === $class;
            }
        );
        $keys  = $types->getKeys();
        $key   = reset($keys);

        if ($key === false) {
            throw new LogicException(sprintf('Transport not found for integration type "%s".', $integrationType));
        }
        if ($typeNameOnly) {
            return $key;
        }

        return $types->first();
    }

    /**
     * Register connector for integration type
     *
     * @param string             $typeName
     * @param string             $integrationTypeName
     * @param ConnectorInterface $type
     *
     * @throws \LogicException
     * @return $this
     */
    public function addConnectorType($typeName, $integrationTypeName, ConnectorInterface $type)
    {
        if (!isset($this->connectorTypes[$integrationTypeName])) {
            $this->connectorTypes[$integrationTypeName] = new ArrayCollection();
        }

        if ($this->connectorTypes[$integrationTypeName]->containsKey($typeName)) {
            throw new LogicException(
                sprintf(
                    'Trying to redeclare connector type "%s" for "%s" integration type.',
                    $typeName,
                    $integrationTypeName
                )
            );
        }

        $this->connectorTypes[$integrationTypeName]->set($typeName, $type);

        return $this;
    }

    /**
     * @param string $integrationType
     * @param string $type
     *
     * @return ConnectorInterface
     *
     * @throws LogicException
     */
    public function getConnectorType($integrationType, $type)
    {
        if (!isset($this->connectorTypes[$integrationType])) {
            throw new LogicException(sprintf('Connectors not found for integration "%s".', $integrationType));
        } elseif (!$this->connectorTypes[$integrationType]->containsKey($type)) {
            throw new LogicException(
                sprintf(
                    'Connector type "%s"  not found for integration "%s".',
                    $type,
                    $integrationType
                )
            );
        }

        return $this->connectorTypes[$integrationType]->get($type);
    }

    /**
     * Returns registered connectors for integration by type
     *
     * @param string        $integrationType
     * @param null|\Closure $filterClosure
     *
     * @return ArrayCollection
     */
    public function getRegisteredConnectorsTypes($integrationType, $filterClosure = null)
    {
        if (!isset($this->connectorTypes[$integrationType])) {
            $this->connectorTypes[$integrationType] = new ArrayCollection();
        }

        if (is_callable($filterClosure)) {
            return $this->connectorTypes[$integrationType]->filter($filterClosure);
        } else {
            return $this->connectorTypes[$integrationType];
        }
    }

    /**
     * Collect available types for choice field
     *
     * @param string        $integrationType
     * @param null|\Closure $filterClosure
     *
     * @return array
     */
    public function getAvailableConnectorsTypesChoiceList($integrationType, $filterClosure = null)
    {
        $types  = $this->getRegisteredConnectorsTypes($integrationType, $filterClosure);
        $keys   = $types->getKeys();
        $values = $types->map(
            function (ConnectorInterface $type) {
                return $type->getLabel();
            }
        )->toArray();

        return array_combine($keys, $values);
    }

    /**
     * Checks if there is at least one connector that supports force sync.
     *
     * @param string $integrationType
     *
     * @return boolean
     */
    public function supportsForceSync($integrationType)
    {
        $connectors = $this->getRegisteredConnectorsTypes($integrationType);

        foreach ($connectors as $connector) {
            if ($connector instanceof ForceConnectorInterface) {
                if ($connector->supportsForceSync()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if there is at least one connector.
     *
     * @param string $integrationType
     *
     * @return boolean
     */
    public function supportsSync($integrationType)
    {
        $connectors = $this->getRegisteredConnectorsTypes($integrationType);

        return $connectors->count() > 0;
    }

    /**
     * Returns type of default owner for entities created by this integration.
     *
     * @param string|null $integrationType
     *
     * @return string 'user'\'business_unit'
     */
    public function getDefaultOwnerType($integrationType = null)
    {
        if ($integrationType === null) {
            return DefaultOwnerTypeAwareInterface::USER;
        }
        $type = $this->integrationTypes[$integrationType];

        if ($type instanceof DefaultOwnerTypeAwareInterface) {
            return $type->getDefaultOwnerType();
        }

        return DefaultOwnerTypeAwareInterface::USER;
    }

    /**
     * @return array
     */
    protected function getAvailableIntegrationTypes()
    {
        $registry = $this;
        $types    = $registry->getRegisteredChannelTypes();
        $types    = $types->partition(
            function ($key, IntegrationInterface $type) use ($registry) {
                return !$registry->getRegisteredTransportTypes($key)->isEmpty();
            }
        );
        return $types[0];
    }
}
