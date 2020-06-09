<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Filter\LanguageFilter;
use Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;

class LanguageFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|MockObject */
    protected $formFactory;

    /** @var LanguageFilter */
    protected $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new class($this->formFactory, new FilterUtility()) extends LanguageFilter {
            public function xgetParams(): array
            {
                return $this->params;
            }
        };
    }

    protected function tearDown(): void
    {
        unset($this->formFactory, $this->filter);
    }

    public function testInit()
    {
        $this->filter->init('test', []);
        static::assertEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY => [
                    'field_options' => [
                        'class' => Language::class,
                        'choice_label' => 'code',
                    ],
                ],
                FilterUtility::FRONTEND_TYPE_KEY => 'choice',
            ],
            $this->filter->xgetParams()
        );
    }

    public function testGetForm()
    {
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(LanguageFilterType::class);

        $this->filter->getForm();
    }
}
