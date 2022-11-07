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
        $this->expectException(InvalidArgumentException::class);
        new EngineParameters($dsn);
    }

    /**
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
     * @dataProvider engineNameAliasProvider
     */
    public function testEngineNameAlias(string $dsn, string $expectedEngineName)
    {
        $engineParametersBag = new EngineParameters($dsn);
        $engineParametersBag->addEngineNameAlias('elastic_search', 'http');

        self::assertEquals($expectedEngineName, $engineParametersBag->getEngineName());
    }

    public function invalidSearchEngineDsnProvider(): array
    {
        return [
            'Invalid schema' => ['invalid_schema:'],
            'Invalid pure schema designation' => ['valid-schema://'],
            'Path params are not allowed' => ['valid-schema://user:pass@localhost/path/to/resource']
        ];
    }

    public function properSearchEngineDsnProvider(): array
    {
        return [
            'Dsn full' => [
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
            'Dsn only host' => [
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
            'Dsn user and host' => [
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
            'Dsn password and host' => [
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

    public function engineNameAliasProvider(): array
    {
        return [
            'ELK engine' => ['elastic-search://user:password@localhost:9200?prefix=oro', 'elastic_search'],
            'Http alias' => ['http://user:password@localhost:9200?prefix=oro', 'elastic_search'],
            'Not matched engine' => ['foo://user:password@localhost:9200?prefix=oro', 'foo'],
        ];
    }
}
