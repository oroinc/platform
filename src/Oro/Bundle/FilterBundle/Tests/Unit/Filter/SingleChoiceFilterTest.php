<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\SingleChoiceFilter;
use Symfony\Component\Form\FormFactoryInterface;

class SingleChoiceFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var SingleChoiceFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new SingleChoiceFilter($this->formFactory, new FilterUtility());
    }

    public function testPrepareDataWithNumericValueAsString()
    {
        $data = ['value' => '23'];
        self::assertSame(['value' => 23], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNumericValueAsStringAndKeepStringValueOption()
    {
        $this->filter->init('test', ['keep_string_value' => true]);

        $data = ['value' => '23'];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNumericValueAsInteger()
    {
        $data = ['value' => 45];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithArrayNumericValueAsString()
    {
        $data = ['value' => ['12', '13']];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithArrayNumericValueAsStringAndKeepStringValueOption()
    {
        $this->filter->init('test', ['keep_string_value' => true]);

        $data = ['value' => ['12', '13']];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithArrayNumericValueAsInteger()
    {
        $data = ['value' => [24, 25]];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
