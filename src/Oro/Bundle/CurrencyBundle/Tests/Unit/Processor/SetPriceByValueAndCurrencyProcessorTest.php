<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\SettablePriceAwareInterface;
use Oro\Bundle\CurrencyBundle\Processor\SetPriceByValueAndCurrencyProcessor;

class SetPriceByValueAndCurrencyProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var SetPriceByValueAndCurrencyProcessor */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->processor = new SetPriceByValueAndCurrencyProcessor();
    }

    public function testProcessWithNotFormContext()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->never())->method($this->anything());

        $this->processor->process($context);
    }

    public function testProcessWithoutRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn([]);
        $context->expects($this->never())->method('setRequestData');

        $this->processor->process($context);
    }

    public function testProcessWithoutRequestSettablePriceAwareItem()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')
            ->willReturn(['currency' => 'USD', 'value' => 10]);
        $context->expects($this->any())->method('getResult')->willReturn(null);
        $context->expects($this->never())->method('setRequestData');

        $this->processor->process($context);
    }

    /**
     * @dataProvider requestDataProvider
     *
     * @param array $requestData
     * @param SettablePriceAwareInterface $expectedItem
     */
    public function testProcess(array $requestData, SettablePriceAwareInterface $expectedItem)
    {
        $actualItem = $this->createSettablePriceAwareMock();

        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn($requestData);
        $context->expects($this->any())->method('getResult')->willReturn($actualItem);

        $this->processor->process($context);
        $this->assertEquals($expectedItem, $actualItem);
    }

    /**
     * @return \Generator
     */
    public function requestDataProvider()
    {
        yield 'empty request' => [
            'requestData' => [],
            'expectedItem' => $this->createSettablePriceAwareMock(),
        ];

        yield 'empty currency' => [
            'requestData' => ['value' => 10],
            'expectedItem' => $this->createSettablePriceAwareMock(),
        ];

        yield 'empty value' => [
            'requestData' => ['currency' => 'USD'],
            'expectedItem' => $this->createSettablePriceAwareMock(),
        ];

        yield 'value & currency exist' => [
            'requestData' => ['currency' => 'USD', 'value' => 10],
            'expectedItem' => $this->createSettablePriceAwareMock(Price::create(10, 'USD')),
        ];
    }

    /**
     * @param Price|null $price
     *
     * @return SettablePriceAwareInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createSettablePriceAwareMock(Price $price = null)
    {
        $mock = $this->createMock(SettablePriceAwareInterface::class);

        $mock
            ->expects($this->any())
            ->method('getPrice')
            ->willReturn($price);

        return $mock;
    }
}
