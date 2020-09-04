<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\Consumption\Extension\ConsumptionExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Routing\RequestContext;

class ConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private const URL = 'https://test.host:444/index.php/admin/path';

    /** @var RequestContext */
    private $context;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConsumptionExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->context = new RequestContext();

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn(self::URL);

        $this->extension = new ConsumptionExtension($this->context, $this->configManager);
    }

    public function testOnPreReceived(): void
    {
        $this->assertEquals('http', $this->context->getScheme());
        $this->assertEquals('localhost', $this->context->getHost());
        $this->assertEquals('80', $this->context->getHttpPort());
        $this->assertEquals('443', $this->context->getHttpsPort());
        $this->assertEquals('', $this->context->getBaseUrl());

        $this->extension->onPreReceived(new Context($this->createMock(SessionInterface::class)));

        $this->assertEquals('https', $this->context->getScheme());
        $this->assertEquals('test.host', $this->context->getHost());
        $this->assertEquals('80', $this->context->getHttpPort());
        $this->assertEquals('444', $this->context->getHttpsPort());
        $this->assertEquals('/index.php/admin/path', $this->context->getBaseUrl());
    }
}
