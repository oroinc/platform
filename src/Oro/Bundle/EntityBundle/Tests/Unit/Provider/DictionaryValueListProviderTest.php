<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class DictionaryValueListProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $groupingConfigProvider;

    /** @var DictionaryValueListProvider */
    private $dictionaryValueListProvider;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->groupingConfigProvider = $this->createMock(ConfigProvider::class);
        $this->em = $this->createMock(EntityManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $this->extendConfigProvider],
                ['grouping', $this->groupingConfigProvider],
            ]);

        $this->dictionaryValueListProvider = new DictionaryValueListProvider($configManager, $doctrine);
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
            $this->dictionaryValueListProvider->getValueListQueryBuilder($className)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetSerializationConfig()
    {
        $className = 'Test\Dictionary';
        $manyToOneTargetClassName = 'Test\ManyToOne';

        $metadata = $this->createMock(ClassMetadata::class);

        $manyToOneTargetMetadata = $this->createMock(ClassMetadata::class);

        $this->em->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [$className, $metadata],
                [$manyToOneTargetClassName, $manyToOneTargetMetadata],
            ]);

        $metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'default', 'extend_field']);
        $metadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['manyToOne', 'manyToMany', 'extend_association']);
        $metadata->expects($this->exactly(2))
            ->method('getAssociationMapping')
            ->willReturnMap([
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
            ]);

        $manyToOneTargetMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->extendConfigProvider->expects($this->exactly(7))
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
            ]);

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

    private function getEntityConfig(string $className, array $values, string $scope = 'extend'): Config
    {
        $configId = new EntityConfigId($scope, $className);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }

    private function getEntityFieldConfig(
        string $className,
        string $fieldName,
        array $values,
        string $scope = 'extend'
    ): Config {
        $configId = new FieldConfigId($scope, $className, $fieldName);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
