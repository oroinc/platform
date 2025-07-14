<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Provider\GroupSearchProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupSearchProviderTest extends TestCase
{
    private ConfigBag&MockObject $configBag;
    private TranslatorInterface&MockObject $translate;
    private GroupSearchProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->translate = $this->createMock(TranslatorInterface::class);

        $this->provider = new GroupSearchProvider($this->configBag, $this->translate);
    }

    public function testSupportsTrue(): void
    {
        $this->configBag->expects($this->once())
            ->method('getGroupsNode')
            ->with('test')
            ->willReturn([]);

        $this->assertTrue($this->provider->supports('test'));
    }

    public function testSupportsFalse(): void
    {
        $this->configBag->expects($this->once())
            ->method('getGroupsNode')
            ->with('test')
            ->willReturn(false);

        $this->assertFalse($this->provider->supports('test'));
    }

    public function testGetData(): void
    {
        $this->configBag->expects($this->once())
            ->method('getGroupsNode')
            ->with('test')
            ->willReturn(['title' => 'title.key']);

        $this->translate->expects($this->once())
            ->method('trans')
            ->with('title.key')
            ->willReturn('Group Title');

        $this->assertSame(['Group Title'], $this->provider->getData('test'));
    }

    public function testGetDataEmpty(): void
    {
        $this->configBag->expects($this->once())
            ->method('getGroupsNode')
            ->with('test')
            ->willReturn([]);

        $this->assertSame([], $this->provider->getData('test'));
    }

    public function testGetDataItemNotFoundException(): void
    {
        $this->configBag->expects($this->once())
            ->method('getGroupsNode')
            ->with('test')
            ->willReturn(false);

        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('Group "test" is not defined.');

        $this->provider->getData('test');
    }
}
