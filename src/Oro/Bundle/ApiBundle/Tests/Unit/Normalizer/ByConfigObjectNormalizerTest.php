<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Normalizer\ConfigNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderInterface;
use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityDataAccessor;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\DataTransformer;
use Oro\Component\EntitySerializer\SerializationHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ByConfigObjectNormalizerTest extends OrmTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var AssociationAccessExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $associationAccessExclusionProvider;

    /** @var ObjectNormalizer */
    private $objectNormalizer;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->associationAccessExclusionProvider =
            $this->createMock(AssociationAccessExclusionProviderInterface::class);

        $associationAccessExclusionProviderRegistry =
            $this->createMock(AssociationAccessExclusionProviderRegistry::class);
        $associationAccessExclusionProviderRegistry->expects(self::any())
            ->method('getAssociationAccessExclusionProvider')
            ->with($this->getRequestType())
            ->willReturn($this->associationAccessExclusionProvider);

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);

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
            new DataNormalizer(),
            $this->authorizationChecker,
            $associationAccessExclusionProviderRegistry
        );
    }

    private function getRequestType(): RequestType
    {
        return new RequestType([RequestType::REST]);
    }

    private function normalizeObject(object $object, EntityDefinitionConfig $config = null, array $context = []): array
    {
        if (!isset($context[ApiContext::REQUEST_TYPE])) {
            $context[ApiContext::REQUEST_TYPE] = $this->getRequestType();
        }
        $normalizedObjects = $this->objectNormalizer->normalizeObjects([$object], $config, $context);

        return reset($normalizedObjects);
    }

    private function createProductObject(): Entity\Product
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

    private function createConfigObject(array $config): EntityDefinitionConfig
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

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($object, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($object, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($object, $this->createConfigObject($config), ['key' => 'context value']);

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
            'exclusion_policy' => 'all',
            'fields'           => [
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
        $configObject->setPostSerializeCollectionHandler(function (array $items) {
            foreach ($items as $key => $item) {
                $items[$key]['name'] .= ' + collection';
            }

            return $items;
        });

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($object, $configObject, ['key' => 'context value']);

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

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($product->getCategory())],
                ['VIEW', self::identicalTo($product->getOwner())],
                ['VIEW', self::identicalTo($product->getOwner()->getCategory())]
            )
            ->willReturn(true);

        $result = $this->normalizeObject($product, $configObject);

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

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($product, $this->createConfigObject($config));

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

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($product->getOwner())],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(0))],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(1))],
            )
            ->willReturn(true);

        $result = $this->normalizeObject($product, $this->createConfigObject($config));

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

        $product = $this->createProductObject();
        $product->getOwner()->setGroups($product->getOwner()->getGroups()->toArray());

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($product->getOwner())],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()[0])],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()[1])],
            )
            ->willReturn(true);

        $result = $this->normalizeObject($product, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($product->getOwner()))
            ->willReturn(true);

        $result = $this->normalizeObject($product, $this->createConfigObject($config));

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

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::exactly(4))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($product->getCategory())],
                ['VIEW', self::identicalTo($product->getOwner())],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(0))],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(1))],
            )
            ->willReturn(true);

        $result = $this->normalizeObject($product, $configObject, ['key' => 'context value']);

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

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($product->getOwner()))
            ->willReturn(true);

        $result = $this->normalizeObject($product, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($product->getCategory())],
                ['VIEW', self::identicalTo($product->getOwner())],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(0))],
            )
            ->willReturn(true);

        $result = $this->normalizeObject($product, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($product->getOwner()))
            ->willReturn(true);

        $result = $this->normalizeObject($product, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($product->getCategory())],
                ['VIEW', self::identicalTo($product->getOwner())],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(0))],
            )
            ->willReturn(true);

        $result = $this->normalizeObject($product, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

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

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($object, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($object, $this->createConfigObject($config));

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

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($object, $this->createConfigObject($config));

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

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $result = $this->normalizeObject($product, $configObject);

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

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $normalizedObjects = $this->objectNormalizer->normalizeObjects(
            [$product],
            $configObject,
            [ApiContext::REQUEST_TYPE => $this->getRequestType()],
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

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($product->getOwner()))
            ->willReturn(true);

        $result = $this->normalizeObject($product, $configObject);

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

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($product->getOwner()))
            ->willReturn(true);

        $result = $this->normalizeObject($product, $configObject);

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
        $object->category = new EntityIdentifier('category1', Entity\Category::class);

        $this->associationAccessExclusionProvider->expects(self::once())
            ->method('isIgnoreAssociationAccessCheck')
            ->with(Entity\EntityWithoutGettersAndSetters::class, 'category')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($object->category))
            ->willReturn(true);

        $result = $this->normalizeObject($object, $this->createConfigObject($config));

        self::assertEquals(
            [
                'id'       => 123,
                'category' => [
                    'id'        => 'category1',
                    '__class__' => Entity\Category::class
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectForInvalidExclusionPolicyInRelationConfig()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The exclusion policy must be "all". Object type: "%s".',
            Category::class
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

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::any())
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::any())
            ->method('isGranted')
            ->willReturn(true);

        $this->normalizeObject($product, $this->createConfigObject($config));
    }

    public function testNormalizeObjectWhenNoViewAccessToSameEntities()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'name'      => null,
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
        $configObject = $this->createConfigObject($config);
        $configObject->getField('owner')->getTargetEntity()->setPostSerializeHandler(
            function (array $item, array $context) {
                $item['name'] .= sprintf('_additional[%s]', $context['key']);

                return $item;
            }
        );

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::exactly(3))
            ->method('isIgnoreAssociationAccessCheck')
            ->withConsecutive(
                [Entity\Product::class, 'category'],
                [Entity\Product::class, 'owner'],
                [Entity\User::class, 'groups']
            )
            ->willReturn(false);
        $this->authorizationChecker->expects(self::exactly(5))
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', self::identicalTo($product->getCategory())],
                ['VIEW', self::identicalTo($product->getOwner())],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(0))],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(0))],
                ['VIEW', self::identicalTo($product->getOwner()->getGroups()->get(1))],
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                false,
                false,
                true,
            );

        $result = $this->normalizeObject($product, $configObject, ['key' => 'context value']);

        self::assertEquals(
            [
                'id'        => 123,
                'name'      => 'product_name',
                'category1' => null,
                'owner'     => [
                    'name'    => 'user_name_additional[context value]',
                    'groups1' => [22]
                ]
            ],
            $result
        );
    }

    public function testNormalizeObjectWhenIgnoreViewAccessToSameEntities()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'name'      => null,
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
        $configObject = $this->createConfigObject($config);
        $configObject->getField('owner')->getTargetEntity()->setPostSerializeHandler(
            function (array $item, array $context) {
                $item['name'] .= sprintf('_additional[%s]', $context['key']);

                return $item;
            }
        );

        $product = $this->createProductObject();

        $this->associationAccessExclusionProvider->expects(self::exactly(3))
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturnMap([
                [Entity\Product::class, 'category', false],
                [Entity\Product::class, 'owner', true],
                [Entity\User::class, 'groups', true]
            ]);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($product->getCategory()))
            ->willReturn(false);

        $result = $this->normalizeObject($product, $configObject, ['key' => 'context value']);

        self::assertEquals(
            [
                'id'        => 123,
                'name'      => 'product_name',
                'category1' => null,
                'owner'     => [
                    'name'    => 'user_name_additional[context value]',
                    'groups1' => [11, 22]
                ]
            ],
            $result
        );
    }
}
