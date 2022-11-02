<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractChoiceType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractChoiceTypeTest extends \PHPUnit\Framework\TestCase
{
    private const TRANSLATION_PREFIX = 'trans_';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var AbstractChoiceType */
    private $instance;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->instance = $this->getMockForAbstractClass(
            AbstractChoiceType::class,
            [$this->translator]
        );
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testFinishView(
        string $expectedTranslationDomain,
        array $options,
        string $parentTranslationDomain = null,
        array $expectedChoices = [],
        array $inputChoices = []
    ) {
        // expectations for translator
        if ($expectedChoices) {
            $prefix = self::TRANSLATION_PREFIX;
            $this->translator->expects($this->exactly(count($expectedChoices)))
                ->method('trans')
                ->with($this->isType('string'), [], $expectedTranslationDomain)
                ->willReturnCallback(function ($id) use ($prefix) {
                    return $prefix . $id;
                });
        } else {
            $this->translator->expects($this->never())
                ->method('trans');
        }

        $form = $this->createMock(FormInterface::class);
        $filterFormView = $this->getFilterFormView($parentTranslationDomain, $inputChoices);

        $this->instance->finishView($filterFormView, $form, $options);

        // get list of actual translated choices
        $valueFormView = $filterFormView->children['value'];
        $choiceViews = $valueFormView->vars['choices'];
        $actualChoices = [];
        /** @var ChoiceView $choiceView */
        foreach ($choiceViews as $choiceView) {
            $actualChoices[$choiceView->value] = $choiceView->label;
        }

        $this->assertEquals($expectedChoices, $actualChoices);
    }

    public function finishViewDataProvider(): array
    {
        return [
            'domain from options' => [
                'expectedTranslationDomain' => 'optionsDomain',
                'options'                   => ['translation_domain' => 'optionsDomain'],
                'parentTranslationDomain'   => 'parentDomain',
                'expectedChoices'           => [
                    'key1' => self::TRANSLATION_PREFIX . 'value1',
                    'key2' => self::TRANSLATION_PREFIX . 'value2',
                ],
                'inputChoices'              => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ],
            'domain from parent' => [
                'expectedTranslationDomain' => 'parentDomain',
                'options'                   => [],
                'parentTranslationDomain'   => 'parentDomain',
                'expectedChoices'           => ['key' => self::TRANSLATION_PREFIX . 'value'],
                'inputChoices'              => ['key' => 'value'],
            ],
            'default domain' => [
                'expectedTranslationDomain' => 'messages',
                'options'                   => [],
                'parentTranslationDomain'   => null,
                'expectedChoices'           => ['key' => self::TRANSLATION_PREFIX . 'value'],
                'inputChoices'              => ['key' => 'value'],
            ],
            'empty choices' => [
                'expectedTranslationDomain' => 'messages',
                'options'                   => [],
            ]
        ];
    }

    private function getFilterFormView(string $parentTranslationDomain = null, array $choices = []): FormView
    {
        $choicesFormView = new FormView();
        $choicesFormView->vars['choices'] = [];
        foreach ($choices as $value => $label) {
            $choicesFormView->vars['choices'][] = new ChoiceView('someData', $value, $label);
        }

        $parentFormView = new FormView();
        $parentFormView->vars['translation_domain'] = $parentTranslationDomain;

        $filterFormView = new FormView($parentFormView);
        $filterFormView->children['value'] = $choicesFormView;

        return $filterFormView;
    }
}
