<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

class TestConnector implements ConnectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'test connector';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return 'testEntity';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return 'test job';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'test Type';
    }
}
