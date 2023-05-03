<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\DBAL\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Oro\Component\DoctrineUtils\DBAL\Schema\MaterializedView;
use Oro\Component\DoctrineUtils\DBAL\Schema\MaterializedViewSchemaManager;

class MaterializedViewSchemaManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var MaterializedViewSchemaManager */
    private $manager;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->manager = new MaterializedViewSchemaManager($this->connection);

        $platform = new PostgreSQL100Platform();
        $this->connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(bool $withData, bool $ifNotExists, string $expected): void
    {
        $materializedView = new MaterializedView('sample_name', 'SELECT 1', $withData);
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with($expected);

        $this->manager->create($materializedView, $ifNotExists);
    }

    public function createDataProvider(): array
    {
        return [
            [
                'withData' => false,
                'ifNotExists' => false,
                'expected' => 'CREATE MATERIALIZED VIEW "sample_name" AS SELECT 1 WITH NO DATA',
            ],
            [
                'withData' => true,
                'ifNotExists' => false,
                'expected' => 'CREATE MATERIALIZED VIEW "sample_name" AS SELECT 1 WITH DATA',
            ],
            [
                'withData' => true,
                'ifNotExists' => true,
                'expected' => 'CREATE MATERIALIZED VIEW IF NOT EXISTS "sample_name" AS SELECT 1 WITH DATA',
            ],
        ];
    }

    /**
     * @dataProvider dropDataProvider
     */
    public function testDrop(
        MaterializedView|string $materializedView,
        bool $ifExists,
        bool $cascade,
        string $expected
    ): void {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with($expected);

        $this->manager->drop($materializedView, $ifExists, $cascade);
    }

    public function dropDataProvider(): array
    {
        $materializedViewShouldBeQuoted = new MaterializedView('public.all', 'SELECT 1', false);
        $materializedViewShouldNotBeQuoted = new MaterializedView('public.sample_name', 'SELECT 1', false);

        return [
            'materialized view is string, ifExists=false, cascade=false' => [
                'materializedView' => 'sample_name',
                'ifExists' => false,
                'cascade' => false,
                'expected' => 'DROP MATERIALIZED VIEW "sample_name"',
            ],
            'materialized view is string, ifExists=true, cascade=false' => [
                'materializedView' => 'sample_name',
                'ifExists' => true,
                'cascade' => false,
                'expected' => 'DROP MATERIALIZED VIEW IF EXISTS "sample_name"',
            ],
            'materialized view is string, ifExists=true, cascade=true' => [
                'materializedView' => 'sample_name',
                'ifExists' => true,
                'cascade' => true,
                'expected' => 'DROP MATERIALIZED VIEW IF EXISTS "sample_name" CASCADE',
            ],
            'materialized view is object with reserved keyword in name, ifExists=false, cascade=false' => [
                'materializedView' => $materializedViewShouldBeQuoted,
                'ifExists' => false,
                'cascade' => false,
                'expected' => 'DROP MATERIALIZED VIEW public."all"',
            ],
            'materialized view is object with reserved keyword in name, ifExists=true, cascade=false' => [
                'materializedView' => $materializedViewShouldBeQuoted,
                'ifExists' => true,
                'cascade' => false,
                'expected' => 'DROP MATERIALIZED VIEW IF EXISTS public."all"',
            ],
            'materialized view is object with reserved keyword in name, ifExists=true, cascade=true' => [
                'materializedView' => $materializedViewShouldBeQuoted,
                'ifExists' => true,
                'cascade' => true,
                'expected' => 'DROP MATERIALIZED VIEW IF EXISTS public."all" CASCADE',
            ],
            'materialized view is object without reserved keyword in name, ifExists=false, cascade=false' => [
                'materializedView' => $materializedViewShouldNotBeQuoted,
                'ifExists' => false,
                'cascade' => false,
                'expected' => 'DROP MATERIALIZED VIEW public.sample_name',
            ],
            'materialized view is object without reserved keyword in name, ifExists=true, cascade=false' => [
                'materializedView' => $materializedViewShouldNotBeQuoted,
                'ifExists' => true,
                'cascade' => false,
                'expected' => 'DROP MATERIALIZED VIEW IF EXISTS public.sample_name',
            ],
            'materialized view is object without reserved keyword in name, ifExists=true, cascade=true' => [
                'materializedView' => $materializedViewShouldNotBeQuoted,
                'ifExists' => true,
                'cascade' => true,
                'expected' => 'DROP MATERIALIZED VIEW IF EXISTS public.sample_name CASCADE',
            ],
        ];
    }

    /**
     * @dataProvider refreshDataProvider
     */
    public function testRefresh(
        MaterializedView|string $materializedView,
        bool $concurrently,
        bool $withData,
        string $expected
    ): void {
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with($expected);

        $this->manager->refresh($materializedView, $concurrently, $withData);
    }

    public function refreshDataProvider(): array
    {
        $materializedViewShouldBeQuoted = new MaterializedView('public.all', 'SELECT 1', false);
        $materializedViewShouldNotBeQuoted = new MaterializedView('public.sample_name', 'SELECT 1', false);

        return [
            'materialized view is string, withData=false, concurrently=false' => [
                'materializedView' => 'sample_name',
                'concurrently' => false,
                'withData' => false,
                'expected' => 'REFRESH MATERIALIZED VIEW "sample_name" WITH NO DATA',
            ],
            'materialized view is string, withData=true, concurrently=false' => [
                'materializedView' => 'sample_name',
                'concurrently' => false,
                'withData' => true,
                'expected' => 'REFRESH MATERIALIZED VIEW "sample_name" WITH DATA',
            ],
            'materialized view is string, withData=true, concurrently=true' => [
                'materializedView' => 'sample_name',
                'concurrently' => true,
                'withData' => true,
                'expected' => 'REFRESH MATERIALIZED VIEW CONCURRENTLY "sample_name" WITH DATA',
            ],
            'materialized view is object with reserved keyword in name, withData=false, concurrently=false' => [
                'materializedView' => $materializedViewShouldBeQuoted,
                'concurrently' => false,
                'withData' => false,
                'expected' => 'REFRESH MATERIALIZED VIEW public."all" WITH NO DATA',
            ],
            'materialized view is object with reserved keyword in name, withData=true, concurrently=false' => [
                'materializedView' => $materializedViewShouldBeQuoted,
                'concurrently' => false,
                'withData' => true,
                'expected' => 'REFRESH MATERIALIZED VIEW public."all" WITH DATA',
            ],
            'materialized view is object with reserved keyword in name, withData=true, concurrently=true' => [
                'materializedView' => $materializedViewShouldBeQuoted,
                'concurrently' => true,
                'withData' => true,
                'expected' => 'REFRESH MATERIALIZED VIEW CONCURRENTLY public."all" WITH DATA',
            ],
            'materialized view is object without reserved keyword in name, withData=false, concurrently=false' => [
                'materializedView' => $materializedViewShouldNotBeQuoted,
                'concurrently' => false,
                'withData' => false,
                'expected' => 'REFRESH MATERIALIZED VIEW public.sample_name WITH NO DATA',
            ],
            'materialized view is object without reserved keyword in name, withData=true, concurrently=false' => [
                'materializedView' => $materializedViewShouldNotBeQuoted,
                'concurrently' => false,
                'withData' => true,
                'expected' => 'REFRESH MATERIALIZED VIEW public.sample_name WITH DATA',
            ],
            'materialized view is object without reserved keyword in name, withData=true, concurrently=true' => [
                'materializedView' => $materializedViewShouldNotBeQuoted,
                'concurrently' => true,
                'withData' => true,
                'expected' => 'REFRESH MATERIALIZED VIEW CONCURRENTLY public.sample_name WITH DATA',
            ],
        ];
    }
}
