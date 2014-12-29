<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer;

class ObjectMapperTest extends \PHPUnit_Framework_TestCase
{
    const TEST_COUNT = 10;
    const TEST_PRICE = 150;

    const ENTITY_MANUFACTURER = 'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer';
    const ENTITY_PRODUCT = 'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product';

    /**
     * @var ObjectMapper
     */
    private $mapper;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $mappingConfig = array(
        self::ENTITY_MANUFACTURER => array(
            'fields' => array(
                array(
                    'name'            => 'products',
                    'relation_type'   => 'one-to-many',
                    'relation_fields' => array(
                        array(
                            'name'        => 'name',
                            'target_type' => 'text',
                        )
                    )
                ),
                array(
                    'name'            => 'parent',
                    'relation_type'   => 'one-to-many',
                    'relation_fields' => array(
                        array()
                    )
                )
            )
        ),
        self::ENTITY_PRODUCT => array(
            'alias'            => 'test_product',
            'label'            => 'test product',
            'title_fields'     => array('name'),
            'route'            => array(
                'name'       => 'test_route',
                'parameters' => array(
                    'id' => 'id'
                )
            ),
            'fields'           => array(
                array(
                    'name'          => 'name',
                    'target_type'   => 'text',
                    'target_fields' => array(
                        'name',
                        'all_data'
                    )
                ),
                array(
                    'name'          => 'description',
                    'target_type'   => 'text',
                    'target_fields' => array(
                        'description',
                        'all_data'
                    )
                ),
                array(
                    'name'          => 'price',
                    'target_type'   => 'decimal',
                    'target_fields' => array('price')
                ),
                array(
                    'name'        => 'count',
                    'target_type' => 'integer',
                ),
                array(
                    'name'            => 'manufacturer',
                    'relation_type'   => 'one-to-one',
                    'relation_fields' => array(
                        array(
                            'name'          => 'name',
                            'target_type'   => 'text',
                            'target_fields' => array(
                                'manufacturer',
                                'all_data'
                            )
                        )
                    )
                ),
            ),
        )
    );

    protected function setUp()
    {
        $this->container = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');

        $manufacturer = new Manufacturer();
        $manufacturer->setName('adidas');

        $this->product = new Product();
        $this->product->setName('test product')
            ->setCount(self::TEST_COUNT)
            ->setPrice(self::TEST_PRICE)
            ->setManufacturer($manufacturer)
            ->setDescription('description')
            ->setCreateDate(new \DateTime());

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->mapper = new ObjectMapper($this->dispatcher, $this->mappingConfig);
    }

    public function testMapObject()
    {
        $productName = $this->product->getName();
        $productDescription = $this->product->getDescription();
        $manufacturerName = $this->product->getManufacturer()->getName();
        $allTextData = sprintf('%s %s %s', $productName, $productDescription, $manufacturerName);

        $productMapping = array(
            'text' => array(
                'name' => $productName,
                'description' => $productDescription,
                'manufacturer' => $manufacturerName,
                'all_data' => $allTextData,
                Indexer::TEXT_ALL_DATA_FIELD => $allTextData,
            ),
            'decimal' => array(
                'price' => $this->product->getPrice(),
            ),
            'integer' => array(
                'count' => $this->product->getCount(),
            )
        );
        $this->assertEquals($productMapping, $this->mapper->mapObject($this->product));

        $manufacturer = new Manufacturer();
        $manufacturer->setName('reebok');
        $manufacturer->addProduct($this->product);

        $manufacturerMapping = array(
            'text' => array(
                'products' => $productName,
                Indexer::TEXT_ALL_DATA_FIELD => $productName
            )
        );
        $this->assertEquals($manufacturerMapping, $this->mapper->mapObject($manufacturer));
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
}
