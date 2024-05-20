<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueListProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueListProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EnumValueListProvider */
    private $enumValueListProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->enumValueListProvider = new EnumValueListProvider($this->configManager, $doctrine);
    }

    private function getEntityConfig(string $className, array $values): Config
    {
        return new Config(new EntityConfigId('extend', $className), $values);
    }

    private function getEntityFieldConfig(string $className, string $fieldName, array $values): Config
    {
        return new Config(new FieldConfigId('extend', $className, $fieldName), $values);
    }

    public function testSupports(): void
    {
        $className = 'Test\Enum';

        $extendConfig = $this->getEntityConfig($className, [
            'is_extend' => true,
            'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS,
            'state'     => ExtendScope::STATE_ACTIVE
        ]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', $className)
            ->willReturn($extendConfig);

        self::assertTrue($this->enumValueListProvider->supports($className));
    }

    public function testSupportsForNotConfigurableEntity(): void
    {
        $className = 'Test\NotConfigurableEntity';

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);

        self::assertFalse($this->enumValueListProvider->supports($className));
    }

    public function testSupportsForNewEnum(): void
    {
        $className = 'Test\NewEnum';

        $extendConfig = $this->getEntityConfig($className, [
            'inherit' => ExtendHelper::BASE_ENUM_VALUE_CLASS,
            'state'   => ExtendScope::STATE_NEW
        ]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', $className)
            ->willReturn($extendConfig);

        self::assertFalse($this->enumValueListProvider->supports($className));
    }

    public function testSupportsForDeletedEnum(): void
    {
        $className = 'Test\DeletedEnum';

        $extendConfig = $this->getEntityConfig($className, [
            'inherit'    => ExtendHelper::BASE_ENUM_VALUE_CLASS,
            'state'      => ExtendScope::STATE_ACTIVE,
            'is_deleted' => true
        ]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', $className)
            ->willReturn($extendConfig);

        self::assertFalse($this->enumValueListProvider->supports($className));
    }

    public function testSupportsForNotEnum(): void
    {
        $className = 'Test\NotEnum';

        $extendConfig = $this->getEntityConfig($className, [
            'state' => ExtendScope::STATE_ACTIVE
        ]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', $className)
            ->willReturn($extendConfig);

        self::assertFalse($this->enumValueListProvider->supports($className));
    }

    public function testGetValueListQueryBuilder(): void
    {
        $className = 'Test\Enum';

        $qb = $this->createMock(QueryBuilder::class);
        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('from')
            ->with($className, 'e')
            ->willReturnSelf();

        self::assertSame($qb, $this->enumValueListProvider->getValueListQueryBuilder($className));
    }

    public function testGetSerializationConfig(): void
    {
        $className = 'Test\Enum';

        $metadata = $this->createMock(ClassMetadata::class);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'priority', 'default', 'extend_field']);

        $this->configManager->expects(self::exactly(5))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    $className,
                    'id',
                    $this->getEntityFieldConfig($className, 'id', [])
                ],
                [
                    'extend',
                    $className,
                    'name',
                    $this->getEntityFieldConfig($className, 'name', [])
                ],
                [
                    'extend',
                    $className,
                    'priority',
                    $this->getEntityFieldConfig($className, 'priority', [])
                ],
                [
                    'extend',
                    $className,
                    'default',
                    $this->getEntityFieldConfig($className, 'default', [])
                ],
                [
                    'extend',
                    $className,
                    'extend_field',
                    $this->getEntityFieldConfig($className, 'extend_field', ['is_extend' => true])
                ],
            ]);

        self::assertEquals(
            [
                'exclusion_policy' => 'all',
                'hints'            => ['HINT_TRANSLATABLE'],
                'fields'           => [
                    'id'      => null,
                    'name'    => null,
                    'order'   => [
                        'property_path' => 'priority'
                    ],
                    'default' => null
                ]
            ],
            $this->enumValueListProvider->getSerializationConfig($className)
        );
    }

    public function testGetSupportedEntityClasses(): void
    {
        $configs = [
            $this->getEntityConfig('Test\Enum', [
                'is_extend' => true,
                'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                'state'     => ExtendScope::STATE_ACTIVE
            ]),
            $this->getEntityConfig('Test\NewEnum', [
                'is_extend' => true,
                'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                'state'     => ExtendScope::STATE_NEW
            ]),
            $this->getEntityConfig('Test\DeletedEnum', [
                'is_extend'  => true,
                'inherit'    => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                'state'      => ExtendScope::STATE_ACTIVE,
                'is_deleted' => true
            ]),
            $this->getEntityConfig('Test\NotEnum', [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ])
        ];

        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend', null, true)
            ->willReturn($configs);

        self::assertEquals(
            ['Test\Enum'],
            $this->enumValueListProvider->getSupportedEntityClasses()
        );
    }
}
