<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;

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

    public function testGetConfig()
    {
        $config = ['key' => 'value'];

        $configBag = new ConfigBag($config, $this->container);

        $this->assertEquals($config, $configBag->getConfig());
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
        $transformer = $this->getMock('Oro\Bundle\ConfigBundle\Config\DataTransformerInterface');
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

    /**
     * @expectedException \Oro\Bundle\ConfigBundle\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Bundle\ConfigBundle\Config\DataTransformerInterface"
     */
    public function testGetDataTransformerWithUnexpectedType()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('test.service')
            ->will($this->returnValue(new \stdClass()));
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
