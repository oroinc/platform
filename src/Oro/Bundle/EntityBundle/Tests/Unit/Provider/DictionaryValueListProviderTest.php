<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class DictionaryValueListProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $groupingConfigProvider;

    /** @var DictionaryValueListProvider */
    protected $dictionaryValueListProvider;

    protected function setUp()
    {
        $this->configManager          = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $this->extendConfigProvider],
                    ['grouping', $this->groupingConfigProvider],
                ]
            );

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->dictionaryValueListProvider = new DictionaryValueListProvider(
            $this->configManager,
            $this->doctrine
        );
    }

    public function testSupports()
    {
        $className = 'Test\Dictionary';

        $groupingConfig = $this->getEntityConfig(
            $className,
            [
                'groups' => [GroupingScope::GROUP_DICTIONARY, 'another'],
            ],
            'grouping'
        );

        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($groupingConfig);

        $this->assertTrue($this->dictionaryValueListProvider->supports($className));
    }

    public function testSupportsForNotConfigurableEntity()
    {
        $className = 'Test\NotConfigurableEntity';

        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);

        $this->assertFalse($this->dictionaryValueListProvider->supports($className));
    }

    public function testSupportsForNotDictionary()
    {
        $className = 'Test\NotDictionary';

        $groupingConfig = $this->getEntityConfig(
            $className,
            [],
            'grouping'
        );

        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($groupingConfig);

        $this->assertFalse($this->dictionaryValueListProvider->supports($className));
    }

    public function testSupportsForNotDictionaryWithGroups()
    {
        $className = 'Test\NotDictionaryWithGroups';

        $groupingConfig = $this->getEntityConfig(
            $className,
            [
                'groups' => ['another'],
            ],
            'grouping'
        );

        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($groupingConfig);

        $this->assertFalse($this->dictionaryValueListProvider->supports($className));
    }

    public function testGetValueListQueryBuilder()
    {
        $className = 'Test\Dictionary';

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
            $this->dictionaryValueListProvider->getValueListQueryBuilder($className)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetSerializationConfig()
    {
        $className                = 'Test\Dictionary';
        $manyToOneTargetClassName = 'Test\ManyToOne';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $manyToOneTargetMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap(
                [
                    [$className, $metadata],
                    [$manyToOneTargetClassName, $manyToOneTargetMetadata],
                ]
            );

        $metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'default', 'extend_field']);
        $metadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['manyToOne', 'manyToMany', 'extend_association']);
        $metadata->expects($this->exactly(2))
            ->method('getAssociationMapping')
            ->willReturnMap(
                [
                    [
                        'manyToOne',
                        [
                            'type'         => ClassMetadata::MANY_TO_ONE,
                            'isOwningSide' => true,
                            'targetEntity' => $manyToOneTargetClassName
                        ]
                    ],
                    [
                        'manyToMany',
                        [
                            'type'         => ClassMetadata::MANY_TO_MANY,
                            'isOwningSide' => true,
                            'targetEntity' => 'Test\ManyToMany'
                        ]
                    ],
                ]
            );

        $manyToOneTargetMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->extendConfigProvider->expects($this->exactly(7))
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
                        'default',
                        $this->getEntityFieldConfig($className, 'default', [])
                    ],
                    [
                        $className,
                        'extend_field',
                        $this->getEntityFieldConfig($className, 'extend_field', ['is_extend' => true])
                    ],
                    [
                        $className,
                        'manyToOne',
                        $this->getEntityFieldConfig($className, 'manyToOne', [])
                    ],
                    [
                        $className,
                        'manyToMany',
                        $this->getEntityFieldConfig($className, 'manyToMany', [])
                    ],
                    [
                        $className,
                        'extend_association',
                        $this->getEntityFieldConfig($className, 'extend_field', ['is_extend' => true])
                    ]
                ]
            );

        $this->assertEquals(
            [
                'exclusion_policy' => 'all',
                'hints'            => ['HINT_TRANSLATABLE'],
                'fields'           => [
                    'id'        => null,
                    'name'      => null,
                    'default'   => null,
                    'manyToOne' => ['fields' => 'id']
                ]
            ],
            $this->dictionaryValueListProvider->getSerializationConfig($className)
        );
    }

    public function testGetSupportedEntityClasses()
    {
        $configs = [
            $this->getEntityConfig(
                'Test\Dictionary',
                [
                    'groups' => [GroupingScope::GROUP_DICTIONARY, 'another'],
                ],
                'grouping'
            ),
            $this->getEntityConfig(
                'Test\NotDictionary',
                [],
                'grouping'
            ),
            $this->getEntityConfig(
                'Test\NotDictionaryWithGroups',
                [
                    'groups' => ['another'],
                ],
                'grouping'
            ),
        ];

        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->willReturn($configs);

        $this->assertEquals(
            ['Test\Dictionary'],
            $this->dictionaryValueListProvider->getSupportedEntityClasses()
        );
    }

    /**
     * @param string $className
     * @param mixed  $values
     * @param string $scope
     *
     * @return Config
     */
    protected function getEntityConfig($className, $values, $scope = 'extend')
    {
        $configId = new EntityConfigId($scope, $className);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param mixed  $values
     * @param string $scope
     *
     * @return Config
     */
    protected function getEntityFieldConfig($className, $fieldName, $values, $scope = 'extend')
    {
        $configId = new FieldConfigId($scope, $className, $fieldName);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
