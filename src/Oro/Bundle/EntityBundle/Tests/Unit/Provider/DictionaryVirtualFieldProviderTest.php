<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\DictionaryVirtualFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;

class DictionaryVirtualFieldProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private EntityManager&MockObject $em;
    private AbstractAdapter&MockObject $cache;
    private DictionaryVirtualFieldProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->cache = $this->createMock(AbstractAdapter::class);

        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->provider = new DictionaryVirtualFieldProvider(
            $this->configManager,
            $doctrine,
            $translator,
            $this->cache,
            (new InflectorFactory())->build()
        );
        $this->provider->registerDictionary('Acme\TestBundle\Entity\RegisteredDictionary', ['id']);
    }

    public function testDictionaryWithOneExplicitlyDeclaredVirtualFields(): void
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

        self::assertEquals(
            ['test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        self::assertTrue(
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );
        self::assertEquals(
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

    public function testDictionaryWithTwoExplicitlyDeclaredVirtualFields(): void
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

        self::assertEquals(
            ['test_rel_id', 'test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        self::assertTrue(
            $this->provider->isVirtualField($entityClassName, 'test_rel_id')
        );
        self::assertTrue(
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );

        self::assertEquals(
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

        self::assertEquals(
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

    public function testDictionaryWithOneVirtualField(): void
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

        self::assertEquals(
            ['test_rel_name'],
            $this->provider->getVirtualFields($entityClassName)
        );
        self::assertTrue(
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );

        self::assertEquals(
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

    public function testDictionaryWithSeveralVirtualFields(): void
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

        self::assertEquals(
            ['test_rel_code', 'test_rel_label'],
            $this->provider->getVirtualFields($entityClassName)
        );
        self::assertFalse(
            $this->provider->isVirtualField($entityClassName, 'test_rel_name')
        );
        self::assertTrue(
            $this->provider->isVirtualField($entityClassName, 'test_rel_code')
        );
        self::assertTrue(
            $this->provider->isVirtualField($entityClassName, 'test_rel_label')
        );

        self::assertEquals(
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

        self::assertEquals(
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

    public function testCachedDictionaries(): void
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = new ClassMetadata($entityClassName);
        $entityMetadata->associationMappings = [
            'testRel' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Acme\TestBundle\Entity\Dictionary3'
            ]
        ];

        $this->cache->expects(self::once())
            ->method('get')
            ->with('dictionaries')
            ->willReturn(['Acme\TestBundle\Entity\Dictionary3' => ['name']]);

        $this->configManager->expects(self::never())
            ->method('getConfigs');
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClassName)
            ->willReturn($entityMetadata);

        self::assertEquals(
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

    public function testClearCache(): void
    {
        $this->cache->expects(self::once())
            ->method('clear');

        $this->provider->clearCache();
    }

    public function testRegisteredDictionary(): void
    {
        $entityClassName = 'Acme\TestBundle\Entity\TestEntity';

        $entityMetadata = new ClassMetadata($entityClassName);
        $entityMetadata->associationMappings = [
            'testRel' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Acme\TestBundle\Entity\RegisteredDictionary'
            ]
        ];

        $this->initialize($entityMetadata);

        self::assertEquals(
            ['test_rel_id'],
            $this->provider->getVirtualFields($entityClassName)
        );
        self::assertTrue(
            $this->provider->isVirtualField($entityClassName, 'test_rel_id')
        );
        self::assertEquals(
            [
                'select' => [
                    'expr' => 'target.id',
                    'label' => 'acme.test.testentity.test_rel.label',
                    'return_type' => 'dictionary',
                    'related_entity_name' => 'Acme\TestBundle\Entity\RegisteredDictionary'
                ],
                'join' => [
                    'left' => [
                        ['join' => 'entity.testRel', 'alias' => 'target']
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($entityClassName, 'test_rel_id')
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function initialize(ClassMetadata $metadata): void
    {
        $this->cache->expects(self::once())
            ->method('get')
            ->with('dictionaries')
            ->willReturn([
                'Acme\TestBundle\Entity\Dictionary1' => ['name'],
                'Acme\TestBundle\Entity\Dictionary2' => ['id', 'name'],
                'Acme\TestBundle\Entity\Dictionary3' => ['name'],
                'Acme\TestBundle\Entity\Dictionary4' => ['code', 'label'],
                'Acme\TestBundle\Entity\RegisteredDictionary' => ['id']
            ]);

        $this->configManager->expects(self::any())
            ->method('getConfigs')
            ->with('grouping')
            ->willReturn([
                $this->createEntityConfig(
                    'grouping',
                    'Acme\TestBundle\Entity\Dictionary1',
                    ['groups' => ['dictionary']]
                ),
                $this->createEntityConfig(
                    'grouping',
                    'Acme\TestBundle\Entity\Dictionary2',
                    ['groups' => ['dictionary']]
                ),
                $this->createEntityConfig(
                    'grouping',
                    'Acme\TestBundle\Entity\Dictionary3',
                    ['groups' => ['dictionary']]
                ),
                $this->createEntityConfig(
                    'grouping',
                    'Acme\TestBundle\Entity\Dictionary4',
                    ['groups' => ['dictionary']]
                )
            ]);
        $this->configManager->expects(self::any())
            ->method('getEntityConfig')
            ->with('dictionary')
            ->willReturnCallback(function ($scope, $class) {
                switch ($class) {
                    case 'Acme\TestBundle\Entity\Dictionary1':
                        return $this->createEntityConfig('dictionary', $class, ['virtual_fields' => ['name']]);
                    case 'Acme\TestBundle\Entity\Dictionary2':
                        return $this->createEntityConfig('dictionary', $class, ['virtual_fields' => ['id', 'name']]);
                    case 'Acme\TestBundle\Entity\Dictionary3':
                    case 'Acme\TestBundle\Entity\Dictionary4':
                        return $this->createEntityConfig('dictionary', $class);
                    default:
                        throw new RuntimeException(sprintf('Entity "%s" is not configurable', $class));
                }
            });

        $this->em->expects(self::any())
            ->method('getClassMetadata')
            ->willReturnCallback(function ($class) use ($metadata) {
                switch ($class) {
                    case 'Acme\TestBundle\Entity\Dictionary2':
                    case 'Acme\TestBundle\Entity\Dictionary3':
                    case 'Acme\TestBundle\Entity\Dictionary1':
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

    private function createEntityConfig(string $scope, string $className, array $values = []): Config
    {
        return new Config(new EntityConfigId($scope, $className), $values);
    }

    private function createDictionaryMetadata(
        string $className,
        array $fields = [],
        string $idFieldName = 'id'
    ): ClassMetadata {
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
