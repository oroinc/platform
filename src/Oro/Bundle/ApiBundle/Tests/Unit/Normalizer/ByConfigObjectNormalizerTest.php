<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\EntityDataTransformer;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Normalizer\DateTimeNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity as Object;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class ByConfigObjectNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectNormalizer */
    protected $objectNormalizer;

    protected function setUp()
    {
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn(null);

        $normalizers = new ObjectNormalizerRegistry();
        $this->objectNormalizer = new ObjectNormalizer(
            $normalizers,
            new DoctrineHelper($doctrine),
            new EntityDataAccessor(),
            new EntityDataTransformer($this->getMock('Symfony\Component\DependencyInjection\ContainerInterface'))
        );

        $normalizers->addNormalizer(
            new DateTimeNormalizer()
        );
    }

    public function testNormalizeSimpleObject()
    {
        $object = new Object\Group();
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

        $this->assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeSimpleObjectWithRenaming()
    {
        $object = new Object\Group();
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

        $this->assertEquals(
            [
                'id'    => 123,
                'name1' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeSimpleObjectWithDataTransformers()
    {
        $object = new Object\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'name' => [
                    'data_transformer' => [
                        function ($class, $property, $value, $config) {
                            return $value . sprintf(' (%s::%s)', $class, $property);
                        }
                    ]
                ]
            ]
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        $this->assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name (Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group::name)'
            ],
            $result
        );
    }

    public function testNormalizeSimpleObjectWithPostSerialize()
    {
        $object = new Object\Group();
        $object->setId(123);
        $object->setName('test_name');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'name' => null
            ],
            'post_serialize'   => function (array $item) {
                $item['name'] .= '_additional';

                return $item;
            }
        ];

        $result = $this->objectNormalizer->normalizeObject(
            $object,
            $this->createConfigObject($config)
        );

        $this->assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name_additional'
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
                        'name'    => null,
                        'groups1' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'groups',
                            'fields'           => 'id'
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

        $this->assertEquals(
            [
                'id'        => 123,
                'category1' => 'category_label',
                'owner'     => [
                    'name'    => 'user_name_additional',
                    'groups1' => [11, 22]
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullToOneRelations()
    {
        $product = new Object\Product();
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

        $this->assertEquals(
            [
                'id'        => 123,
                'category1' => null,
                'owner'     => null
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullToManyRelations()
    {
        $product = new Object\Product();
        $product->setId(123);
        $product->setName('product_name');
        $owner = new Object\User();
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

        $this->assertEquals(
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
                                function ($class, $property, $value, $config) {
                                    return $value . sprintf(' (%s::%s)', $class, $property);
                                }
                            ]
                        ],
                        'groups1' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'groups',
                            'fields'           => 'id'
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

        $this->assertEquals(
            [
                'id'        => 123,
                'category1' => 'category_label',
                'owner'     => [
                    'name'    => 'user_name (Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User::name)_additional',
                    'groups1' => [11, 22]
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithCollapsedNullTableInheritanceRelations()
    {
        $product = new Object\Product();
        $product->setId(123);
        $product->setName('product_name');
        $owner = new Object\User();
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

        $this->assertEquals(
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
        $product = new Object\Product();
        $product->setId(123);
        $product->setName('product_name');
        $product->setCategory(new Object\Category('category_name'));
        $owner = new Object\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $owner->addProduct($product);
        $group = new Object\Group();
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

        $this->assertEquals(
            [
                'id'       => 123,
                'category' => [
                    'name'      => 'category_name',
                    '__class__' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                ],
                'owner'    => [
                    'name'   => 'user_name',
                    'groups' => [
                        [
                            'id'        => 789,
                            '__class__' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group',
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullTableInheritanceRelations()
    {
        $product = new Object\Product();
        $product->setId(123);
        $product->setName('product_name');
        $owner = new Object\User();
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

        $this->assertEquals(
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
        $product = new Object\Product();
        $product->setId(123);
        $product->setName('product_name');
        $product->setCategory(new Object\Category('category_name'));
        $owner = new Object\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $owner->addProduct($product);
        $group = new Object\Group();
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

        $this->assertEquals(
            [
                'id'       => 123,
                'category' => [
                    'name'      => 'category_name',
                    '__class__' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                ],
                'owner'    => [
                    'name'   => 'user_name',
                    'groups' => [
                        [
                            'id'        => 789,
                            '__class__' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group',
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @return Object\Product
     */
    protected function createProductObject()
    {
        $product = new Object\Product();
        $product->setId(123);
        $product->setName('product_name');
        $product->setUpdatedAt(new \DateTime('2015-12-01 10:20:30', new \DateTimeZone('UTC')));

        $category = new Object\Category('category_name');
        $category->setLabel('category_label');
        $product->setCategory($category);

        $owner = new Object\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $ownerCategory = new Object\Category('owner_category_name');
        $ownerCategory->setLabel('owner_category_label');
        $owner->setCategory($ownerCategory);
        $ownerGroup1 = new Object\Group();
        $ownerGroup1->setId(11);
        $ownerGroup1->setName('owner_group1');
        $owner->addGroup($ownerGroup1);
        $ownerGroup2 = new Object\Group();
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
    protected function createConfigObject(array $config)
    {
        $configExtensionRegistry = new ConfigExtensionRegistry();
        $configExtensionRegistry->addExtension(new FiltersConfigExtension());
        $configExtensionRegistry->addExtension(new SortersConfigExtension());

        $loaderFactory = new ConfigLoaderFactory($configExtensionRegistry);

        return $loaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
    }
}
