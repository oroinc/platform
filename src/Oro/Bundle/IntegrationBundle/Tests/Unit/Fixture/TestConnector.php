<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

class TestConnector implements ConnectorInterface
{
    #[\Override]
    public function getLabel(): string
    {
        return 'test connector';
    }

    #[\Override]
    public function getImportEntityFQCN()
    {
        return 'testEntity';
    }

    #[\Override]
    public function getImportJobName()
    {
        return 'test job';
    }

    #[\Override]
    public function getType()
    {
        return 'test Type';
    }
}
