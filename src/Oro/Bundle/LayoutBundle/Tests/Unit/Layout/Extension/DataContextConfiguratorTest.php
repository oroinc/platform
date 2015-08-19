<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\DataContextConfigurator;

class DataContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var DataContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new DataContextConfigurator();
    }

    public function testMoveDataToDataCollection()
    {
        $dataKey1 = 'test1';
        $dataId1  = 'dataId1';
        $data1    = new \stdClass();
        $dataKey2 = 'test2';
        $dataId2  = 'dataId2';
        $data2    = null;

        $context = new LayoutContext();

        $context['data'] = [
            $dataKey1 => [
                'id'   => $dataId1,
                'data' => $data1
            ],
            $dataKey2 => [
                'identifier' => $dataId2,
                'data'       => $data2
            ]
        ];

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context->has('data'));
        $this->assertTrue($context->data()->has($dataKey1));
        $this->assertEquals($dataId1, $context->data()->getIdentifier($dataKey1));
        $this->assertSame($data1, $context->data()->get($dataKey1));
        $this->assertTrue($context->data()->has($dataKey2));
        $this->assertEquals($dataId2, $context->data()->getIdentifier($dataKey2));
        $this->assertSame($data2, $context->data()->get($dataKey2));
    }

    public function testEmptyData()
    {
        $context         = new LayoutContext();
        $context['data'] = [];
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context->has('data'));
    }

    public function testNoData()
    {
        $context         = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Failed to resolve the context variables. Reason: The option "data" does not exist.
     */
    public function testShouldThrowExceptionIfDataNotArray()
    {
        $context         = new LayoutContext();
        $context['data'] = 123;
        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The data key "0" must be a string, but "integer" given.
     */
    public function testShouldThrowExceptionIfInvalidDataArray()
    {
        $context         = new LayoutContext();
        $context['data'] = [123];
        $this->contextConfigurator->configureContext($context);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The data item "test" must be an array, but "integer" given.
     */
    public function testShouldThrowExceptionIfDataItemIsNotArray()
    {
        $context         = new LayoutContext();
        $context['data'] = ['test' => 123];
        $this->contextConfigurator->configureContext($context);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The data item "test" must have either "id" or "identifier" key.
     */
    public function testShouldThrowExceptionIfDataItemIsEmptyArray()
    {
        $context         = new LayoutContext();
        $context['data'] = ['test' => []];
        $this->contextConfigurator->configureContext($context);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The data identifier for the data item "test" must be a string, but "integer" given.
     */
    public function testShouldThrowExceptionIfDataIdIsNotString()
    {
        $context         = new LayoutContext();
        $context['data'] = ['test' => ['identifier' => 123]];
        $this->contextConfigurator->configureContext($context);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The data item "test" must have "data" key.
     */
    public function testShouldThrowExceptionIfNoDataValue()
    {
        $context         = new LayoutContext();
        $context['data'] = ['test' => ['identifier' => 'dataId']];
        $this->contextConfigurator->configureContext($context);
    }
}
