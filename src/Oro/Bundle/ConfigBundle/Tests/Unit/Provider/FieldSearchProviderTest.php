<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Provider\FieldSearchProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class FieldSearchProviderTest extends TestCase
{
    private ConfigBag&MockObject $configBag;
    private TranslatorInterface&MockObject $translate;
    private ConfigManager&MockObject $configManager;
    private FieldSearchProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->translate = $this->createMock(TranslatorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new FieldSearchProvider($this->configBag, $this->translate, $this->configManager);
    }

    public function testSupportsTrue(): void
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn([]);

        $this->assertTrue($this->provider->supports('test'));
    }

    public function testSupportsFalse(): void
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn(false);

        $this->assertFalse($this->provider->supports('test'));
    }

    public function testGetDataWithoutSearchType(): void
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
            ->willReturnMap([
                ['label.key', [], null, null, 'Field Label'],
                ['tooltip.key', [], null, null, 'Field Tooltip'],
            ]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertSame(['Field Label', 'Field Tooltip'], $this->provider->getData('test'));
    }

    public function testGetDataSearchTypeText(): void
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

    public function testGetDataSearchTypeChoice(): void
    {
        $field = [
            'search_type' => 'choice',
            'options' => [
                'choices' => ['choice.1.key' => 0, 'choice.2.key' => 1]
            ]
        ];

        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn($field);

        $this->translate->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['choice.1.key', [], null, null, 'Field Choice 1'],
                ['choice.2.key', [], null, null, 'Field Choice 2'],
            ]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertSame(['Field Choice 1', 'Field Choice 2'], $this->provider->getData('test'));
    }

    public function testGetDataEmpty(): void
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn([]);

        $this->assertSame([], $this->provider->getData('test'));
    }

    public function testGetDataItemNotFoundException(): void
    {
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->with('test')
            ->willReturn(false);

        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('Field "test" is not defined.');

        $this->provider->getData('test');
    }

    public function testGetDataLogicException(): void
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
