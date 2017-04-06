<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

class TestTransportProvider implements TransportInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'Oro\Bundle\TestFrameworkBundle\Entity\TestIntegrationTransport';
    }

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
    }
}
