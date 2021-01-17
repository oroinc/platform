<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Provider\DictionaryVirtualFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class DictionaryVirtualFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var DictionaryVirtualFieldProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->cache = $this->createMock(CacheProvider::class);

        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->provider = new DictionaryVirtualFieldProvider(
            $this->configManager,
            $doctrine,
            $translator,
            $this->cache,
            (new InflectorFactory())->build()
        );
    }

    public function testDictionaryWithOneExplicitlyDeclaredVirtualFields()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = new ClassMetadata($entityClassName);
        $entityMetadata->associationMappings = [
            'testRel' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Acme\TestBundle\Entity\Dictionary1'
            ]
        ];

        $this->initialize($entityMetadata);

        $this->assertEquals(
            ['test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );
        $this->assertEquals(
            [
                'select' => [
                    'expr' => 'target.name',
                    'label' => 'acme.test.testentity.test_rel.label',
                    'return_type' => 'dictionary',
                    'related_entity_name' => 'Acme\TestBundle\Entity\Dictionary1'
                ],
                'join' => [
                    'left' => [
                        ['join' => 'entity.testRel', 'alias' => 'target']
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_name')
        );
    }

    public function testDictionaryWithTwoExplicitlyDeclaredVirtualFields()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = new ClassMetadata($entityClassName);
        $entityMetadata->associationMappings = [
            'testRel' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Acme\TestBundle\Entity\Dictionary2'
            ]
        ];

        $this->initialize($entityMetadata);

        $this->assertEquals(
            ['test_rel_id', 'test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_id')
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );

        $this->assertEquals(
            [
                'select' => [
                    'expr' => 'target.id',
                    'label' => 'acme.test.testentity.test_rel_id.label',
                    'return_type' => 'dictionary',
                    'related_entity_name' => 'Acme\TestBundle\Entity\Dictionary2'
                ],
                'join' => [
                    'left' => [
                        ['join' => 'entity.testRel', 'alias' => 'target']
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_id')
        );

        $this->assertEquals(
            [
                'select' => [
                    'expr' => 'target.name',
                    'label' => 'acme.test.testentity.test_rel_name.label',
                    'return_type' => 'dictionary',
                    'related_entity_name' => 'Acme\TestBundle\Entity\Dictionary2'
                ],
                'join' => [
                    'left' => [
                        ['join' => 'entity.testRel', 'alias' => 'target']
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_name')
        );
    }

    public function testDictionaryWithOneVirtualField()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = new ClassMetadata($entityClassName);
        $entityMetadata->associationMappings = [
            'testRel' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Acme\TestBundle\Entity\Dictionary3'
            ]
        ];

        $this->initialize($entityMetadata);

        $this->assertEquals(
            ['test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );

        $this->assertEquals(
            [
                'select' => [
                    'expr' => 'target.name',
                    'label' => 'acme.test.testentity.test_rel.label',
                    'return_type' => 'dictionary',
                    'related_entity_name' => 'Acme\TestBundle\Entity\Dictionary3'
                ],
                'join' => [
                    'left' => [
                        ['join' => 'entity.testRel', 'alias' => 'target']
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_name')
        );
    }

    public function testDictionaryWithSeveralVirtualFields()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = new ClassMetadata($entityClassName);
        $entityMetadata->associationMappings = [
            'testRel' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Acme\TestBundle\Entity\Dictionary4'
            ]
        ];

        $this->initialize($entityMetadata);

        $this->assertEquals(
            ['test_rel_code', 'test_rel_label'],
            $this->provider->getVirtualFields($entityClassName)
        );
        $this->assertEquals(
            false,
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_code')
        );
        $this->assertEquals(
            true,
            $this->provider->isVirtualField($entityClassName, 'test_rel_label')
        );

        $this->assertEquals(
            [
                'select' => [
                    'expr' => 'target.code',
                    'label' => 'acme.test.testentity.test_rel_code.label',
                    'return_type' => 'dictionary',
                    'related_entity_name' => 'Acme\TestBundle\Entity\Dictionary4'
                ],
                'join' => [
                    'left' => [
                        ['join' => 'entity.testRel', 'alias' => 'target']
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_code')
        );

        $this->assertEquals(
            [
                'select' => [
                    'expr' => 'target.label',
                    'label' => 'acme.test.testentity.test_rel_label.label',
                    'return_type' => 'dictionary',
                    'related_entity_name' => 'Acme\TestBundle\Entity\Dictionary4'
                ],
                'join' => [
                    'left' => [
                        ['join' => 'entity.testRel', 'alias' => 'target']
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_label')
        );
    }

    public function testCachedDictionaries()
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = new ClassMetadata($entityClassName);
        $entityMetadata->associationMappings = [
            'testRel' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Acme\TestBundle\Entity\Dictionary3'
            ]
        ];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('dictionaries')
            ->willReturn([
                'Acme\TestBundle\Entity\Dictionary3' => ['name']
            ]);
        $this->cache->expects($this->never())
            ->method('save');

        $this->configManager->expects($this->never())
            ->method('getConfigs');
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClassName)
            ->willReturn($entityMetadata);

        $this->assertEquals(
            [
                'select' => [
                    'expr' => 'target.name',
                    'label' => 'acme.test.testentity.test_rel.label',
                    'return_type' => 'dictionary',
                    'related_entity_name' => 'Acme\TestBundle\Entity\Dictionary3'
                ],
                'join' => [
                    'left' => [
                        ['join' => 'entity.testRel', 'alias' => 'target']
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_name')
        );
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->provider->clearCache();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function initialize(ClassMetadata $metadata)
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('dictionaries')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                'dictionaries',
                [
                    'Acme\TestBundle\Entity\Dictionary1' => ['name'],
                    'Acme\TestBundle\Entity\Dictionary2' => ['id', 'name'],
                    'Acme\TestBundle\Entity\Dictionary3' => ['name'],
                    'Acme\TestBundle\Entity\Dictionary4' => ['code', 'label']
                ]
            );

        $this->configManager->expects($this->any())
            ->method('getConfigs')
            ->with('grouping')
            ->willReturn([
                $this->createEntityConfig(
                    'grouping',
                    'Acme\TestBundle\Entity\Dictionary1',
                    ['groups' => [GroupingScope::GROUP_DICTIONARY]]
                ),
                $this->createEntityConfig(
                    'grouping',
                    'Acme\TestBundle\Entity\Dictionary2',
                    ['groups' => [GroupingScope::GROUP_DICTIONARY]]
                ),
                $this->createEntityConfig(
                    'grouping',
                    'Acme\TestBundle\Entity\Dictionary3',
                    ['groups' => [GroupingScope::GROUP_DICTIONARY]]
                ),
                $this->createEntityConfig(
                    'grouping',
                    'Acme\TestBundle\Entity\Dictionary4',
                    ['groups' => [GroupingScope::GROUP_DICTIONARY]]
                )
            ]);
        $this->configManager->expects($this->any())
            ->method('getEntityConfig')
            ->with('dictionary')
            ->willReturnCallback(function ($scope, $class) {
                switch ($class) {
                    case 'Acme\TestBundle\Entity\Dictionary1':
                        return $this->createEntityConfig('dictionary', $class, ['virtual_fields' => ['name']]);
                    case 'Acme\TestBundle\Entity\Dictionary2':
                        return $this->createEntityConfig('dictionary', $class, ['virtual_fields' => ['id', 'name']]);
                    case 'Acme\TestBundle\Entity\Dictionary3':
                        return $this->createEntityConfig('dictionary', $class);
                    case 'Acme\TestBundle\Entity\Dictionary4':
                        return $this->createEntityConfig('dictionary', $class);
                    default:
                        throw new RuntimeException(sprintf('Entity "%s" is not configurable', $class));
                }
            });

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnCallback(function ($class) use ($metadata) {
                switch ($class) {
                    case 'Acme\TestBundle\Entity\Dictionary1':
                        return $this->createDictionaryMetadata($class);
                    case 'Acme\TestBundle\Entity\Dictionary2':
                        return $this->createDictionaryMetadata($class);
                    case 'Acme\TestBundle\Entity\Dictionary3':
                        return $this->createDictionaryMetadata($class);
                    case 'Acme\TestBundle\Entity\Dictionary4':
                        return $this->createDictionaryMetadata(
                            $class,
                            ['name' => 'string', 'code' => 'string', 'label' => 'string'],
                            'name'
                        );
                    default:
                        if ($metadata->name !== $class) {
                            throw MappingException::reflectionFailure($class, new \ReflectionException());
                        }

                        return $metadata;
                }
            });
    }

    /**
     * @param string $scope
     * @param string $className
     * @param array  $values
     * @return Config
     */
    private function createEntityConfig($scope, $className, $values = [])
    {
        $config = new Config(new EntityConfigId($scope, $className));
        $config->setValues($values);

        return $config;
    }

    /**
     * @param array  $fields key = fieldName, value = fieldType
     * @param string $idFieldName
     *
     * @return ClassMetadata
     */
    private function createDictionaryMetadata($className, $fields = [], $idFieldName = 'id')
    {
        if (empty($fields)) {
            $fields = ['id' => 'integer', 'name' => 'string'];
        }
        $fieldMappings = [];
        foreach ($fields as $name => $type) {
            $fieldMappings[$name] = ['type' => $type];
        }

        $metadata = new ClassMetadata($className);
        $metadata->fieldMappings = $fieldMappings;
        $metadata->identifier = [$idFieldName];

        return $metadata;
    }
}
