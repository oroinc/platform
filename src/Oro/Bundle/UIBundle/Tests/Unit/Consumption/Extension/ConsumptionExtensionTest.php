<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\Consumption\Extension\ConsumptionExtension;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Routing\RequestContext;

class ConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const URL = 'https://test.host:444/index.php/admin/path';

    private RequestContext $requestContext;
    private ConsumptionExtension $extension;
    private Context $context;

    protected function setUp(): void
    {
        $this->requestContext = new RequestContext();

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn(self::URL);

        $this->extension = new ConsumptionExtension($this->requestContext, $configManager);
        $this->extension->addTopicName('test-topic');

        $this->context = new Context($this->createMock(SessionInterface::class));
    }

    /**
     * @dataProvider onPreReceivedDataProvider
     */
    public function testOnPreReceived(?Message $message, bool $changed): void
    {
        $this->assertEquals('http', $this->requestContext->getScheme());
        $this->assertEquals('localhost', $this->requestContext->getHost());
        $this->assertEquals('80', $this->requestContext->getHttpPort());
        $this->assertEquals('443', $this->requestContext->getHttpsPort());
        $this->assertEquals('', $this->requestContext->getBaseUrl());

        if ($message) {
            $this->context->setMessage($message);
        }

        $this->extension->onPreReceived($this->context);

        if ($changed) {
            $this->assertEquals('https', $this->requestContext->getScheme());
            $this->assertEquals('test.host', $this->requestContext->getHost());
            $this->assertEquals('80', $this->requestContext->getHttpPort());
            $this->assertEquals('444', $this->requestContext->getHttpsPort());
            $this->assertEquals('/index.php/admin/path', $this->requestContext->getBaseUrl());
        } else {
            $this->assertEquals('http', $this->requestContext->getScheme());
            $this->assertEquals('localhost', $this->requestContext->getHost());
            $this->assertEquals('80', $this->requestContext->getHttpPort());
            $this->assertEquals('443', $this->requestContext->getHttpsPort());
            $this->assertEquals('', $this->requestContext->getBaseUrl());
        }
    }

    public function onPreReceivedDataProvider(): array
    {
        $message1 = new Message();
        $message1->setProperties([Config::PARAMETER_TOPIC_NAME => 'unknown-topic']);

        $message2 = new Message();
        $message2->setProperties([Config::PARAMETER_TOPIC_NAME => 'test-topic']);

        return [
            [
                'message' => null,
                'changed' => false,
            ],
            [
                'message' => new Message(),
                'changed' => false,
            ],
            [
                'message' => $message1,
                'changed' => false,
            ],
            [
                'message' => $message2,
                'changed' => true,
            ],
        ];
    }
}
