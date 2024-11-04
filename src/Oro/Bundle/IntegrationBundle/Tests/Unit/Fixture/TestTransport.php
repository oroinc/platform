<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

class TestTransport implements TransportInterface
{
    #[\Override]
    public function init(Transport $transportEntity)
    {
    }

    #[\Override]
    public function getLabel()
    {
        return 'test label';
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return 'settings';
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return 'FQCN';
    }
}
