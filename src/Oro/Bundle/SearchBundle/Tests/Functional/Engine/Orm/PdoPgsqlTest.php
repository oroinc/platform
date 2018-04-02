<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine\Orm;

use Doctrine\ORM\Configuration;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql;

/**
 * @group search
 */
class PdoPgsqlTest extends AbstractDriverTest
{
    const ENTITY_TITLE = 'test-entity-title';
    const ENVIRONMENT_NAME = 'PostgreSQL';

    public function testGetPlainSql()
    {
        $recordString = PdoPgsql::getPlainSql();
        $this->assertTrue(strpos($recordString, 'to_tsvector') > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return DatabaseDriverInterface::DRIVER_POSTGRESQL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return new PdoPgsql();
    }

    /**
     * {@inheritdoc}
     */
    protected function assertInitConfiguration(Configuration $configuration)
    {
        $this->assertEquals(
            'Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql\TsRank',
            $configuration->getCustomStringFunction('TsRank')
        );

        $this->assertEquals(
            'Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql\TsvectorTsquery',
            $configuration->getCustomStringFunction('TsvectorTsquery')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertTruncateQueries(array $queries)
    {
        $expectedQueries = [
            'TRUNCATE oro_search_item CASCADE',
            'TRUNCATE oro_search_index_text CASCADE',
            'TRUNCATE oro_search_index_integer CASCADE',
            'TRUNCATE oro_search_index_decimal CASCADE',
            'TRUNCATE oro_search_index_datetime CASCADE'
        ];

        foreach ($expectedQueries as $expectedQuery) {
            $this->assertContains($expectedQuery, $queries);
        }
    }
}
