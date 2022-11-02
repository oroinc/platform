<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueListProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueListProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EnumValueListProvider */
    private $enumValueListProvider;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->extendConfigProvider);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->enumValueListProvider = new EnumValueListProvider(
            $configManager,
            $doctrine
        );
    }

    public function testSupports()
    {
        $className = 'Test\Enum';

        $extendConfig = $this->getEntityConfig(
            $className,
            [
                'is_extend' => true,
                'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                'state'     => ExtendScope::STATE_ACTIVE
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($extendConfig);

        $this->assertTrue($this->enumValueListProvider->supports($className));
    }

    public function testSupportsForNotConfigurableEntity()
    {
        $className = 'Test\NotConfigurableEntity';

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);

        $this->assertFalse($this->enumValueListProvider->supports($className));
    }

    public function testSupportsForNewEnum()
    {
        $className = 'Test\NewEnum';

        $extendConfig = $this->getEntityConfig(
            $className,
            [
                'inherit' => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                'state'   => ExtendScope::STATE_NEW
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($extendConfig);

        $this->assertFalse($this->enumValueListProvider->supports($className));
    }

    public function testSupportsForDeletedEnum()
    {
        $className = 'Test\DeletedEnum';

        $extendConfig = $this->getEntityConfig(
            $className,
            [
                'inherit'    => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                'state'      => ExtendScope::STATE_ACTIVE,
                'is_deleted' => true
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($extendConfig);

        $this->assertFalse($this->enumValueListProvider->supports($className));
    }

    public function testSupportsForNotEnum()
    {
        $className = 'Test\NotEnum';

        $extendConfig = $this->getEntityConfig(
            $className,
            [
                'state' => ExtendScope::STATE_ACTIVE
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($extendConfig);

        $this->assertFalse($this->enumValueListProvider->supports($className));
    }

    public function testGetValueListQueryBuilder()
    {
        $className = 'Test\Enum';

        $qb = $this->createMock(QueryBuilder::class);
        $repo = $this->createMock(EntityRepository::class);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with($className)
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->assertSame(
            $qb,
            $this->enumValueListProvider->getValueListQueryBuilder($className)
        );
    }

    public function testGetSerializationConfig()
    {
        $className = 'Test\Enum';

        $metadata = $this->createMock(ClassMetadata::class);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'priority', 'default', 'extend_field']);

        $this->extendConfigProvider->expects($this->exactly(5))
            ->method('getConfig')
            ->willReturnMap([
                [
                    $className,
                    'id',
                    $this->getEntityFieldConfig($className, 'id', [])
                ],
                [
                    $className,
                    'name',
                    $this->getEntityFieldConfig($className, 'name', [])
                ],
                [
                    $className,
                    'priority',
                    $this->getEntityFieldConfig($className, 'priority', [])
                ],
                [
                    $className,
                    'default',
                    $this->getEntityFieldConfig($className, 'default', [])
                ],
                [
                    $className,
                    'extend_field',
                    $this->getEntityFieldConfig($className, 'extend_field', ['is_extend' => true])
                ],
            ]);

        $this->assertEquals(
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

    public function testGetSupportedEntityClasses()
    {
        $configs = [
            $this->getEntityConfig(
                'Test\Enum',
                [
                    'is_extend' => true,
                    'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                    'state'     => ExtendScope::STATE_ACTIVE
                ]
            ),
            $this->getEntityConfig(
                'Test\NewEnum',
                [
                    'is_extend' => true,
                    'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                    'state'     => ExtendScope::STATE_NEW
                ]
            ),
            $this->getEntityConfig(
                'Test\DeletedEnum',
                [
                    'is_extend'  => true,
                    'inherit'    => ExtendHelper::BASE_ENUM_VALUE_CLASS,
                    'state'      => ExtendScope::STATE_ACTIVE,
                    'is_deleted' => true
                ]
            ),
            $this->getEntityConfig(
                'Test\NotEnum',
                [
                    'is_extend' => true,
                    'state'     => ExtendScope::STATE_ACTIVE
                ]
            ),
        ];

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->willReturn($configs);

        $this->assertEquals(
            ['Test\Enum'],
            $this->enumValueListProvider->getSupportedEntityClasses()
        );
    }

    private function getEntityConfig(string $className, array $values): Config
    {
        $config = new Config(new EntityConfigId('extend', $className));
        $config->setValues($values);

        return $config;
    }

    private function getEntityFieldConfig(string $className, string $fieldName, array $values): Config
    {
        $config = new Config(new FieldConfigId('extend', $className, $fieldName));
        $config->setValues($values);

        return $config;
    }
}
