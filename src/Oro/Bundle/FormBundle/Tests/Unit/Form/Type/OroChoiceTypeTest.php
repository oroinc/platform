<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroChoiceTypeTest extends FormIntegrationTestCase
{
    public function testGetParent(): void
    {
        $formType = new OroChoiceType();
        self::assertEquals(Select2ChoiceType::class, $formType->getParent());
    }

    /**
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(
        string $data,
        array $viewData,
        array $options = []
    ): void {
        $form = $this->factory->create(OroChoiceType::class, $data, $options);
        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            self::assertArrayHasKey($key, $view->vars);
            self::assertSame($value, $view->vars[$key]);
        }
    }

    public function buildFormDataProvider(): array
    {
        return [
            'empty' => [
                'data' => '',
                'viewData' => [
                    'value' => '',
                ],
            ],
            'select one choice' => [
                'data' => 'c1',
                'viewData' => [
                    'value' => 'c1',
                ],
                'options' => [
                    'choices' => ['c1', 'c2', 'c3'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $options, array $expected): void
    {
        $formType = new OroChoiceType();
        $optionsResolver = new OptionsResolver();
        $formType->configureOptions($optionsResolver);

        self::assertEquals(
            $expected,
            $optionsResolver->resolve($options),
        );
    }

    public function configureOptionsDataProvider(): array
    {
        $defaults = [
            'placeholder' => 'oro.form.choose_value',
            'allowClear' => true,
        ];

        return [
            'empty options' => [
                'options' => [],
                'expected' => [
                    'configs' => $defaults,
                ],
            ],
            'with extra option' => [
                'options' => ['configs' => ['extra_key' => 'extra_value']],
                'expected' => ['configs' => ['extra_key' => 'extra_value'] + $defaults],
            ],
        ];
    }
}
