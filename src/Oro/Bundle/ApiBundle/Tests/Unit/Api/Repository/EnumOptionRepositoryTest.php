<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Tests\Unit\Api\Repository;

use Oro\Bundle\ApiBundle\Api\Repository\EnumOptionRepository;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnumOptionRepositoryTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private EnumOptionRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->repository = new EnumOptionRepository($this->configManager);
    }

    private function createFieldConfig(string $scope, string $fieldName, string $fieldType, array $values = []): Config
    {
        return new Config(new FieldConfigId($scope, 'Test\Entity', $fieldName, $fieldType), $values);
    }

    public function testGetConfigs(): void
    {
        $enumConfig = $this->createFieldConfig('enum', 'enumField', 'enum');
        $nonEnumConfig = $this->createFieldConfig('enum', 'textField', 'string');
        $deletedEnumConfig = $this->createFieldConfig('enum', 'deletedEnumField', 'enum');
        $this->configManager->expects(self::once())
            ->method('findFieldConfigsByIndexedValue')
            ->with('enum', 'enum_code', 'test_enum')
            ->willReturn([$enumConfig, $nonEnumConfig, $deletedEnumConfig]);
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    'Test\Entity',
                    'enumField',
                    $this->createFieldConfig(
                        'extend',
                        'enumField',
                        'enum',
                        ['is_extend' => true]
                    )
                ],
                [
                    'extend',
                    'Test\Entity',
                    'deletedEnumField',
                    $this->createFieldConfig(
                        'extend',
                        'deletedEnumField',
                        'enum',
                        ['is_extend' => true, 'is_deleted' => true]
                    )
                ]
            ]);

        self::assertEquals([$enumConfig], $this->repository->getConfigs('test_enum'));
    }

    public function testIsSystemEnum(): void
    {
        $customConfig = $this->createFieldConfig('enum', 'customField', 'enum');
        $systemConfig = $this->createFieldConfig('enum', 'systemField', 'enum');
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    'Test\Entity',
                    'customField',
                    $this->createFieldConfig(
                        'extend',
                        'customField',
                        'enum',
                        ['owner' => ExtendScope::OWNER_CUSTOM]
                    )
                ],
                [
                    'extend',
                    'Test\Entity',
                    'systemField',
                    $this->createFieldConfig(
                        'extend',
                        'systemField',
                        'enum',
                        ['owner' => ExtendScope::OWNER_SYSTEM]
                    )
                ]
            ]);

        self::assertTrue($this->repository->isSystemEnum([$customConfig, $systemConfig]));
        self::assertFalse($this->repository->isSystemEnum([]));
    }

    public function testCreateOptionFromId(): void
    {
        $option = $this->repository->createOptionFromId('test_enum.new_option');

        self::assertNotNull($option);
        self::assertEquals('test_enum.new_option', $option->getId());
        self::assertEquals('test_enum', $option->getEnumCode());
        self::assertEquals('new_option', $option->getInternalId());
        self::assertEquals('new_option', $option->getName());
    }

    /** @dataProvider invalidIdsProvider */
    public function testCreateOptionFromInvalidId(string $id): void
    {
        self::assertNull($this->repository->createOptionFromId($id));
    }

    public static function invalidIdsProvider(): array
    {
        return [
            'empty' => [''],
            'without separator' => ['test_enum'],
            'empty enum code' => ['.option'],
            'empty option id' => ['test_enum.'],
            'extra separator' => ['test_enum.option.extra'],
            'long enum code' => [str_repeat('e', 65) . '.option'],
            'long option id' => ['test_enum.' . str_repeat('o', 33)]
        ];
    }
}
