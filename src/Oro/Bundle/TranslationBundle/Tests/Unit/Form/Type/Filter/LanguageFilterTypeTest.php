<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class LanguageFilterTypeTest extends FormIntegrationTestCase
{
    /** @var LanguageCodeFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $formatter;

    /** @var LanguageFilterType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(LanguageCodeFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new LanguageFilterType($this->formatter);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formatter, $this->type);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LanguageFilterType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityFilterType::class, $this->type->getParent());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $children = new FormView();

        $view->children = [
            'value' => $children
        ];

        $children->vars = [
            'choices' => [
                new ChoiceView([], 'Value2', 'Locale Label2'),
                new ChoiceView([], 'Value', 'Locale Label'),
            ],
        ];

        $this->formatter->expects($this->any())
            ->method('formatLocale')
            ->will($this->returnValueMap([
                ['Locale Label', 'Formatted Locale Label'],
                ['Locale Label2', 'Formatted Locale Label2'],
            ]));

        $this->type->finishView($view, $this->createMock(FormInterface::class), []);

        $this->assertEquals(
            [
                'choices' => [
                    new ChoiceView([], 'Value', 'Formatted Locale Label'),
                    new ChoiceView([], 'Value2', 'Formatted Locale Label2'),
                ],
            ],
            $children->vars
        );
    }
}
