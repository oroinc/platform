<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport;

use Oro\Component\MessageQueue\Transport\DsnBasedParameters;

class DsnBasedParametersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider invalidDsnProvider
     */
    public function testInvalidDsnProcessing($dsn): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" message queue transport connection DSN is invalid.',
            $dsn
        ));
        new DsnBasedParameters($dsn);
    }

    public function invalidDsnProvider(): array
    {
        return [
            'invalid_dsn_port' => ['//*:port'],
            'invalid_dsn_protocol_splitter' => ['///'],
            'invalid_dsn_protocol_missing_host_for_port_pointed' => ['//:5672'],
        ];
    }

    public function testMissingSchemeProcessing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "//localhost:5672" message queue transport connection DSN must contain a scheme.'
        );
        new DsnBasedParameters('//localhost:5672');
    }

    /**
     * @dataProvider properDsnProvider
     */
    public function testProperDsnProcessing(
        string $dsn,
        string $transportName,
        array $parameters,
        array $parameter,
        string $user = null,
        string $password = null,
        string $host = null,
        string $port = null,
        string $path = null
    ): void {
        $dsnParametersBag = new DsnBasedParameters($dsn);
        self::assertEquals($transportName, $dsnParametersBag->getTransportName());
        self::assertEquals($user, $dsnParametersBag->getuser());
        self::assertEquals($password, $dsnParametersBag->getPassword());
        self::assertEquals($host, $dsnParametersBag->getHost());
        self::assertEquals($port, $dsnParametersBag->getPort());
        self::assertEquals($path, $dsnParametersBag->getPath());
        self::assertEquals($parameters, $dsnParametersBag->getParameters());
        self::assertEquals(current($parameter), $dsnParametersBag->getParamValue(key($parameter)));
    }

    public function properDsnProvider(): array
    {
        return [
            'dbal' => [
                'dsn' => 'dbal:',
                'transportName' => 'dbal',
                'parameters' => [],
                'parameter' => ['vhost' => null],
                'user' => null,
                'password' => null,
                'host' => null,
                'port' => null,
                'path' => null
            ],
            'amqp' => [
                'dsn' => 'amqp://guest:guest@127.0.0.1:5672?vhost=/oro_crm_0',
                'transportName' => 'amqp',
                'parameters' => ['vhost' => '/oro_crm_0'],
                'parameter' => ['vhost' => '/oro_crm_0'],
                'user' => 'guest',
                'password' => 'guest',
                'host' => '127.0.0.1',
                'port' => '5672',
                'path' => null
            ]
        ];
    }
}
