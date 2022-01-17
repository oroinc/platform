<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;

class EngineParametersTest extends TestCase
{
    /**
     * @dataProvider invalidSearchEngineDsnProvider
     */
    public function testInvalidSearchEngineDsnProcessing($dsn)
    {
        self::expectException(InvalidArgumentException::class);
        new EngineParameters($dsn);
    }

    /**
     * @param string $dsn
     * @param array $expectedParams
     * @dataProvider properSearchEngineDsnProvider
     */
    public function testProperSearchEngineDsnProcessing(string $dsn, array $expectedProcessedDsnResults)
    {
        $engineParametersBag = new EngineParameters($dsn);

        $processedDsnResults = [
            'engine_name' => $engineParametersBag->getEngineName(),
            'connection' => [
                'user' => $engineParametersBag->getUser(),
                'password' => $engineParametersBag->getPassword(),
                'host' => $engineParametersBag->getHost(),
                'port' => $engineParametersBag->getPort(),
            ],
            'parameters' => $engineParametersBag->getParameters()
        ];

        self::assertEquals($processedDsnResults, $expectedProcessedDsnResults);
    }

    /**
     * @return array
     */
    public function invalidSearchEngineDsnProvider(): array
    {
        return [
            'Invalid schema' => ['invalid_schema:'],
            'Invalid pure schema designation' => ['valid-schema://'],
            'Path params are not allowed' => ['valid-schema://user:pass@localhost/path/to/resource']
        ];
    }

    /**
     * @return array
     */
    public function properSearchEngineDsnProvider(): array
    {
        return [
            'Sample 1' => [
                'elastic-search://user:password@localhost:9200?prefix=oro',
                [
                    'engine_name' => 'elastic_search',
                    'connection' => [
                        'user' => 'user',
                        'password' => 'password',
                        'host' => 'localhost',
                        'port' => '9200'
                    ],
                    'parameters' =>  [
                        'prefix' => 'oro'
                    ]
                ]
            ],
            'Sample 2' => [
                'elastic-search://localhost',
                [
                    'engine_name' => 'elastic_search',
                    'connection' => [
                        'user' => null,
                        'password' => null,
                        'host' => 'localhost',
                        'port' => null,
                    ],
                    'parameters' =>  []
                ]
            ],
            'Sample 3' => [
                'elastic-search://user:@localhost',
                [
                    'engine_name' => 'elastic_search',
                    'connection' => [
                        'user' => 'user',
                        'password' => null,
                        'host' => 'localhost',
                        'port' => null,
                    ],
                    'parameters' =>  []
                ]
            ],
            'Sample 4' => [
                'elastic-search://:password@localhost',
                [
                    'engine_name' => 'elastic_search',
                    'connection' => [
                        'user' => null,
                        'password' => 'password',
                        'host' => 'localhost',
                        'port' => null,
                    ],
                    'parameters' =>  []
                ]
            ],
        ];
    }
}
