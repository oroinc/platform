<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\DataContextConfigurator;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\LayoutContext;

class DataContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DataContextConfigurator */
    private $contextConfigurator;

    protected function setUp(): void
    {
        $this->contextConfigurator = new DataContextConfigurator();
    }

    public function testMoveDataToDataCollection()
    {
        $dataKey1 = 'test1';
        $data1 = new \stdClass();
        $dataKey2 = 'test2';
        $data2 = null;

        $context = new LayoutContext();

        $context['data'] = [
            $dataKey1 => [
                'data' => $data1
            ],
            $dataKey2 => [
                'data'       => $data2
            ],
        ];

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context->has('data'));
        $this->assertTrue($context->data()->has($dataKey1));
        $this->assertSame($data1, $context->data()->get($dataKey1));
        $this->assertTrue($context->data()->has($dataKey2));
        $this->assertSame($data2, $context->data()->get($dataKey2));
    }

    public function testEmptyData()
    {
        $context = new LayoutContext();
        $context['data'] = [];
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context->has('data'));
    }

    public function testNoData()
    {
        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    public function testShouldThrowExceptionIfDataNotArray()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Failed to resolve the context variables. Reason: The option "data" does not exist.'
        );

        $context = new LayoutContext();
        $context['data'] = 123;
        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    public function testShouldThrowExceptionIfInvalidDataArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data key "0" must be a string, but "integer" given.');

        $context = new LayoutContext();
        $context['data'] = [123];
        $this->contextConfigurator->configureContext($context);
    }

    public function testShouldThrowExceptionIfNoDataValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data item "test" must have "data" key.');

        $context = new LayoutContext();
        $context['data'] = ['test' => ['identifier' => 'dataId']];
        $this->contextConfigurator->configureContext($context);
    }
}
