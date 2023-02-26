<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Filter\LanguageFilter;
use Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class LanguageFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LanguageFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new LanguageFilter($this->formFactory, new FilterUtility(), $this->doctrine);
    }

    public function testInit()
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

    public function testGetForm()
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(LanguageFilterType::class)
            ->willReturn($form);

        self::assertSame($form, $this->filter->getForm());
    }
}
