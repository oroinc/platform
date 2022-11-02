<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\BooleanFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var BooleanFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(
                static function ($value) {
                    return $value . '_translated';
                }
            );

        $this->filter = new BooleanFilter($this->formFactory, new FilterUtility(), $translator);
    }

    public function testPrepareDataWithStringFalseValue()
    {
        $data = ['value' => '2'];
        self::assertSame(['value' => 2], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithStringTrueValue()
    {
        $data = ['value' => '1'];
        self::assertSame(['value' => 1], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithIntegerFalseValue()
    {
        $data = ['value' => 2];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithIntegerTrueValue()
    {
        $data = ['value' => 1];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNullValue()
    {
        $data = ['value' => null];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
