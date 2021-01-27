<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Normalizer\ConfigNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityDataAccessor;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\DataTransformer;
use Oro\Component\EntitySerializer\SerializationHelper;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ByConfigObjectNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectNormalizer */
    private $objectNormalizer;

    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn(null);

        $this->objectNormalizer = new ObjectNormalizer(
            new ObjectNormalizerRegistry(
                [],
                TestContainerBuilder::create()->getContainer($this),
                new RequestExpressionMatcher()
            ),
            new DoctrineHelper($doctrine),
            new SerializationHelper(new DataTransformer($this->createMock(ContainerInterface::class))),
            new EntityDataAccessor(),
            new ConfigNormalizer(),
            new DataNormalizer()
        );
    }

    /**
     * @param mixed                       $object
     * @param EntityDefinitionConfig|null $config
     * @param array                       $context
     *
     * @return mixed
     */
    private function normalizeObject($object, EntityDefinitionConfig $config = null, array $context = [])
    {
        if (!isset($context[ApiContext::REQUEST_TYPE])) {
            $context[ApiContext::REQUEST_TYPE] = new RequestType([RequestType::REST]);
        }
        $normalizedObjects = $this->objectNormalizer->normalizeObjects([$object], $config, $context);

        return reset($normalizedObjects);
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

        $result = $this->normalizeObject(
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

        $result = $this->normalizeObject(
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
                        function ($value, $config, $context) {
                            return $value . sprintf(' [%s]', $context['key']);
                        }
                    ]
                ]
            ]
        ];

        $result = $this->normalizeObject(
            $object,
            $this->createConfigObject($config),
            ['key' => 'context value']
        );

        self::assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name [context value]'
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
            'exclusion_policy'          => 'all',
            'fields'                    => [
                'id'   => null,
                'name' => null
            ]
        ];
        $configObject = $this->createConfigObject($config);
        $configObject->setPostSerializeHandler(function (array $item, array $context) {
            $item['name'] .= sprintf('_additional[%s]', $context['key']);
            $item['another'] = 'value';

            return $item;
        });
        $configObject->setPostSerializeCollectionHandler(function (array $items, array $context) {
            foreach ($items as $key => $item) {
                $items[$key]['name'] .= ' + collection';
            }

            return $items;
        });

        $result = $this->normalizeObject(
            $object,
            $configObject,
            ['key' => 'context value']
        );

        self::assertEquals(
            [
                'id'      => 123,
                'name'    => 'test_name_additional[context value] + collection',
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
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);
        $configObject->getField('owner')->getTargetEntity()->setPostSerializeHandler(function (array $item) {
            $item['name'] .= '_additional';

            return $item;
        });

        $result = $this->normalizeObject(
            $this->createProductObject(),
            $configObject
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

        $result = $this->normalizeObject(
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

        $result = $this->normalizeObject(
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

        $result = $this->normalizeObject(
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

        $result = $this->normalizeObject(
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
                                function ($value, $config, $context) {
                                    return $value . sprintf(' [%s]', $context['key']);
                                }
                            ]
                        ],
                        'groups1' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'groups',
                            'fields'           => 'id'
                        ]
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);
        $configObject->getField('owner')->getTargetEntity()->setPostSerializeHandler(
            function (array $item, array $context) {
                $item['name'] .= sprintf('_additional[%s]', $context['key']);

                return $item;
            }
        );

        $result = $this->normalizeObject(
            $this->createProductObject(),
            $configObject,
            ['key' => 'context value']
        );

        self::assertEquals(
            [
                'id'        => 123,
                'category1' => 'category_label',
                'owner'     => [
                    'name'    => 'user_name [context value]_additional[context value]',
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

        $result = $this->normalizeObject(
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

        $result = $this->normalizeObject(
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

        $result = $this->normalizeObject(
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

        $result = $this->normalizeObject(
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
        $this->normalizeObject($object, $configObject);

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

        $result = $this->normalizeObject(
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

        $result = $this->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        self::assertEquals(
            [
                'name' => 'test_name',
                'id'   => 123
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

        $result = $this->normalizeObject(
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

    public function testNormalizeWithComputedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'           => null,
                'computedName' => [
                    'property_path' => '_',
                    'depends_on'    => ['name']
                ],
                'name'         => [
                    'exclude' => true
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);
        $configObject->setPostSerializeHandler(function (array $item) {
            $item['computedName'] = $item['name'] . ' (computed)';

            return $item;
        });

        $result = $this->normalizeObject(
            $this->createProductObject(),
            $configObject
        );

        self::assertEquals(
            [
                'id'           => 123,
                'computedName' => 'product_name (computed)'
            ],
            $result
        );
    }

    public function testNormalizeWithComputedFieldAndSkipPostSerializationForPrimaryObjects()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'           => null,
                'computedName' => [
                    'property_path' => '_',
                    'depends_on'    => ['name']
                ],
                'name'         => [
                    'exclude' => true
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);
        $configObject->setPostSerializeHandler(function (array $item) {
            $item['computedName'] = $item['name'] . ' (computed)';

            return $item;
        });

        $normalizedObjects = $this->objectNormalizer->normalizeObjects(
            [$this->createProductObject()],
            $configObject,
            [ApiContext::REQUEST_TYPE => new RequestType([RequestType::REST])],
            true
        );
        $result = reset($normalizedObjects);

        self::assertEquals(
            [
                'id' => 123
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
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);
        $configObject->getField('owner')->getTargetEntity()->setPostSerializeHandler(function (array $item) {
            $item['computedName'] = ['value' => $item['name'] . ' (computed)'];

            return $item;
        });

        $result = $this->normalizeObject(
            $this->createProductObject(),
            $configObject
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
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);
        $configObject->getField('renamedOwner')->getTargetEntity()->setPostSerializeHandler(function (array $item) {
            $item['renamedComputedName'] = ['renamedValue' => $item['name'] . ' (computed)'];

            return $item;
        });

        $result = $this->normalizeObject(
            $this->createProductObject(),
            $configObject
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

        $result = $this->normalizeObject(
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

    public function testNormalizeObjectForInvalidExclusionPolicyInRelationConfig()
    {
        $this->expectException(\Oro\Bundle\ApiBundle\Exception\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'The exclusion policy must be "all". Object type: "%s".',
            \Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category::class
        ));

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'category' => [
                    'exclusion_policy' => 'none'
                ]
            ]
        ];

        $this->normalizeObject(
            $this->createProductObject(),
            $this->createConfigObject($config)
        );
    }
}
