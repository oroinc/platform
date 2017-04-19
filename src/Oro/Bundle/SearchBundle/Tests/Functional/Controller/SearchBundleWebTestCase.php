<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller;

use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SearchBundleWebTestCase extends WebTestCase
{
    use SearchExtensionTrait;

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


    protected function tearDown()
    {
        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->clearTestData();
    }
}
