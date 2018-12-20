<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueListProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueListProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var EnumValueListProvider */
    protected $enumValueListProvider;

    protected function setUp()
    {
        $this->configManager        = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->extendConfigProvider);

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->enumValueListProvider = new EnumValueListProvider(
            $this->configManager,
            $this->doctrine
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

        $qb   = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
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

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'priority', 'default', 'extend_field']);

        $this->extendConfigProvider->expects($this->exactly(5))
            ->method('getConfig')
            ->willReturnMap(
                [
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
                ]
            );

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

    /**
     * @param string $className
     * @param mixed  $values
     *
     * @return Config
     */
    protected function getEntityConfig($className, $values)
    {
        $configId = new EntityConfigId('extend', $className);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param mixed  $values
     *
     * @return Config
     */
    protected function getEntityFieldConfig($className, $fieldName, $values)
    {
        $configId = new FieldConfigId('extend', $className, $fieldName);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
