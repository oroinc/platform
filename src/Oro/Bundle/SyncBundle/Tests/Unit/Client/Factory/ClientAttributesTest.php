<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SyncBundle\Client\Wamp\Factory\ClientAttributes;
use PHPUnit\Framework\MockObject\MockObject;

class ClientAttributesTest extends \PHPUnit\Framework\TestCase
{
    private ConfigManager|MockObject $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
    }

    public function testGetters(): void
    {
        $host = 'sampleHost';
        $port = 8080;
        $path = '/samplePath';
        $transport = 'sampleTransport';
        $contextOptions = ['sampleKey' => 'sampleValue'];
        $userAgent = 'user-agent/5.1.4';

        $clientAttributes = new ClientAttributes(
            $host,
            $port,
            $path,
            $transport,
            $contextOptions
        );
        $clientAttributes->setUserAgent($userAgent);

        $clientAttributes->setConfigManager($this->configManager);

        self::assertSame($host, $clientAttributes->getHost());
        self::assertSame($port, $clientAttributes->getPort());
        self::assertSame($path, $clientAttributes->getPath());
        self::assertSame($transport, $clientAttributes->getTransport());
        self::assertSame($contextOptions, $clientAttributes->getContextOptions());
        self::assertSame($userAgent, $clientAttributes->getUserAgent());
    }

    public function testGetHostResolvedStar(): void
    {
        $host = '*';
        $port = 8080;
        $path = '/samplePath';
        $transport = 'sampleTransport';
        $contextOptions = ['sampleKey' => 'sampleValue'];
        $testHost = 'test.host.com';
        $testApplicationUrl = "https://{$testHost}/";
        $userAgent = null;

        $clientAttributes = new ClientAttributes(
            $host,
            $port,
            $path,
            $transport,
            $contextOptions
        );
        $clientAttributes->setUserAgent($userAgent);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn($testApplicationUrl);

        $clientAttributes->setConfigManager($this->configManager);

        self::assertSame($testHost, $clientAttributes->getHost());
        self::assertSame($port, $clientAttributes->getPort());
        self::assertSame($path, $clientAttributes->getPath());
        self::assertSame($transport, $clientAttributes->getTransport());
        self::assertSame($contextOptions, $clientAttributes->getContextOptions());
        self::assertSame($userAgent, $clientAttributes->getUserAgent());
    }

    public function testGetHostWithoutConfigManagerResolvedStar(): void
    {
        $host = '*';
        $port = 8080;
        $path = '/samplePath';
        $transport = 'sampleTransport';
        $contextOptions = ['sampleKey' => 'sampleValue'];
        $testHost = '127.0.0.1';
        $userAgent = '';

        $clientAttributes = new ClientAttributes(
            $host,
            $port,
            $path,
            $transport,
            $contextOptions
        );
        $clientAttributes->setUserAgent($userAgent);

        self::assertSame($testHost, $clientAttributes->getHost());
        self::assertSame($port, $clientAttributes->getPort());
        self::assertSame($path, $clientAttributes->getPath());
        self::assertSame($transport, $clientAttributes->getTransport());
        self::assertSame($contextOptions, $clientAttributes->getContextOptions());
        self::assertSame($userAgent, $clientAttributes->getUserAgent());
    }
}
