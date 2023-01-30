<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine\Orm;

use Doctrine\ORM\Configuration;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql\TsRank;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql\TsvectorTsquery;

/**
 * @group search
 */
class PdoPgsqlTest extends AbstractDriverTest
{
    public function testGetPlainSql()
    {
        $recordString = PdoPgsql::getPlainSql();
        $this->assertTrue(strpos($recordString, 'to_tsvector') > 0);
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
            TsRank::class,
            $configuration->getCustomStringFunction('TsRank')
        );

        $this->assertEquals(
            TsvectorTsquery::class,
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
