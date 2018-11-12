<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\CurrencyBundle\Api\Processor\SetPriceByValueAndCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\SettablePriceAwareInterface;

class SetPriceByValueAndCurrencyTest extends FormProcessorTestCase
{
    /** @var SetPriceByValueAndCurrency */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetPriceByValueAndCurrency();
    }

    public function testProcessWithoutRequestData()
    {
        $requestData = [];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals($requestData, $this->context->getRequestData());
    }

    public function testProcessWithoutRequestSettablePriceAwareItem()
    {
        $requestData = ['currency' => 'USD', 'value' => 10];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals($requestData, $this->context->getRequestData());
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

        $this->context->setRequestData($requestData);
        $this->context->setResult($actualItem);
        $this->processor->process($this->context);

        self::assertEquals($requestData, $this->context->getRequestData());
        self::assertEquals($expectedItem, $actualItem);
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
     * @return SettablePriceAwareInterface|\PHPUnit\Framework\MockObject\MockObject
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
