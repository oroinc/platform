<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Provider\FieldSearchProvider;
use Symfony\Component\Translation\TranslatorInterface;

class FieldSearchProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigBag|\PHPUnit_Framework_MockObject_MockObject */
    private $configBag;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translate;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var FieldSearchProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->translate = $this->createMock(TranslatorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new FieldSearchProvider($this->configBag, $this->translate, $this->configManager);
    }

    public function testSupportsTrue()
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn([]);

        $this->assertTrue($this->provider->supports('test'));
    }

    public function testSupportsFalse()
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn(false);

        $this->assertFalse($this->provider->supports('test'));
    }

    public function testGetDataWithoutSearchType()
    {
        $field = [
            'options' => [
                'label' => 'label.key',
                'tooltip' => 'tooltip.key',
            ]
        ];

        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn($field);

        $this->translate->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['label.key', [], null, null, 'Field Label'],
                    ['tooltip.key', [], null, null, 'Field Tooltip'],
                ]
            );

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertSame(['Field Label', 'Field Tooltip'], $this->provider->getData('test'));
    }

    public function testGetDataSearchTypeText()
    {
        $field = [
            'search_type' => 'text'
        ];

        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn($field);

        $this->translate->expects($this->never())
            ->method('trans');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('test')
            ->willReturn('Field Value');

        $this->assertSame(['Field Value'], $this->provider->getData('test'));
    }

    public function testGetDataSearchTypeChoice()
    {
        $field = [
            'search_type' => 'choice',
            'options' => [
                'choices' => ['choice.1.key', 'choice.2.key']
            ]
        ];

        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn($field);

        $this->translate->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['choice.1.key', [], null, null, 'Field Choice 1'],
                    ['choice.2.key', [], null, null, 'Field Choice 2'],
                ]
            );

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertSame(['Field Choice 1', 'Field Choice 2'], $this->provider->getData('test'));
    }

    public function testGetDataEmpty()
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn([]);

        $this->assertSame([], $this->provider->getData('test'));
    }

    public function testGetDataItemNotFoundException()
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn(false);

        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('Field "test" is not defined.');

        $this->provider->getData('test');
    }

    public function testGetDataLogicException()
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn(['search_type' => 'choice']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The choices option should be defined, when search type "choice" is used.');

        $this->provider->getData('test');
    }
}
