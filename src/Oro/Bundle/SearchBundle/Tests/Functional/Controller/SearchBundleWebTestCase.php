<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller;

use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SearchBundleWebTestCase extends WebTestCase
{
    use SearchExtensionTrait {
        reindex as traitReindex;
    }

    /** @var array */
    protected static $entitiesToClear = [];

    /**
     * For InnoDB, all DML operations (INSERT, UPDATE, DELETE) involving columns with full-text indexes are
     * processed at transaction commit time. For example, for an INSERT operation, an inserted string
     * is tokenized and decomposed into individual words.
     * The individual words are then added to full-text index tables when the transaction is committed.
     * As a result, full-text searches only return committed data.
     *
     * Because of this, we are disabling dbIsolation for this test case.
     * Fixtures data is committed, then manually deleted after tests
     */

    /**
     * override parent method so no DB transaction is started
     * @param bool $nestTransactionsWithSavePoints
     */
    protected function startTransaction($nestTransactionsWithSavePoints = false)
    {
    }

    /**
     * override parent method so no DB transaction is rolled back
     */
    protected static function rollbackTransaction()
    {
    }

    protected function tearDown(): void
    {
        if (static::isDbIsolationPerTest()) {
            static::clear();
        }

        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (!static::isDbIsolationPerTest()) {
            static::clear();
        }

        parent::tearDownAfterClass();
    }

    protected function loadFixture(string $entityClass, string $fixtureClass, int $expectedCount): void
    {
        $doReindex = static::isDbIsolationPerTest() || !$this->isLoadedFixture($fixtureClass);

        $this->loadFixtures([$fixtureClass]);

        if ($doReindex) {
            $this->reindex($entityClass, $expectedCount);
        }
    }

    protected function reindex(string $entityClass, int $expectedCount): void
    {
        static::$entitiesToClear[] = $entityClass;

        static::clearIndex($entityClass);

        static::traitReindex($entityClass);

        $alias = static::getSearchObjectMapper()->getEntityAlias($entityClass);
        static::ensureItemsLoaded($alias, $expectedCount);
    }

    protected static function clearIndex(string $entityClass): void
    {
        static::getSearchIndexer()->resetIndex($entityClass);

        $alias = static::getSearchObjectMapper()->getEntityAlias($entityClass);
        static::ensureItemsLoaded($alias, 0);
    }

    protected static function clear(): void
    {
        foreach (static::$entitiesToClear as $entityClass) {
            static::clearIndex($entityClass);
            static::clearTestData($entityClass);
        }

        static::$entitiesToClear = [];
    }
}
