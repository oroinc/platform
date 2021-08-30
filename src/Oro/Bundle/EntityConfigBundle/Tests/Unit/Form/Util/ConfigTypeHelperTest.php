<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class ConfigTypeHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigTypeHelper */
    private $typeHelper;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->typeHelper = new ConfigTypeHelper($this->configManager);
    }

    /**
     * @dataProvider getFieldNameProvider
     */
    public function testGetFieldName(ConfigIdInterface $configId, ?string $expected)
    {
        $this->assertSame(
            $expected,
            $this->typeHelper->getFieldName($configId)
        );
    }

    public function getFieldNameProvider(): array
    {
        return [
            [new EntityConfigId('test', 'Test\Entity'), null],
            [new FieldConfigId('test', 'Test\Entity', 'testField', 'integer'), 'testField'],
        ];
    }

    /**
     * @dataProvider getFieldTypeProvider
     */
    public function testGetFieldType(ConfigIdInterface $configId, ?string $expected)
    {
        $this->assertSame(
            $expected,
            $this->typeHelper->getFieldType($configId)
        );
    }

    public function getFieldTypeProvider(): array
    {
        return [
            [new EntityConfigId('test', 'Test\Entity'), null],
            [new FieldConfigId('test', 'Test\Entity', 'testField', 'integer'), 'integer'],
        ];
    }

    public function testGetImmutableNoConfig()
    {
        $scope = 'test_scope';
        $className = 'Test\Entity';

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, null)
            ->willReturn(false);
        $configProvider->expects($this->never())
            ->method('getConfig');

        $this->assertNull(
            $this->typeHelper->getImmutable($scope, $className)
        );
    }

    /**
     * @dataProvider getImmutableProvider
     */
    public function testGetImmutable(mixed $value, string $fieldName = null)
    {
        $scope = 'test_scope';
        $className = 'Test\Entity';

        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('get')
            ->with('immutable')
            ->willReturn($value);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($config);

        $this->assertSame(
            $value,
            $this->typeHelper->getImmutable($scope, $className, $fieldName)
        );
    }

    public function getImmutableProvider(): array
    {
        return [
            [true, null],
            [false, null],
            [null, null],
            [['val1', 'val2'], null],
            [true, 'testField'],
            [false, 'testField'],
            [null, 'testField'],
            [['val1', 'val2'], 'testField'],
        ];
    }

    /**
     * @dataProvider isImmutableProvider
     */
    public function testIsImmutable(
        mixed $value,
        bool $expected,
        string $fieldName = null,
        string $constraintName = null
    ) {
        $scope = 'test_scope';
        $className = 'Test\Entity';

        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('get')
            ->with('immutable')
            ->willReturn($value);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($config);

        $this->assertSame(
            $expected,
            $this->typeHelper->isImmutable($scope, $className, $fieldName, $constraintName)
        );
    }

    public function isImmutableProvider(): array
    {
        return [
            [true, true, null, null],
            [true, true, null, 'val1'],
            [false, false, null, null],
            [false, false, null, 'val1'],
            [null, false, null, null],
            [null, false, null, 'val1'],
            [[], false, null, null],
            [[], false, null, 'val1'],
            [['val1', 'val2'], false, null, null],
            [['val1', 'val2'], true, null, 'val2'],
            [['val1'], false, null, 'val2'],
            [true, true, 'testField', null],
            [true, true, 'testField', 'val1'],
            [false, false, 'testField', null],
            [false, false, 'testField', 'val1'],
            [null, false, 'testField', null],
            [null, false, 'testField', 'val1'],
            [[], false, 'testField', null],
            [[], false, 'testField', 'val1'],
            [['val1', 'val2'], false, 'testField', null],
            [['val1', 'val2'], true, 'testField', 'val2'],
            [['val1'], false, 'testField', 'val2'],
        ];
    }
}
