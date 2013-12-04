<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

abstract class AbstractConnector implements ConnectorInterface
{
    /** @var TransportInterface */
    protected $transport;

    /** @var Transport */
    protected $transportSettings;

    /** @var bool */
    protected $isConnected = false;

    /**
     * {@inheritdoc}
     */
    public function configure(TransportInterface $realTransport, Transport $transportSettings)
    {
        $this->transport         = $realTransport;
        $this->transportSettings = $transportSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (!($this->transport && $this->transportSettings)) {
            throw new \LogicException('Connector does not configured correctly');
        }

        $transportSettings = $this->transportSettings->getSettingsBag();
        $this->isConnected = $this->transport->init($transportSettings);

        return $this->isConnected;
    }

    /**
     * Used to get/send data from/to remote channel using transport
     *
     * @param string $action
     * @param array  $params
     *
     * @return mixed
     */
    protected function call($action, $params = [])
    {
        if ($this->isConnected === false) {
            $this->connect();
        }

        $params = is_array($params) ? $params : [$params];

        return $this->transport->call($action, $params);
    }

    /**
     * Does not allow to serialize
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }
}
