<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Connector;

abstract class AbstractConnector implements ConnectorInterface
{
    /** @var TransportInterface */
    protected $transport;

    /** @var Connector */
    protected $connectorEntity = null;

    /** @var bool */
    protected $isConnected = false;

    /**
     * @param TransportInterface $transport
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (is_null($this->connectorEntity)) {
            throw new \Exception('There\'s no connector entity specified in connector');
        }

        $transportSettings = $this->connectorEntity
            ->getTransport()
            ->getSettingsBag();

        $this->isConnected = $this->transport->init($transportSettings);

        return $this->isConnected;
    }

    /**
     * Used to get/send data from/to remote channel using transport
     *
     * @param string $action
     * @param array $params
     * @return mixed
     */
    protected function call($action, $params = [])
    {
        if ($this->isConnected === false) {
            $this->connect();
        }

        return $this->transport->call($action, $params);
    }

    /**
     * @param Connector $connector
     */
    public function setConnectorEntity(Connector $connector)
    {
        $this->connectorEntity = $connector;
    }
}
