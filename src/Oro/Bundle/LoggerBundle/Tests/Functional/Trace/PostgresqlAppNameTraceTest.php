<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Trace;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\LoggerBundle\Trace\TraceManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PostgresqlAppNameTraceTest extends WebTestCase
{
    private TraceManager $traceManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->doctrine = self::getContainer()->get('doctrine');
        $connection = $this->doctrine->getConnection();
        $databasePlatform = $connection->getDatabasePlatform()->getName();

        if (DatabasePlatformInterface::DATABASE_POSTGRESQL !== $databasePlatform) {
            self::markTestSkipped('This test requires PostgreSQL');
        }

        /** @var TraceManager $traceManager */
        $traceManager = self::getContainer()->get('oro_logger.trace.manager');
        $this->traceManager = $traceManager;
    }

    public function testTraceManagerSetsCustomTrace(): void
    {
        $expectedTrace = '77777777777777777777777777777777';
        $this->traceManager->set($expectedTrace);

        $pgAppName = $this->getAppName();
        self::assertSame($expectedTrace, $pgAppName);
        self::assertSame($expectedTrace, $this->traceManager->get());
    }

    public function testTraceManagerGeneratesAndSetsTrace(): void
    {
        $this->traceManager->set();

        $trace = $this->traceManager->get();
        self::assertNotNull($trace);

        $pgAppName = $this->getAppName();
        self::assertSame($trace, $pgAppName);
        self::assertSame($trace, $this->traceManager->get());
    }

    private function getAppName(): ?string
    {
        $connection = $this->doctrine->getConnection();

        return $connection->executeQuery("SHOW application_name")->fetchOne();
    }
}
