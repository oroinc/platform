<?php

declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Tests\Functional\EventListener;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\MigrationBundle\EventListener\ResetMigrationVersionListener;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ResetMigrationVersionListenerTest extends WebTestCase
{
    private const string BUNDLE_NAME = 'AcmeTestBundle';
    private const string VERSION = 'v7_1_0_0';
    private const string NEW_VERSION = 'v7_0_2_0';
    private const string MIGRATION_TABLE = CreateMigrationTableMigration::MIGRATION_TABLE;

    private Connection $connection;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->connection = self::getContainer()->get('doctrine.dbal.default_connection');
    }

    private function createListener(string $bundleName, string $version): ResetMigrationVersionListener
    {
        return new ResetMigrationVersionListener($this->connection, $bundleName, $version, self::NEW_VERSION);
    }

    private function insertMigrationRow(string $bundleName, string $version): void
    {
        $this->connection->insert(self::MIGRATION_TABLE, [
            'bundle' => $bundleName,
            'version' => $version,
            'loaded_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    private function fetchMigrationRows(string $bundleName, string $version): array
    {
        return $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT * FROM "%s" WHERE bundle = :bundle AND version = :version',
                self::MIGRATION_TABLE
            ),
            ['bundle' => $bundleName, 'version' => $version]
        );
    }

    public function testOnPreUpRenamesRowWhenVersionMatches(): void
    {
        $this->insertMigrationRow(self::BUNDLE_NAME, self::VERSION);

        $listener = $this->createListener(self::BUNDLE_NAME, self::VERSION);
        $listener->onPreUp(new PreMigrationEvent($this->connection));

        self::assertCount(0, $this->fetchMigrationRows(self::BUNDLE_NAME, self::VERSION));
        self::assertCount(1, $this->fetchMigrationRows(self::BUNDLE_NAME, self::NEW_VERSION));
    }

    public function testOnPreUpDoesNotRenameRowWhenVersionDoesNotMatch(): void
    {
        $this->insertMigrationRow(self::BUNDLE_NAME, 'v7_0_3_0');

        $listener = $this->createListener(self::BUNDLE_NAME, self::VERSION);
        $listener->onPreUp(new PreMigrationEvent($this->connection));

        $rows = $this->fetchMigrationRows(self::BUNDLE_NAME, 'v7_0_3_0');

        self::assertCount(1, $rows);
    }

    public function testOnPreUpDoesNotRenameRowWhenBundleDoesNotMatch(): void
    {
        $this->insertMigrationRow(self::BUNDLE_NAME, self::VERSION);

        $listener = $this->createListener('OtherBundle', self::VERSION);
        $listener->onPreUp(new PreMigrationEvent($this->connection));

        $rows = $this->fetchMigrationRows(self::BUNDLE_NAME, self::VERSION);

        self::assertCount(1, $rows);
    }

    public function testOnPreUpDoesNothingWhenNoRowExistsForBundle(): void
    {
        $listener = $this->createListener(self::BUNDLE_NAME, self::VERSION);

        // Must not throw any exception when there is no matching row.
        $listener->onPreUp(new PreMigrationEvent($this->connection));

        $rows = $this->fetchMigrationRows(self::BUNDLE_NAME, self::VERSION);
        self::assertCount(0, $rows);
    }
}
