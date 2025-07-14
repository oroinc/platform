<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Filter\LanguageFilter;
use Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class LanguageFilterTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private ManagerRegistry&MockObject $doctrine;
    private LanguageFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new LanguageFilter($this->formFactory, new FilterUtility(), $this->doctrine);
    }

    public function testInit(): void
    {
        $this->filter->init('test', []);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY => [
                    'field_options' => [
                        'class' => Language::class,
                        'choice_label' => 'code',
                    ]
                ],
                FilterUtility::FRONTEND_TYPE_KEY => 'choice',
            ],
            $params
        );
    }

    public function testGetForm(): void
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(LanguageFilterType::class)
            ->willReturn($form);

        self::assertSame($form, $this->filter->getForm());
    }
}
