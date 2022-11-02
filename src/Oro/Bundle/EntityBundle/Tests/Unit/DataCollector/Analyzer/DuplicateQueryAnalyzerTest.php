<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DataCollector\Analyzer;

use Oro\Bundle\EntityBundle\DataCollector\Analyzer\DuplicateQueryAnalyzer;

class DuplicateQueryAnalyzerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DuplicateQueryAnalyzer */
    private $queryAnalyzer;

    protected function setUp(): void
    {
        $this->queryAnalyzer = new DuplicateQueryAnalyzer();
    }

    /**
     * @dataProvider getIdenticalQueriesDataProvider
     */
    public function testGetIdenticalQueries(array $queries, array $expectedIdenticalQueries)
    {
        foreach ($queries as $query) {
            $this->queryAnalyzer->addQuery($query['sql'], $query['params']);
        }
        $this->assertEquals($expectedIdenticalQueries, $this->queryAnalyzer->getIdenticalQueries());
    }

    public function getIdenticalQueriesDataProvider(): array
    {
        return [
            [
                'queries' => [
                    [
                        'sql' => 'select * from table',
                        'params' => [],
                    ],
                    [
                        'sql' => 'select * from table',
                        'params' => [],
                    ],
                    [
                        'sql' => 'select * from table where id = ?',
                        'params' => [1],
                    ],
                    [
                        'sql' => 'select * from table where id = ?',
                        'params' => [1],
                    ],
                    [
                        'sql' => 'select * from table where id = ?',
                        'params' => [1],
                    ],
                    [
                        'sql' => 'select * from table order by id',
                        'params' => [],
                    ],
                    [
                        'sql' => 'select * from table where id = ?',
                        'params' => [2],
                    ],
                ],
                'identicalQueries' => [
                    [
                        'sql' => 'select * from table',
                        'count' => 2,
                        'parameters' => [],
                    ],
                    [
                        'sql' => 'select * from table where id = ?',
                        'count' => 3,
                        'parameters' => [1],
                    ]
                ],
            ]
        ];
    }

    /**
     * @dataProvider getSimilarQueriesDataProvider
     */
    public function testGetSimilarQueries(array $queries, array $expectedIdenticalQueries)
    {
        foreach ($queries as $query) {
            $this->queryAnalyzer->addQuery($query['sql'], $query['params']);
        }
        $this->assertEquals($expectedIdenticalQueries, $this->queryAnalyzer->getSimilarQueries());
    }

    public function getSimilarQueriesDataProvider(): array
    {
        return [
            [
                'queries' => [
                    [
                        'sql' => 'select * from table where id = ?',
                        'params' => [1],
                    ],
                    [
                        'sql' => 'select * from table where id = ?',
                        'params' => [2],
                    ],
                    [
                        'sql' => 'select * from table',
                        'params' => [],
                    ],
                    [
                        'sql' => 'select * from table',
                        'params' => [],
                    ],
                    [
                        'sql' => 'select * from table',
                        'params' => [],
                    ],
                    [
                        'sql' => 'select * from table where number = ?',
                        'params' => [2],
                    ],
                    [
                        'sql' => 'select * from table where number = ?',
                        'params' => [2],
                    ],
                    [
                        'sql' => 'select * from table where number = ?',
                        'params' => [1],
                    ],
                    [
                        'sql' => 'select * from table order by id',
                        'params' => [],
                    ],
                    [
                        'sql' => 'select * from table where name = ?',
                        'params' => ['name'],
                    ]
                ],
                'identicalQueries' => [
                    [
                        'sql' => 'select * from table where id = ?',
                        'count' => 2,
                    ],
                    [
                        'sql' => 'select * from table where number = ?',
                        'count' => 3,
                    ],
                ],
            ]
        ];
    }
}
