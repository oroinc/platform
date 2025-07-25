<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\SingleChoiceFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class SingleChoiceFilterTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private SingleChoiceFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new SingleChoiceFilter($this->formFactory, new FilterUtility());
    }

    public function testPrepareDataWithNumericValueAsString(): void
    {
        $data = ['value' => '23'];
        self::assertSame(['value' => 23], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNumericValueAsStringAndKeepStringValueOption(): void
    {
        $this->filter->init('test', ['keep_string_value' => true]);

        $data = ['value' => '23'];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNumericValueAsInteger(): void
    {
        $data = ['value' => 45];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithArrayNumericValueAsString(): void
    {
        $data = ['value' => ['12', '13']];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithArrayNumericValueAsStringAndKeepStringValueOption(): void
    {
        $this->filter->init('test', ['keep_string_value' => true]);

        $data = ['value' => ['12', '13']];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithArrayNumericValueAsInteger(): void
    {
        $data = ['value' => [24, 25]];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
