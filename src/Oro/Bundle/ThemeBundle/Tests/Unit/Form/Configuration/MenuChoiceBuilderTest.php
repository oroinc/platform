<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\NavigationBundle\Form\Type\MenuChoiceType;
use Oro\Bundle\ThemeBundle\Form\Configuration\MenuChoiceBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

final class MenuChoiceBuilderTest extends TestCase
{
    private MenuChoiceBuilder $menuChoiceBuilder;

    private FormBuilderInterface $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->menuChoiceBuilder = new MenuChoiceBuilder();
    }

    public function testThatOptionSelectSupported(): void
    {
        self::assertTrue($this->menuChoiceBuilder->supports(['type'=> 'menu_selector']));
    }

    /**
     * @dataProvider optionDataProvider
     */
    public function testThatOptionBuiltCorrectly(array $option, array $expected): void
    {
        $this->formBuilder
            ->expects(self::once())
            ->method('add')
            ->with(
                $expected['name'],
                $expected['form_type'],
                $expected['options']
            );

        $this->menuChoiceBuilder->buildOption($this->formBuilder, $option);
    }

    private function optionDataProvider(): array
    {
        return [
            'no previews' => [
                [
                    'name' => 'general-menu-choice',
                    'label' => 'Select',
                    'type' => 'menu_selector',
                    'default' => null,
                    'options' => [
                        'required' => false,
                        'scope_type' => 'menu_frontend_visibility'
                    ]
                ],
                [
                    'name' => 'general-menu-choice',
                    'form_type' => MenuChoiceType::class,
                    'options' => [
                        'required' => false,
                        'empty_data' => null,
                        'label' => 'Select',
                        'attr' => [],
                        'scope_type' => 'menu_frontend_visibility'
                    ]
                ]
            ]
        ];
    }
}
