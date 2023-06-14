<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Composer;

use Oro\Bundle\InstallerBundle\Composer\ScriptHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ScriptHandlerTest extends TestCase
{
    /**
     * @dataProvider quoteParameterDataProvider
     */
    public function testQuoteParameter(string $parameter, $value)
    {
        $reflection = new \ReflectionClass(ScriptHandler::class);
        $method = $reflection->getMethod('quoteParameter');
        $method->setAccessible(true);
        $parameters = [
            'parameters' => [
                'assets_version_strategy' => 'time_hash',
                $parameter => $value,
                'database_driver' => 'pdo_pgsql'
            ]
        ];

        $data = Yaml::dump($parameters);
        $processedData = $method->invoke(null, $data, $parameter);
        $processedData = Yaml::parse($processedData);

        self::assertEquals($parameters, $processedData);
    }

    public function quoteParameterDataProvider(): array
    {
        return [
            'null value' => ['assets_version', '~'],
            'float like value' => ['assets_version', '556e3720'],
            'time_hash value' => ['assets_version', 'cf08c511'],
            'incremental value' => ['assets_version', 'ver1'],
        ];
    }
}
