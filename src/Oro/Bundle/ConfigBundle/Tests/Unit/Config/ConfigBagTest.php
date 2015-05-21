<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;

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

        $this->assertEquals($configBag->getDataTransformer('test_key'), null);
    }

    public function testGetDataTransformer()
    {
        $transformer = $this->getMock('Oro\Bundle\ConfigBundle\Model\Data\Transformer\TransformerInterface');
        $this->container->expects($this->once())
                        ->method('get')
                        ->with('test.service')
                        ->will($this->returnValue($transformer));
        $config = [
            'fields' => [
                'test_key' => [
                    'data_transformer' => 'test.service'
                ]
            ]
        ];
        $configBag = new ConfigBag($config, $this->container);

        $this->assertSame($configBag->getDataTransformer('test_key'), $transformer);
    }

    public function testGetDataTransformerWithUnexpectedType()
    {
        $this->setExpectedException('Symfony\Component\Validator\Exception\UnexpectedTypeException');

        $transformer = $this->getMock('Oro\Bundle\ConfigBundle\Tests\Unit\Model\Data\Transformer');
        $this->container->expects($this->once())
            ->method('get')
            ->with('test.service')
            ->will($this->returnValue($transformer));
        $config = [
            'fields' => [
                'test_key' => [
                    'data_transformer' => 'test.service'
                ]
            ]
        ];
        $configBag = new ConfigBag($config, $this->container);

        $configBag->getDataTransformer('test_key');
    }
}
