<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine\Orm;

use Doctrine\ORM\Configuration;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

/**
 * @group search
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
        $expectedQueries = [
            'SET FOREIGN_KEY_CHECKS=0',
            'DELETE FROM oro_search_item',
            'DELETE FROM oro_search_index_text',
            'DELETE FROM oro_search_index_integer',
            'DELETE FROM oro_search_index_decimal',
            'DELETE FROM oro_search_index_datetime',
            'SET FOREIGN_KEY_CHECKS=1'
        ];

        foreach ($expectedQueries as $expectedQuery) {
            $this->assertContains($expectedQuery, $queries);
        }
    }
}
