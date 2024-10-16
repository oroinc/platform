<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Behat\Stub\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Stub\TestTransport1Settings;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\IntegrationBundle\Tests\Behat\Stub\Form\Type\TestTransport1SettingsType;

class TestTransport1 implements TransportInterface
{
    #[\Override]
    public function init(Transport $transportEntity)
    {
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return TestTransport1SettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return TestTransport1Settings::class;
    }

    #[\Override]
    public function getLabel()
    {
        return 'Test Transport 1';
    }
}
