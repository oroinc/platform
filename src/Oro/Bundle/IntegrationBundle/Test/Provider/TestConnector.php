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

    #[\Override]
    public function getType(): string
    {
        return self::TYPE;
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro_integration.connector.test.label';
    }

    #[\Override]
    public function getImportJobName(): string
    {
        return self::JOB_IMPORT;
    }

    #[\Override]
    public function getImportEntityFQCN(): string
    {
        return \stdClass::class;
    }

    #[\Override]
    protected function getConnectorSource(): \Iterator
    {
        return new TestIterator();
    }

    public function isAllowed(Channel $integration, array $processedConnectorsStatuses): bool
    {
        return true;
    }

    #[\Override]
    public function getExportJobName(): string
    {
        return self::JOB_EXPORT;
    }
}
