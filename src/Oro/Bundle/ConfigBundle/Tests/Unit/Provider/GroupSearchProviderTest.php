<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Provider\GroupSearchProvider;
use Symfony\Component\Translation\TranslatorInterface;

class GroupSearchProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigBag|\PHPUnit_Framework_MockObject_MockObject */
    private $configBag;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translate;

    /** @var GroupSearchProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->translate = $this->createMock(TranslatorInterface::class);

        $this->provider = new GroupSearchProvider($this->configBag, $this->translate);
    }

    public function testSupportsTrue()
    {
        $this->configBag->expects($this->once())
            ->method('getGroupsNode')
            ->with('test')
            ->willReturn([]);

        $this->assertTrue($this->provider->supports('test'));
    }

    public function testSupportsFalse()
    {
        $this->configBag->expects($this->once())
            ->method('getGroupsNode')
            ->with('test')
            ->willReturn(false);

        $this->assertFalse($this->provider->supports('test'));
    }

    public function testGetData()
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

    public function testGetDataEmpty()
    {
        $this->configBag->expects($this->once())
            ->method('getGroupsNode')
            ->with('test')
            ->willReturn([]);

        $this->assertSame([], $this->provider->getData('test'));
    }

    public function testGetDataItemNotFoundException()
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
