<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine\Orm;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

use Doctrine\ORM\Configuration;

/**
 * @dbIsolation
 * @dbReindex
 */
class PdoMysqlTest extends AbstractDriverTest
{
    const ENTITY_TITLE = 'test-entity-title';
    const ENVIRONMENT_NAME = 'MySQL';

    public function testGetPlainSql()
    {
        $recordString = PdoMysql::getPlainSql();
        $this->assertTrue(strpos($recordString, 'FULLTEXT') > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return DatabaseDriverInterface::DRIVER_MYSQL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return new PdoMysql();
    }

    /**
     * {@inheritdoc}
     */
    protected function assertInitConfiguration(Configuration $configuration)
    {
        $this->assertEquals(
            'Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MatchAgainst',
            $configuration->getCustomStringFunction('MATCH_AGAINST')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertTruncateQueries(array $queries)
    {
        $this->assertCount(7, $queries);

        $expectedQueries = [
            'SET FOREIGN_KEY_CHECKS=0',
            'TRUNCATE oro_search_item',
            'TRUNCATE oro_search_index_text',
            'TRUNCATE oro_search_index_integer',
            'TRUNCATE oro_search_index_decimal',
            'TRUNCATE oro_search_index_datetime',
            'SET FOREIGN_KEY_CHECKS=1'
        ];

        $this->assertEquals($expectedQueries, $queries);
    }
}
