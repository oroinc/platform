<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Behat\Stub\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Stub\TestTransport2Settings;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\IntegrationBundle\Tests\Behat\Stub\Form\Type\TestTransport2SettingsType;

class TestTransport2 implements TransportInterface
{
    #[\Override]
    public function init(Transport $transportEntity)
    {
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return TestTransport2SettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return TestTransport2Settings::class;
    }

    #[\Override]
    public function getLabel()
    {
        return 'Test Transport 2';
    }
}
