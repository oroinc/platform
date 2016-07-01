<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\DBAL\Logging\DebugStack;

use Oro\Bundle\EntityBundle\DataCollector\DuplicateQueriesDataCollector;

class DuplicateQueriesDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DuplicateQueriesDataCollector
     */
    protected $collector;

    public function setUp()
    {
        $this->collector = new DuplicateQueriesDataCollector();
    }

    public function testGetName()
    {
        $this->assertEquals('duplicate_queries', $this->collector->getName());
    }

    /**
     * @dataProvider collectDataProvider
     * @param array $loggers
     * @param int $expectedCount
     * @param array $expectedIdentical
     * @param array $expectedSimilar
     */
    public function testCollect(array $loggers, $expectedCount, array $expectedIdentical, array $expectedSimilar)
    {
        foreach ($loggers as $loggerName => $queries) {
            $logger = new DebugStack();
            $logger->queries = $queries;
            $this->collector->addLogger($loggerName, $logger);
        }
        /** @var Request $request */
        $request = $this->getMock(Request::class);
        /** @var Response $response */
        $response = $this->getMock(Response::class);
        $this->collector->collect($request, $response);
        $this->assertEquals($expectedCount, $this->collector->getQueriesCount());
        $this->assertEquals($expectedIdentical, $this->collector->getIdenticalQueries());
        $this->assertEquals(count($expectedIdentical), $this->collector->getIdenticalQueriesCount());
        $this->assertEquals($expectedSimilar, $this->collector->getSimilarQueries());
        $this->assertEquals(count($expectedSimilar), $this->collector->getSimilarQueriesCount());
    }

    /**
     * @return array
     */
    public function collectDataProvider()
    {
        return [
            [
                'loggers' => [
                    'default' => [
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
                    ],
                    'config' => [
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
                    ]
                ],
                'expectedCount' => 10,
                'expectedIdentical' => [
                    'default' => [
                        [
                            'sql' => 'select * from table',
                            'parameters' => [],
                            'count' => 3,
                        ]
                    ],
                    'config' => [
                        [
                            'sql' => 'select * from table where number = ?',
                            'parameters' => [2],
                            'count' => 2,
                        ]
                    ],
                ],
                'expectedSimilar' => [
                    'default' => [
                        [
                            'sql' => 'select * from table where id = ?',
                            'count' => 2,
                        ]
                    ],
                    'config' => [
                        [
                            'sql' => 'select * from table where number = ?',
                            'count' => 3,
                        ]
                    ]
                ],
            ]
        ];
    }

    public function testGet()
    {
        $this->assertEquals(0, $this->collector->getQueriesCount());
        $this->assertEquals(0, $this->collector->getIdenticalQueriesCount());
        $this->assertEquals([], $this->collector->getIdenticalQueries());
        $this->assertEquals(0, $this->collector->getSimilarQueriesCount());
        $this->assertEquals([], $this->collector->getSimilarQueries());
    }
}
