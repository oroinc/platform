<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\FormFactoryInterface;

class ChoiceFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ChoiceFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new ChoiceFilter($this->formFactory, new FilterUtility());
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
        self::assertSame(['value' => [12, 13]], $this->filter->prepareData($data));
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
