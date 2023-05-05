<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Test\Unit\SearchMappingTypeCastingHandlersTestTrait;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Category;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ObjectMapperTest extends \PHPUnit\Framework\TestCase
{
    use SearchMappingTypeCastingHandlersTestTrait;

    private const TEST_ID = 1;
    private const TEST_COUNT = 10;
    private const TEST_PRICE = 150;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var Manufacturer */
    protected $manufacturer;

    /** @var Product */
    protected $product;

    /** @var Category */
    protected $category;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $nameResolver;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var SearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $mappingProvider;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $htmlTagHelper;

    /** @var array */
    private $mappingConfig = [
        Manufacturer::class => [
            'fields' => [
                [
                    'name'        => 'name',
                    'target_type' => 'text'
                ],
                [
                    'name'            => 'products',
                    'relation_type'   => 'one-to-many',
                    'relation_fields' => [
                        [   // test that 'target_fields' is set to ['products']
                            'name'        => 'name',
                            'target_type' => 'text'
                        ],
                        [
                            'name'            => 'categories',
                            'relation_type'   => 'one-to-many',
                            'relation_fields' => [
                                [   // test that 'target_fields' is set to ['categories']
                                    'name'        => 'name',
                                    'target_type' => 'text'
                                ],
                                [
                                    'name'          => 'name',
                                    'target_type'   => 'text',
                                    'target_fields' => ['category']
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'name'            => 'parent',
                    'relation_type'   => 'one-to-many',
                    'relation_fields' => [
                        []
                    ]
                ]
            ]
        ],
        Product::class      => [
            'alias'        => 'test_product',
            'label'        => 'test product',
            'route'        => [
                'name'       => 'test_route',
                'parameters' => [
                    'id' => 'id'
                ]
            ],
            'fields'       => [
                [
                    'name'          => 'name',
                    'target_type'   => 'text',
                    'target_fields' => ['name', 'all_data']
                ],
                [
                    'name'          => 'description',
                    'target_type'   => 'text',
                    'target_fields' => ['description', 'all_data']
                ],
                [
                    'name'          => 'price',
                    'target_type'   => 'decimal',
                    'target_fields' => ['price']
                ],
                [
                    'name'          => 'createDate',
                    'target_type'   => 'datetime',
                    'target_fields' => ['createDate']
                ],
                [   // test that 'target_fields' is set to ['count']
                    'name'        => 'count',
                    'target_type' => 'integer'
                ],
                [
                    'name'            => 'manufacturer',
                    'relation_type'   => 'many-to-one',
                    'relation_fields' => [
                        [
                            'name'          => 'name',
                            'target_type'   => 'text',
                            'target_fields' => ['manufacturer', 'all_data']
                        ]
                    ]
                ]
            ]
        ],
        Category::class     => [
            'fields' => [
                [
                    'name'          => 'name',
                    'target_type'   => 'text',
                    'target_fields' => ['name']
                ],
                [
                    'name'            => 'products',
                    'relation_type'   => 'many-to-many',
                    'relation_fields' => [
                        [   // test that 'target_fields' is set to ['products']
                            'name'        => 'name',
                            'target_type' => 'text'
                        ],
                        [
                            'name'            => 'manufacturer',
                            'relation_type'   => 'one-to-one',
                            'relation_fields' => [
                                [
                                    'name'          => 'name',
                                    'target_type'   => 'text',
                                    'target_fields' => ['manufacturers']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    /** @var array */
    private $categories = ['<p>men</p>', '<p>women</p>'];

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->manufacturer = new Manufacturer();
        $this->manufacturer->setId(42);
        $this->manufacturer->setName('<p>adidas</p>');
        $this->product = new Product();
        $this->product
            ->setId(self::TEST_ID)
            ->setName('<p>test product</p>')
            ->setCount(self::TEST_COUNT)
            ->setPrice(self::TEST_PRICE)
            ->setManufacturer($this->manufacturer)
            ->setDescription('<p>description</p>')
            ->setCreateDate(new \DateTime('2022-12-12 12:13:14', new \DateTimeZone('UTC')));
        foreach ($this->categories as $key => $categoryName) {
            $category = new Category();
            $category
                ->setId($key + 100)
                ->setName($categoryName)
                ->addProduct($this->product);
            $this->product->addCategory($category);
            if (!$this->category) {
                $this->category = $category;
            }
        }
        $this->manufacturer->addProduct($this->product);

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->nameResolver = $this->createMock(EntityNameResolver::class);
        $this->nameResolver->expects($this->any())
            ->method('getName')
            ->with($this->isType('object'), EntityNameProviderInterface::FULL)
            ->willReturnCallback(
                function ($entity) {
                    return $entity->getName();
                }
            );

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->isType('object'))
            ->willReturnCallback(
                function ($entity) {
                    return $entity->getId();
                }
            );

        $configProvider = $this->createMock(MappingConfigurationProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->mappingConfig);
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cache->expects($this->any())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cacheItem->expects($this->any())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem->expects($this->any())
            ->method('set')
            ->willReturn($cacheItem);
        $this->mappingProvider = new SearchMappingProvider(
            $this->dispatcher,
            $configProvider,
            $cache,
            'test',
            'test',
            'test'
        );

        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->htmlTagHelper->expects($this->any())
            ->method('stripTags')
            ->willReturnCallback(function ($value) {
                return trim(strip_tags($value));
            });
        $this->htmlTagHelper->expects($this->any())
            ->method('stripLongWords')
            ->willReturnCallback(function ($value) {
                $words = preg_split('/\s+/', $value);
                $words = array_filter(
                    $words,
                    function ($item) {
                        return \strlen($item) <= HtmlTagHelper::MAX_STRING_LENGTH;
                    }
                );

                return implode(' ', $words);
            });

        $this->mapper = new ObjectMapper(
            $this->mappingProvider,
            PropertyAccess::createPropertyAccessor(),
            $this->getTypeCastingHandlerRegistry(),
            $this->nameResolver,
            $this->doctrineHelper,
            $this->dispatcher,
            $this->htmlTagHelper,
            new DateTimeFormatter()
        );
    }

    /**
     * Tests the following features:
     * * different types of scalar fields
     * * scalar field without 'target_fields' mappings
     * * mapping to several target fields
     * * many-to-one relation
     */
    public function testMapObjectForProduct()
    {
        $productName = $this->product->getName();
        $productDescription = $this->product->getDescription();
        $manufacturerName = $this->product->getManufacturer()->getName();
        $allTextData = sprintf('%s %s %s', $productName, $productDescription, $manufacturerName);

        $expectedMapping = [
            'text'    => $this->clearTextData([
                Indexer::NAME_FIELD          => $productName,
                'name'                       => $productName,
                'description'                => $productDescription,
                'manufacturer'               => $manufacturerName,
                'all_data'                   => $allTextData,
                Indexer::TEXT_ALL_DATA_FIELD => $allTextData,
            ]),
            'decimal' => [
                'price' => $this->product->getPrice(),
            ],
            'integer' => [
                'system_entity_id' => self::TEST_ID,
                'count'            => $this->product->getCount(),
            ],
            'datetime' => [
                'createDate' => $this->product->getCreateDate()
            ]
        ];

        $this->assertEquals($expectedMapping, $this->mapper->mapObject($this->product));
    }

    public function testAllTextLimitation()
    {
        // create a product name exceeding the 256 length limitation
        $productName = 'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234'
            . 'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234'
            . 'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234'
            . 'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234'
            . 'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234'
            . ' ';
        $expectedProductName = 'zUWidBOhT9IzqNyPhYvchY QJfPB2teh0ukQ';
        $productName .= $expectedProductName;
        $productDescription = 'description';
        $manufacturerName = $this->product->getManufacturer()->getName();

        $allData = sprintf('%s %s %s', $productName, $productDescription, $manufacturerName);
        $allTextData = sprintf('%s %s %s', $expectedProductName, $productDescription, $manufacturerName);

        $expectedMapping = [
            'text'    => $this->clearTextData(
                [
                    Indexer::NAME_FIELD          => $productName,
                    'name'                       => $productName,
                    'description'                => $productDescription,
                    'manufacturer'               => $manufacturerName,
                    'all_data'                   => $allData,
                    Indexer::TEXT_ALL_DATA_FIELD => $allTextData
                ]
            ),
            'decimal' => [
                'price' => $this->product->getPrice()
            ],
            'integer' => [
                'system_entity_id' => self::TEST_ID,
                'count'            => $this->product->getCount(),
            ],
            'datetime' => [
                'createDate' => $this->product->getCreateDate()
            ]
        ];

        $this->product
            ->setName($productName)
            ->setDescription($productDescription);

        $this->assertEquals($expectedMapping, $this->mapper->mapObject($this->product));
    }

    public function testNullFieldValues()
    {
        $this->product->setCount(null);
        $this->product->setPrice(null);
        $this->product->setName(null);

        $allTextData = sprintf(
            '%s %s',
            $this->product->getDescription(),
            $this->product->getManufacturer()->getName()
        );
        $expectedMapping = [
            'text' => $this->clearTextData(
                [
                    Indexer::NAME_FIELD          => '',
                    'description'                => $this->product->getDescription(),
                    'manufacturer'               => $this->product->getManufacturer()->getName(),
                    'all_data'                   => $allTextData,
                    Indexer::TEXT_ALL_DATA_FIELD => $allTextData
                ]
            ),
            'integer' => [
                'system_entity_id' => self::TEST_ID,
            ],
            'datetime' => [
                'createDate' => $this->product->getCreateDate()
            ]
        ];

        $this->assertEquals($expectedMapping, $this->mapper->mapObject($this->product));
    }

    public function testZeroNumberAndEmptyStringFieldValues()
    {
        $this->product->setCount(0);
        $this->product->setPrice(0.0);
        $this->product->setName('');

        $allTextData = sprintf(
            '%s %s',
            $this->product->getDescription(),
            $this->product->getManufacturer()->getName()
        );
        $expectedMapping = [
            'text'    => $this->clearTextData(
                [
                    Indexer::NAME_FIELD          => '',
                    'description'                => $this->product->getDescription(),
                    'manufacturer'               => $this->product->getManufacturer()->getName(),
                    'all_data'                   => $allTextData,
                    Indexer::TEXT_ALL_DATA_FIELD => $allTextData
                ]
            ),
            'decimal' => [
                'price' => 0.0
            ],
            'integer' => [
                'system_entity_id' => self::TEST_ID,
                'count'            => 0,
            ],
            'datetime' => [
                'createDate' => $this->product->getCreateDate()
            ]
        ];

        $this->assertEquals($expectedMapping, $this->mapper->mapObject($this->product));
    }

    /**
     * Tests the following features:
     * * one-to-many relation
     * * field without 'target_fields' mappings
     * * relation without 'target_fields' mappings
     * * nested one-to-many relation
     * * nested one-to-many relation without 'target_fields' mappings
     */
    public function testMapObjectForManufacturer()
    {
        $productName = $this->product->getName();

        $expectedMapping = [
            'text' => $this->clearTextData([
                Indexer::NAME_FIELD          => $this->manufacturer->getName(),
                'name'                       => '<p>adidas</p>',
                'products'                   => $productName,
                'categories'                 => implode(' ', $this->categories),
                'category'                   => implode(' ', $this->categories),
                Indexer::TEXT_ALL_DATA_FIELD => sprintf(
                    '%s %s %s',
                    $this->manufacturer->getName(),
                    $productName,
                    implode(' ', $this->categories)
                )
            ]),
            'integer' => [
                'system_entity_id' => $this->manufacturer->getId(),
            ],
        ];
        $this->assertEquals($expectedMapping, $this->mapper->mapObject($this->manufacturer));
    }

    /**
     * Tests the following features:
     * * many-to-many relation
     * * nested many-to-many relation
     * * nested many-to-many relation without 'target_fields' mappings
     */
    public function testMapObjectForCategory()
    {
        $categoryName = $this->category->getName();
        $productName = $this->product->getName();
        $manufacturerName = $this->manufacturer->getName();

        $expectedMapping = [
            'text' => $this->clearTextData([
                Indexer::NAME_FIELD          => $categoryName,
                'name'                       => $categoryName,
                'products'                   => $productName,
                'manufacturers'              => $manufacturerName,
                Indexer::TEXT_ALL_DATA_FIELD => $categoryName . ' ' . $productName . ' ' . $manufacturerName
            ]),
            'integer' => [
                'system_entity_id' => $this->category->getId(),
            ],
        ];
        $this->assertEquals($expectedMapping, $this->mapper->mapObject($this->category));
    }

    public function testMapObjectForNull()
    {
        $this->assertEquals([], $this->mapper->mapObject(null));
    }

    public function testMapObjectForNullFieldsAndManyToOneRelation()
    {
        $product = new Product();
        $product->setId(42);
        $product->setName('test product');

        $expectedMapping = [
            'text' => [
                Indexer::NAME_FIELD          => $product->getName(),
                'name'                       => $product->getName(),
                'all_data'                   => $product->getName(),
                Indexer::TEXT_ALL_DATA_FIELD => $product->getName()
            ],
            'integer' => [
                'system_entity_id' => $product->getId(),
            ],
        ];
        $this->assertEquals($expectedMapping, $this->mapper->mapObject($product));
    }

    public function testMapObjectForNullManyToManyRelation()
    {
        $category = new Category();
        $category->setId(777);
        $category->setName('men');

        $expectedMapping = [
            'text' => [
                Indexer::NAME_FIELD          => $category->getName(),
                'name'                       => $category->getName(),
                Indexer::TEXT_ALL_DATA_FIELD => $category->getName()
            ],
            'integer' => [
                'system_entity_id' => $category->getId(),
            ],
        ];
        $this->assertEquals($expectedMapping, $this->mapper->mapObject($category));
    }

    /**
     * Tests the following features:
     * * all_text virtual field must contain actual data even if fields have been changed in listener
     * * data that has been provided for all_text field in listeners is not lost during generation of "all_text" field
     */
    public function testMapObjectWithListener()
    {
        $product = new Product();
        $product->setName('test product');
        $product->setDescription('short description');

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(SearchMappingCollectEvent::class)],
                [$this->isInstanceOf(PrepareEntityMapEvent::class), PrepareEntityMapEvent::EVENT_NAME]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (SearchMappingCollectEvent $event) {
                    return $event;
                }),
                new ReturnCallback(function (PrepareEntityMapEvent $event) {
                    $data = $event->getData();
                    $data[Query::TYPE_TEXT]['name'] = 'test product with changed title';
                    $data[Query::TYPE_TEXT]['all_text'] = 'custom text';
                    $event->setData($data);

                    return $event;
                })
            );

        $mapping = $this->mapper->mapObject($product);

        $this->assertEquals(
            'custom text test product with changed title short description',
            $mapping[Query::TYPE_TEXT][Indexer::TEXT_ALL_DATA_FIELD]
        );
    }

    public function testGetEntitiesListAliases()
    {
        $data = $this->mapper->getEntitiesListAliases();

        $this->assertEquals('test_product', $data[Product::class]);
    }

    public function testGetMappingConfig()
    {
        $this->assertEquals($this->mappingConfig, $this->mapper->getMappingConfig());
    }

    public function testGetEntityMapParameter()
    {
        $this->assertEquals(
            'test_product',
            $this->mapper->getEntityMapParameter(Product::class, 'alias')
        );

        $this->assertEquals(
            false,
            $this->mapper->getEntityMapParameter(Product::class, 'non exists parameter')
        );
    }

    public function testGetEntities()
    {
        $entities = $this->mapper->getEntities();
        $this->assertEquals(Product::class, $entities[1]);
    }

    public function testNonExistsConfig()
    {
        $this->assertEquals([], $this->mapper->getEntityConfig('non exists entity'));
    }

    public function testSelectedData()
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSelectDataFields')
            ->willReturn([
                'text.sku'             => 'sku',
                'text.defaultName'     => 'defaultName',
                'integer.integerField' => 'integerValue',
                'decimal.decimalField' => 'decimalValue',
                'datetime.updated'     => 'updatedAt',
                'notExistingField'     => 'notExistingField'
            ]);

        $item = [
            'item'         => [
                'id'       => 50,
                'recordId' => 29
            ],
            'sku'          => '2GH80',
            'defaultName'  => 'Example Headlamp',
            'integerField' => '42',
            'decimalField' => '12.34',
            'updated'      => new \DateTime('2022-12-12 12:13:14', new \DateTimeZone('UTC')),
        ];

        $result = $this->mapper->mapSelectedData($query, $item);

        $this->assertSame(
            [
                'sku'              => '2GH80',
                'defaultName'      => 'Example Headlamp',
                'integerValue'     => 42,
                'decimalValue'     => 12.34,
                'updatedAt'        => '2022-12-12 12:13:14',
                'notExistingField' => '',
            ],
            $result
        );
    }

    public function testBuildAllDataField()
    {
        $allData = '';

        $allData = $this->mapper->buildAllDataField($allData, 'first second');
        $this->assertEquals('first second', $allData);

        $allData = $this->mapper->buildAllDataField($allData, 'second third');
        $this->assertEquals('first second third', $allData);
    }

    protected function clearTextData(array $fields): array
    {
        foreach ($fields as $name => &$value) {
            if ($name !== Indexer::TEXT_ALL_DATA_FIELD) {
                continue;
            }

            $value = str_replace(['<p>', '</p>'], ['', ''], $value);
        }

        return $fields;
    }
}
