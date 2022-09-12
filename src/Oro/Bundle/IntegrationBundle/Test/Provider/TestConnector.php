<?php

namespace Oro\Bundle\IntegrationBundle\Test\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\TestIterator;

/**
 * Test Connector
 */
class TestConnector extends AbstractConnector implements TwoWaySyncConnectorInterface
{
    public const TYPE = 'connector1';
    private const JOB_IMPORT = 'integration_test_import';
    private const JOB_EXPORT = 'integration_test_export';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'oro_integration.connector.test.label';
    }

    public function getImportJobName(): string
    {
        return self::JOB_IMPORT;
    }

    public function getImportEntityFQCN(): string
    {
        return \stdClass::class;
    }

    protected function getConnectorSource(): \Iterator
    {
        return new TestIterator();
    }

    public function isAllowed(Channel $integration, array $processedConnectorsStatuses): bool
    {
        return true;
    }

    public function getExportJobName(): string
    {
        return self::JOB_EXPORT;
    }
}
