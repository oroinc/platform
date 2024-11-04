<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionEntityClassProvider;

class EnumOptionEntityClassProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EnumOptionEntityClassProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new EnumOptionEntityClassProvider($this->configManager);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetClassNames(): void
    {
        $this->configManager->expects(self::exactly(3))
            ->method('getIds')
            ->willReturnMap([
                [
                    'extend',
                    null,
                    true,
                    [
                        new EntityConfigId('extend', 'Test\Entity1'),
                        new EntityConfigId('extend', 'Test\Entity2')
                    ]
                ],
                [
                    'extend',
                    'Test\Entity1',
                    true,
                    [
                        new FieldConfigId('extend', 'Test\Entity1', 'id', 'integer')
                    ]
                ],
                [
                    'extend',
                    'Test\Entity2',
                    true,
                    [
                        new FieldConfigId('extend', 'Test\Entity2', 'id', 'integer'),
                        new FieldConfigId('extend', 'Test\Entity2', 'enumField1', 'enum'),
                        new FieldConfigId('extend', 'Test\Entity2', 'enumField2', 'enum'),
                        new FieldConfigId('extend', 'Test\Entity2', 'enumField3', 'enum'),
                        new FieldConfigId('extend', 'Test\Entity2', 'multiEnumField1', 'multiEnum'),
                        new FieldConfigId('extend', 'Test\Entity2', 'multiEnumField2', 'multiEnum'),
                        new FieldConfigId('extend', 'Test\Entity2', 'multiEnumField3', 'multiEnum')
                    ]
                ],
            ]);
        $this->configManager->expects(self::atLeastOnce())
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    'Test\Entity2',
                    'enumField1',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'enumField1', 'enum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
                    )
                ],
                [
                    'extend',
                    'Test\Entity2',
                    'enumField2',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'enumField2', 'enum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
                    )
                ],
                [
                    'extend',
                    'Test\Entity2',
                    'enumField3',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'enumField3', 'enum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_NEW]
                    )
                ],
                [
                    'extend',
                    'Test\Entity2',
                    'multiEnumField1',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'multiEnumField1', 'multiEnum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
                    )
                ],
                [
                    'extend',
                    'Test\Entity2',
                    'multiEnumField2',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'multiEnumField2', 'multiEnum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
                    )
                ],
                [
                    'extend',
                    'Test\Entity2',
                    'multiEnumField3',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'multiEnumField3', 'multiEnum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_NEW]
                    )
                ],
                [
                    'enum',
                    'Test\Entity2',
                    'enumField1',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'enumField1', 'enum'),
                        ['enum_code' => 'enum_1']
                    )
                ],
                [
                    'enum',
                    'Test\Entity2',
                    'enumField2',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'enumField2', 'enum'),
                        []
                    )
                ],
                [
                    'enum',
                    'Test\Entity2',
                    'multiEnumField1',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'multiEnumField1', 'multiEnum'),
                        ['enum_code' => 'multi_enum_1']
                    )
                ],
                [
                    'enum',
                    'Test\Entity2',
                    'multiEnumField2',
                    new Config(
                        new FieldConfigId('extend', 'Test\Entity2', 'multiEnumField2', 'multiEnum'),
                        []
                    )
                ]
            ]);

        self::assertEquals(
            [
                'Extend\Entity\EV_Enum_1',
                'Extend\Entity\EV_Multi_Enum_1',
                'Oro\Bundle\EntityExtendBundle\Entity\EnumOption'
            ],
            $this->provider->getClassNames()
        );
    }
}
