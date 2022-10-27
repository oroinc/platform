<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\UrlProvider;
use Symfony\Component\Routing\RouterInterface;

class UrlProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var UrlProvider */
    private $urlProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->urlProvider = new UrlProvider($this->configManager, $this->router);
    }

    public function testGetUrlReturnEmptyStringIfMetadataNotExist()
    {
        $reminder = $this->createMock(Reminder::class);
        $expected = '';
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn(null);
        $actual = $this->urlProvider->getUrl($reminder);
        $this->assertEquals($expected, $actual);
    }

    public function testGetUrlForView()
    {
        $reminder = $this->createMock(Reminder::class);
        $expected = '/fake/path/for/view';
        $expectedId = 42;
        $metadata = new \stdClass();
        $metadata->routeView = $expected;
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn($metadata);
        $expectedParams = ['id' => $expectedId];
        $reminder->expects($this->once())
            ->method('getRelatedEntityId')
            ->willReturn($expectedId);
        $this->router->expects($this->once())
            ->method('generate')
            ->with($expected, $expectedParams);

        $this->urlProvider->getUrl($reminder);
    }

    public function testGetUrlForIndex()
    {
        $reminder = $this->createMock(Reminder::class);
        $expected = '/fake/path/for/view';
        $metadata = new \stdClass();
        $metadata->routeName = $expected;
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn($metadata);
        $this->router->expects($this->once())
            ->method('generate')
            ->with($expected);

        $this->urlProvider->getUrl($reminder);
    }
}
