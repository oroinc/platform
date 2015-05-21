<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ConfigBagTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    public function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->container);
    }

    /**
     * @dataProvider propertiesDataProvider
     *
     * @param string $property
     * @param array  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new ConfigBag([], $this->container);

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return [
            [
                'config',
                [
                    'key' => 'value'
                ],
            ]
        ];
    }

    public function testGetNullDataTransformer()
    {
        $config    = [
            'fields' => [
                'test_key' => [
                    'data_transformer' => 'test.service'
                ]
            ]
        ];
        $configBag = new ConfigBag($config, $this->container);

        $this->assertEquals(null, $configBag->getDataTransformer('test_key'));
    }

    public function testGetDataTransformer()
    {
        $transformer = $this->getMock('Oro\Bundle\ConfigBundle\Model\Data\Transformer\TransformerInterface');
        $this->container->expects($this->once())
            ->method('get')
            ->with('test.service')
            ->will($this->returnValue($transformer));

        $config    = [
            'fields' => [
                'test_key' => [
                    'data_transformer' => 'test.service'
                ]
            ]
        ];
        $configBag = new ConfigBag($config, $this->container);

        $this->assertSame($transformer, $configBag->getDataTransformer('test_key'));
    }

    public function testGetDataTransformerWithUnexpectedType()
    {
        $this->setExpectedException('Symfony\Component\Validator\Exception\UnexpectedTypeException');

        $transformer = $this->getMock('Oro\Bundle\ConfigBundle\Tests\Unit\Model\Data\Transformer');
        $this->container->expects($this->once())
            ->method('get')
            ->with('test.service')
            ->will($this->returnValue($transformer));
        $config    = [
            'fields' => [
                'test_key' => [
                    'data_transformer' => 'test.service'
                ]
            ]
        ];
        $configBag = new ConfigBag($config, $this->container);

        $configBag->getDataTransformer('test_key');
    }

    /**
     * @dataProvider fieldsRootDataProvider
     *
     * @param array  $config
     * @param string $node
     * @param mixed  $expectedResult
     */
    public function testGetFieldsRoot($config, $node, $expectedResult)
    {
        $configBag = new ConfigBag($config, $this->container);
        $this->assertEquals($expectedResult, $configBag->getFieldsRoot($node));
    }

    public function fieldsRootDataProvider()
    {
        return [
            'fields root does not exists' => [
                'config'         => [],
                'node'           => 'test',
                'expectedResult' => false
            ],
            'fields root exists'          => [
                'config'         => [
                    ProcessorDecorator::FIELDS_ROOT => [
                        'test' => 'value'
                    ]
                ],
                'node'           => 'test',
                'expectedResult' => 'value'
            ]
        ];
    }

    /**
     * @dataProvider treeRootDataProvider
     *
     * @param array  $config
     * @param string $treeName
     * @param mixed  $expectedResult
     */
    public function testGetTreeRoot($config, $treeName, $expectedResult)
    {
        $configBag = new ConfigBag($config, $this->container);

        $this->assertEquals($expectedResult, $configBag->getTreeRoot($treeName));
    }

    public function treeRootDataProvider()
    {
        return [
            'tree root does not exists' => [
                'config'         => [],
                'treeName'       => 'test',
                'expectedResult' => false
            ],
            'tree root exists'          => [
                'config'         => [
                    ProcessorDecorator::TREE_ROOT => [
                        'test' => 'value'
                    ]
                ],
                'treeName'       => 'test',
                'expectedResult' => 'value'
            ]
        ];
    }

    /**
     * @dataProvider groupsNodeDataProvider
     *
     * @param array  $config
     * @param string $name
     * @param mixed  $expectedResult
     */
    public function testGetGroupsNode($config, $name, $expectedResult)
    {
        $configBag = new ConfigBag($config, $this->container);
        $this->assertEquals($expectedResult, $configBag->getGroupsNode($name));
    }

    public function groupsNodeDataProvider()
    {
        return [
            'groups node does not exists' => [
                'config'         => [],
                'name'           => 'test',
                'expectedResult' => false
            ],
            'groups node exists'          => [
                'config'         => [
                    ProcessorDecorator::GROUPS_NODE => [
                        'test' => 'value'
                    ]
                ],
                'name'           => 'test',
                'expectedResult' => 'value'
            ]
        ];
    }
}
