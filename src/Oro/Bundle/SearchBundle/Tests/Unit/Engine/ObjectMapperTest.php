<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Category;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer;

class ObjectMapperTest extends \PHPUnit_Framework_TestCase
{
    const TEST_COUNT = 10;
    const TEST_PRICE = 150;

    const ENTITY_MANUFACTURER = 'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer';
    const ENTITY_PRODUCT      = 'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product';
    const ENTITY_CATEGORY     = 'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Category';

    /** @var ObjectMapper */
    private $mapper;

    /** @var Manufacturer */
    private $manufacturer;

    /** @var Product */
    private $product;

    /** @var Category */
    private $category;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $dispatcher;

    /** @var array */
    private $mappingConfig = [
        self::ENTITY_MANUFACTURER => [
            'fields' => [
                [
                    'name'            => 'products',
                    'relation_type'   => 'one-to-many',
                    'relation_fields' => [
                        [   // test that 'target_fields' is set to ['products']
                            'name'        => 'name',
                            'target_type' => 'text',
                        ],
                        [
                            'name'            => 'categories',
                            'relation_type'   => 'one-to-many',
                            'relation_fields' => [
                                [   // test that 'target_fields' is set to ['categories']
                                    'name'        => 'name',
                                    'target_type' => 'text',
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
        self::ENTITY_PRODUCT      => [
            'alias'        => 'test_product',
            'label'        => 'test product',
            'title_fields' => ['name'],
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
                [   // test that 'target_fields' is set to ['count']
                    'name'        => 'count',
                    'target_type' => 'integer',
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
            ],
        ],
        self::ENTITY_CATEGORY     => [
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
                            'target_type' => 'text',
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
        ],
    ];

    /** @var array */
    private $categories = ['men', 'women'];

    protected function setUp()
    {
        $this->manufacturer = new Manufacturer();
        $this->manufacturer->setName('adidas');
        $this->product = new Product();
        $this->product
            ->setName('test product')
            ->setCount(self::TEST_COUNT)
            ->setPrice(self::TEST_PRICE)
            ->setManufacturer($this->manufacturer)
            ->setDescription('description')
            ->setCreateDate(new \DateTime());
        foreach ($this->categories as $categoryName) {
            $category = new Category();
            $category
                ->setName($categoryName)
                ->addProduct($this->product);
            $this->product->addCategory($category);
            if (!$this->category) {
                $this->category = $category;
            }
        }
        $this->manufacturer->addProduct($this->product);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();
        $mapperProvider  = new SearchMappingProvider($eventDispatcher);
        $mapperProvider->setMappingConfig($this->mappingConfig);

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->mapper = new ObjectMapper($this->dispatcher, $this->mappingConfig);
        $this->mapper->setMappingProvider($mapperProvider);
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
        $productName        = $this->product->getName();
        $productDescription = $this->product->getDescription();
        $manufacturerName   = $this->product->getManufacturer()->getName();
        $allTextData        = sprintf('%s %s %s', $productName, $productDescription, $manufacturerName);

        $expectedMapping = [
            'text'    => [
                'name'                       => $productName,
                'description'                => $productDescription,
                'manufacturer'               => $manufacturerName,
                'all_data'                   => $allTextData,
                Indexer::TEXT_ALL_DATA_FIELD => $allTextData,
            ],
            'decimal' => [
                'price' => $this->product->getPrice(),
            ],
            'integer' => [
                'count' => $this->product->getCount(),
            ]
        ];
        $this->assertEquals($expectedMapping, $this->mapper->mapObject($this->product));
    }

    /**
     * Tests the following features:
     * * one-to-many relation
     * * relation without 'target_fields' mappings
     * * nested one-to-many relation
     * * nested one-to-many relation without 'target_fields' mappings
     */
    public function testMapObjectForManufacturer()
    {
        $productName = $this->product->getName();

        $expectedMapping = [
            'text' => [
                'products'                   => $productName,
                'categories'                 => implode(' ', $this->categories),
                'category'                   => implode(' ', $this->categories),
                Indexer::TEXT_ALL_DATA_FIELD => $productName . ' ' . implode(' ', $this->categories)
            ]
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
        $categoryName     = $this->category->getName();
        $productName      = $this->product->getName();
        $manufacturerName = $this->manufacturer->getName();

        $expectedMapping = [
            'text' => [
                'name'                       => $categoryName,
                'products'                   => $productName,
                'manufacturers'              => $manufacturerName,
                Indexer::TEXT_ALL_DATA_FIELD => $categoryName . ' ' . $productName . ' ' . $manufacturerName
            ]
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
        $product->setName('test product');

        $expectedMapping = [
            'text' => [
                'name'                       => $product->getName(),
                'all_data'                   => $product->getName(),
                Indexer::TEXT_ALL_DATA_FIELD => $product->getName()
            ]
        ];
        $this->assertEquals($expectedMapping, $this->mapper->mapObject($product));
    }

    public function testMapObjectForNullManyToManyRelation()
    {
        $category = new Category();
        $category->setName('men');

        $expectedMapping = [
            'text' => [
                'name'                       => $category->getName(),
                Indexer::TEXT_ALL_DATA_FIELD => $category->getName()
            ]
        ];
        $this->assertEquals($expectedMapping, $this->mapper->mapObject($category));
    }

    public function testGetEntitiesListAliases()
    {
        $data = $this->mapper->getEntitiesListAliases();

        $this->assertEquals('test_product', $data[self::ENTITY_PRODUCT]);
    }

    public function testGetMappingConfig()
    {
        $mapping = $this->mappingConfig;

        $this->assertEquals($mapping, $this->mapper->getMappingConfig());
    }

    public function testGetEntityMapParameter()
    {
        $this->assertEquals(
            'test_product',
            $this->mapper->getEntityMapParameter(self::ENTITY_PRODUCT, 'alias')
        );

        $this->assertEquals(
            false,
            $this->mapper->getEntityMapParameter(self::ENTITY_PRODUCT, 'non exists parameter')
        );
    }

    public function testGetEntities()
    {
        $entities = $this->mapper->getEntities();
        $this->assertEquals(self::ENTITY_PRODUCT, $entities[1]);
    }

    public function testNonExistsConfig()
    {
        $this->assertEquals(false, $this->mapper->getEntityConfig('non exists entity'));
    }

    public function testSelectedData()
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query->expects($this->once())
            ->method('getSelect')
            ->willReturn([
                'text.sku',
                'text.defaultName',
                'notExistingField'
            ]);

        $item = [
            'item' => [
                'id'       => 50,
                'recordId' => 29
            ],
            'sku' => '2GH80',
            'defaultName' => 'Example Headlamp'
        ];

        $result = $this->mapper->mapSelectedData($query, $item);

        $this->assertEquals(
            [
                'sku' => '2GH80',
                'defaultName' => 'Example Headlamp',
                'notExistingField' => ''
            ],
            $result
        );
    }
}
