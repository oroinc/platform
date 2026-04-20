<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\EnumFieldFormOptionsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnumFieldFormOptionsProviderTest extends TestCase
{
    private EntityConfigManager&MockObject $entityConfigManager;
    private EnumFieldFormOptionsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);

        $this->provider = new EnumFieldFormOptionsProvider($this->entityConfigManager);
    }

    public function testGetOptions(): void
    {
        $className = \stdClass::class;
        $fieldName = 'sampleField';
        $fieldType = 'enum';
        $label = 'Sample Label';
        $enumCode = 'sample_enum_code';

        $entityFieldConfig = new Config(
            new FieldConfigId('entity', $className, $fieldName, $fieldType),
            ['label' => $label]
        );

        $enumFieldConfig = new Config(
            new FieldConfigId('enum', $className, $fieldName, $fieldType),
            ['enum_code' => $enumCode]
        );

        $this->entityConfigManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                ['entity', $className, $fieldName, $entityFieldConfig],
                ['enum', $className, $fieldName, $enumFieldConfig],
            ]);

        $result = $this->provider->getOptions($className, $fieldName);

        self::assertEquals(
            [
                'label' => 'Sample Label',
                'block' => 'general',
                'enum_code' => 'sample_enum_code',
                'multiple' => false,
            ],
            $result
        );
    }
}
