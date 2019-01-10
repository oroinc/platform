<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Normalizer\ConfigNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\DateTimeNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityDataAccessor;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\EntityDataTransformer;
use Oro\Component\EntitySerializer\SerializationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ByConfigObjectNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectNormalizer */
    private $objectNormalizer;

    protected function setUp()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn(null);

        $normalizers = new ObjectNormalizerRegistry();
        $this->objectNormalizer = new ObjectNormalizer(
            $normalizers,
            new DoctrineHelper($doctrine),
            new SerializationHelper(
                new EntityDataTransformer($this->createMock(ContainerInterface::class))
            ),
            new EntityDataAccessor(),
            new ConfigNormalizer(),
            new DataNormalizer()
        );

        $normalizers->addNormalizer(
            new DateTimeNormalizer()
        );
    }

    public function testNormalizeSimpleObject()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'name' => null
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeSimpleObjectWithRenaming()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'    => null,
                'name1' => [
                    'property_path' => 'name'
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'    => 123,
                'name1' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeSimpleObjectWithDataTransformers()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'name' => [
                    'data_transformer' => [
                        function ($class, $property, $value, $config, $context) {
                            return $value . sprintf(' (%s::%s)[%s]', $class, $property, $context['key']);
                        }
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config),
            ['key' => 'context value']
        );

        self::assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name (Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group::name)[context value]'
            ],
            $result
        );
    }

    public function testNormalizeSimpleObjectWithPostSerialize()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'name' => null
            ],
            'post_serialize'   => function (array $item, array $context) {
                $item['name'] .= sprintf('_additional[%s]', $context['key']);
                $item['another'] = 'value';

                return $item;
            }
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config),
            ['key' => 'context value']
        );

        self::assertEquals(
            [
                'id'      => 123,
                'name'    => 'test_name_additional[context value]',
                'another' => 'value'
            ],
            $result
        );
    }

    public function testNormalizeObjectWithToOneRelations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'name'      => ['exclude' => true],
                'category1' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'category',
                    'fields'           => 'label'
                ],
                'owner'     => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'          => null,
                        'ownerCategory' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'category',
                            'fields'           => 'name'
                        ]
                    ],
                    'post_serialize'   => function (array $item) {
                        $item['name'] .= '_additional';

                        return $item;
                    }
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject(),
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'        => 123,
                'category1' => 'category_label',
                'owner'     => [
                    'name'          => 'user_name_additional',
                    'ownerCategory' => 'owner_category_name'
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullToOneRelations()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'name'      => ['exclude' => true],
                'category1' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'category',
                    'fields'           => 'label'
                ],
                'owner'     => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'    => null,
                        'groups1' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'groups',
                            'fields'           => 'id'
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $product,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'        => 123,
                'category1' => null,
                'owner'     => null
            ],
            $result
        );
    }

    public function testNormalizeObjectWithToManyRelation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'    => null,
                'name'  => ['exclude' => true],
                'owner' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'    => null,
                        'groups1' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'groups',
                            'fields'           => 'id'
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject(),
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'    => 123,
                'owner' => [
                    'name'    => 'user_name',
                    'groups1' => [11, 22]
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithArrayToManyRelation()
    {
        $data = $this->createProductObject();
        $data->getOwner()->setGroups($data->getOwner()->getGroups()->toArray());

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'    => null,
                'name'  => ['exclude' => true],
                'owner' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'    => null,
                        'groups1' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'groups',
                            'target_type'      => 'to-many',
                            'fields'           => 'id'
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $data,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'    => 123,
                'owner' => [
                    'name'    => 'user_name',
                    'groups1' => [11, 22]
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullToManyRelation()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');
        $owner = new Entity\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $owner->addProduct($product);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'name'      => ['exclude' => true],
                'category1' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'category',
                    'fields'           => 'label'
                ],
                'owner'     => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'    => null,
                        'groups1' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'groups',
                            'fields'           => 'id'
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $product,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'        => 123,
                'category1' => null,
                'owner'     => [
                    'name'    => 'user_name',
                    'groups1' => []
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithToOneRelationsAndDataTransformers()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'name'      => ['exclude' => true],
                'category1' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'category',
                    'fields'           => 'label'
                ],
                'owner'     => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'    => [
                            'data_transformer' => [
                                function ($class, $property, $value, $config, $context) {
                                    return $value . sprintf(' (%s::%s)[%s]', $class, $property, $context['key']);
                                }
                            ]
                        ],
                        'groups1' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'groups',
                            'fields'           => 'id'
                        ]
                    ],
                    'post_serialize'   => function (array $item, array $context) {
                        $item['name'] .= sprintf('_additional[%s]', $context['key']);

                        return $item;
                    }
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject(),
            $this->createConfigObject($config),
            ['key' => 'context value']
        );

        self::assertEquals(
            [
                'id'        => 123,
                'category1' => 'category_label',
                'owner'     => [
                    'name'    => 'user_name (Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User::name)'
                        . '[context value]_additional[context value]',
                    'groups1' => [11, 22]
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithCollapsedNullTableInheritanceRelations()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');
        $owner = new Entity\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $owner->addProduct($product);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'name'     => ['exclude' => true],
                'category' => [
                    'exclusion_policy' => 'all',
                    'collapse'         => true,
                    'fields'           => [
                        'name'      => null,
                        '__class__' => null
                    ]
                ],
                'owner'    => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'   => null,
                        'groups' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            'fields'           => [
                                'id'        => null,
                                '__class__' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $product,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'       => 123,
                'category' => null,
                'owner'    => [
                    'name'   => 'user_name',
                    'groups' => []
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithCollapsedTableInheritanceRelations()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');
        $product->setCategory(new Entity\Category('category_name'));
        $owner = new Entity\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $owner->addProduct($product);
        $group = new Entity\Group();
        $group->setId(789);
        $owner->addGroup($group);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'name'     => ['exclude' => true],
                'category' => [
                    'exclusion_policy' => 'all',
                    'collapse'         => true,
                    'fields'           => [
                        'name'      => null,
                        '__class__' => null
                    ]
                ],
                'owner'    => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'   => null,
                        'groups' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            'fields'           => [
                                'id'        => null,
                                '__class__' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $product,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'       => 123,
                'category' => [
                    'name'      => 'category_name',
                    '__class__' => Entity\Category::class
                ],
                'owner'    => [
                    'name'   => 'user_name',
                    'groups' => [
                        [
                            'id'        => 789,
                            '__class__' => Entity\Group::class
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullTableInheritanceRelations()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');
        $owner = new Entity\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $owner->addProduct($product);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'name'     => ['exclude' => true],
                'category' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'      => null,
                        '__class__' => null
                    ]
                ],
                'owner'    => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'   => null,
                        'groups' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'id'        => null,
                                '__class__' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $product,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'       => 123,
                'category' => null,
                'owner'    => [
                    'name'   => 'user_name',
                    'groups' => []
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithTableInheritanceRelations()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');
        $product->setCategory(new Entity\Category('category_name'));
        $owner = new Entity\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $owner->addProduct($product);
        $group = new Entity\Group();
        $group->setId(789);
        $owner->addGroup($group);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'name'     => ['exclude' => true],
                'category' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'      => null,
                        '__class__' => null
                    ]
                ],
                'owner'    => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name'   => null,
                        'groups' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'id'        => null,
                                '__class__' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $product,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'       => 123,
                'category' => [
                    'name'      => 'category_name',
                    '__class__' => Entity\Category::class
                ],
                'owner'    => [
                    'name'   => 'user_name',
                    'groups' => [
                        [
                            'id'        => 789,
                            '__class__' => Entity\Group::class
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testNormalizeShouldNotChangeOriginalConfig()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'name' => [
                    'depends_on' => ['id']
                ]
            ]
        ];

        $configObject = $this->createConfigObject($config);
        $srcConfig = $configObject->toArray();
        $this->objectNormalizer->normalizeObject($object, $configObject);

        self::assertEquals($srcConfig, $configObject->toArray());
    }

    public function testNormalizeWithIgnoredField()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'    => null,
                'name1' => [
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id' => 123
            ],
            $result
        );
    }

    public function testNormalizeWithDependsOnNotConfiguredField()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'name' => [
                    'depends_on' => ['id']
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'name' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeWithDependsOnExcludedField()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => [
                    'exclude' => true
                ],
                'name' => [
                    'depends_on' => ['id']
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'name' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeWithDependsOnComputedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'ownerName' => [
                    'property_path' => 'owner.computedName.value'
                ],
                'owner'     => [
                    'exclusion_policy' => 'all',
                    'exclude'          => true,
                    'fields'           => [
                        'name'         => null,
                        'computedName' => [
                            'fields' => [
                                'value' => null
                            ]
                        ]
                    ],
                    'post_serialize'   => function (array $item) {
                        $item['computedName'] = ['value' => $item['name'] . ' (computed)'];

                        return $item;
                    }
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject(),
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'        => 123,
                'ownerName' => 'user_name (computed)'
            ],
            $result
        );
    }

    public function testNormalizeWithDependsOnRenamedComputedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'           => null,
                'ownerName'    => [
                    'property_path' => 'owner.computedName.value'
                ],
                'renamedOwner' => [
                    'exclusion_policy' => 'all',
                    'exclude'          => true,
                    'property_path'    => 'owner',
                    'fields'           => [
                        'name'                => null,
                        'renamedComputedName' => [
                            'property_path' => 'computedName',
                            'fields'        => [
                                'renamedValue' => [
                                    'property_path' => 'value'
                                ]
                            ]
                        ]
                    ],
                    'post_serialize'   => function (array $item) {
                        $item['renamedComputedName'] = ['renamedValue' => $item['name'] . ' (computed)'];

                        return $item;
                    }
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject(),
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'        => 123,
                'ownerName' => 'user_name (computed)'
            ],
            $result
        );
    }

    public function testNormalizeObjectWhenRelationRepresentedByEntityIdentifierClass()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'name'     => ['exclude' => true],
                'category' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'id'        => null,
                        '__class__' => [
                            'meta_property' => true
                        ]
                    ]
                ]
            ]
        ];

        $object = new Entity\EntityWithoutGettersAndSetters();
        $object->id = 123;
        $object->category = new EntityIdentifier('category1', 'Test\Category');

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'id'       => 123,
                'category' => [
                    'id'        => 'category1',
                    '__class__' => 'Test\Category'
                ]
            ],
            $result
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The exclusion policy must be "all". Object type: "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category".
     */
    // @codingStandardsIgnoreEnd
    public function testNormalizeObjectForInvalidExclusionPolicyInRelationConfig()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'category' => [
                    'exclusion_policy' => 'none'
                ]
            ]
        ];

        $this->objectNormalizer->normalizeObject(
            $this->createProductObject(),
            $this->createConfigObject($config)
        );
    }

    /**
     * @return Entity\Product
     */
    private function createProductObject()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');
        $product->setUpdatedAt(new \DateTime('2015-12-01 10:20:30', new \DateTimeZone('UTC')));

        $category = new Entity\Category('category_name');
        $category->setLabel('category_label');
        $product->setCategory($category);

        $owner = new Entity\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $ownerCategory = new Entity\Category('owner_category_name');
        $ownerCategory->setLabel('owner_category_label');
        $owner->setCategory($ownerCategory);
        $ownerGroup1 = new Entity\Group();
        $ownerGroup1->setId(11);
        $ownerGroup1->setName('owner_group1');
        $owner->addGroup($ownerGroup1);
        $ownerGroup2 = new Entity\Group();
        $ownerGroup2->setId(22);
        $ownerGroup2->setName('owner_group2');
        $owner->addGroup($ownerGroup2);
        $owner->addProduct($product);

        return $product;
    }

    /**
     * @param array $config
     *
     * @return EntityDefinitionConfig
     */
    private function createConfigObject(array $config)
    {
        $configExtensionRegistry = new ConfigExtensionRegistry();
        $configExtensionRegistry->addExtension(new FiltersConfigExtension(new FilterOperatorRegistry([])));
        $configExtensionRegistry->addExtension(new SortersConfigExtension());

        $loaderFactory = new ConfigLoaderFactory($configExtensionRegistry);

        return $loaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
    }
}
