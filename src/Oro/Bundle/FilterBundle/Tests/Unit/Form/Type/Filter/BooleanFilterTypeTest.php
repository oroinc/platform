<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanFilterTypeTest extends FormIntegrationTestCase
{
    private array $booleanChoices = [
        'oro.filter.form.label_type_yes' => BooleanFilterType::TYPE_YES,
        'oro.filter.form.label_type_no' => BooleanFilterType::TYPE_NO,
    ];

    private BooleanFilterType $type;

    protected function setUp(): void
    {
        $this->type = new BooleanFilterType();

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        return [
            new PreloadedExtension([
                $this->type,
                new FilterType($translator),
                new ChoiceFilterType($translator)
            ], [])
        ];
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects(self::once())
            ->method('setDefault')
            ->with('field_options', ['choices' => $this->booleanChoices])
            ->willReturnSelf();

        $this->type->configureOptions($resolver);
    }

    /**
     * @dataProvider bindDataProvider
     */
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = []
    ): void {
        $form = $this->factory->create(BooleanFilterType::class, null, $customOptions);

        $form->submit($bindData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($formData, $form->getData());

        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            self::assertArrayHasKey($key, $view->vars);
            self::assertEquals($value, $view->vars[$key]);
        }
    }

    public function bindDataProvider(): array
    {
        return [
            'empty' => [
                'bindData' => [],
                'formData' => ['type' => null, 'value' => null],
                'viewData' => [
                    'value' => ['type' => null, 'value' => null],
                ]
            ],
            'predefined value choice' => [
                'bindData' => ['value' => BooleanFilterType::TYPE_YES],
                'formData' => ['type' => null, 'value' => BooleanFilterType::TYPE_YES],
                'viewData' => [
                    'value' => ['type' => null, 'value' => BooleanFilterType::TYPE_YES],
                ],
                'customOptions' => [
                    'field_options' => [
                        'choices' => $this->booleanChoices,
                    ],
                ]
            ],
            'invalid value choice' => [
                'bindData' => ['value' => 'incorrect_value'],
                'formData' => ['type' => null],
                'viewData' => [
                    'value' => ['type' => null, 'value' => 'incorrect_value'],
                ],
                'customOptions' => [
                    'field_options' => [
                        'choices' => $this->booleanChoices
                    ],
                ]
            ],
        ];
    }
}
