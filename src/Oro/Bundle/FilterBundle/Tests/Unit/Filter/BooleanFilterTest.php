<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\BooleanFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanFilterTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private BooleanFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static function ($value) {
                return $value . '_translated';
            });

        $this->filter = new BooleanFilter($this->formFactory, new FilterUtility(), $translator);
    }

    public function testPrepareDataWithStringFalseValue(): void
    {
        $data = ['value' => '2'];
        self::assertSame(['value' => 2], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithStringTrueValue(): void
    {
        $data = ['value' => '1'];
        self::assertSame(['value' => 1], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithIntegerFalseValue(): void
    {
        $data = ['value' => 2];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithIntegerTrueValue(): void
    {
        $data = ['value' => 1];
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNullValue(): void
    {
        $data = ['value' => null];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
