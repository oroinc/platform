<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

/**
 * Test transport provider for integration testing.
 *
 * This provider implements the {@see TransportInterface} for testing purposes, providing
 * a minimal transport implementation with a test integration transport entity.
 */
class TestTransportProvider implements TransportInterface
{
    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return 'Oro\Bundle\TestFrameworkBundle\Entity\TestIntegrationTransport';
    }

    #[\Override]
    public function init(Transport $transportEntity)
    {
    }

    #[\Override]
    public function getLabel()
    {
    }

    #[\Override]
    public function getSettingsFormType()
    {
    }
}
