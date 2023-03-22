<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\WebSocket;

use Oro\Bundle\SyncBundle\WebSocket\DsnBasedParameters;

class DsnBasedParametersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider invalidDsnProvider
     */
    public function testInvalidDsnProcessing(string $dsn): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The "%s" websocket related config DSN string is invalid.', $dsn));
        new DsnBasedParameters($dsn);
    }

    public function invalidDsnProvider(): array
    {
        return [
            'invalid_dsn_port' => ['//*:port'],
            'invalid_dsn_protocol_splitter' => ['///'],
            'invalid_dsn_protocol_missing_host_for_port_pointed' => ['//:80'],
        ];
    }

    /**
     * @dataProvider properDsnProvider
     */
    public function testProperDsnProcessing(
        string $dsn,
        string $scheme,
        string $host,
        string $port,
        string $path,
        array $parameters,
        array $parameter
    ): void {
        $dsnParametersBag = new DsnBasedParameters($dsn);
        self::assertEquals($scheme, $dsnParametersBag->getScheme());
        self::assertEquals($host, $dsnParametersBag->getHost());
        self::assertEquals($port, $dsnParametersBag->getPort());
        self::assertEquals($path, $dsnParametersBag->getPath());
        self::assertEquals($parameters, $dsnParametersBag->getParameters());
        self::assertEquals(current($parameter), $dsnParametersBag->getParamValue(key($parameter)));
    }

    public function properDsnProvider(): array
    {
        return [
            'full_info_dsn' => [
                'dsn' => 'ssl://host:1234/ws?context_options[opt1]=opt1_val',
                'scheme' => 'ssl',
                'host' => 'host',
                'port' => '1234',
                'path' => 'ws',
                'parameters' => ['context_options' => ['opt1' => 'opt1_val']],
                'parameter' => ['context_options' => ['opt1' => 'opt1_val']]
            ],
            'default_scheme_and_host_set' => [
                'dsn' => '',
                'scheme' => 'tcp',
                'host' => '*',
                'port' => '',
                'path' => '',
                'parameters' => [],
                'parameter' => ['non_existent' => null]
            ]
        ];
    }
}
