<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\NavigationBundle\Form\Type\MenuChoiceType;
use Oro\Bundle\ThemeBundle\Form\Configuration\MenuChoiceBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormBuilderInterface;

final class MenuChoiceBuilderTest extends TestCase
{
    private MenuChoiceBuilder $menuChoiceBuilder;

    private FormBuilderInterface|MockObject $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->menuChoiceBuilder = new MenuChoiceBuilder(new Packages());
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->menuChoiceBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [MenuChoiceBuilder::getType(), true],
        ];
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
                    'label' => 'Menu Choice',
                    'type' => MenuChoiceBuilder::getType(),
                    'default' => null,
                    'options' => [
                        'required' => false,
                        'scope_type' => 'menu_frontend_visibility',
                        'configs' => []
                    ],
                ],
                [
                    'name' => 'general-menu-choice',
                    'form_type' => MenuChoiceType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Menu Choice',
                        'attr' => [],
                        'scope_type' => 'menu_frontend_visibility',
                        'configs' => [],
                        'choice_attr' => function () {
                        }
                    ],
                ],
            ],
            'with previews' => [
                [
                    'name' => 'general-menu-choice',
                    'label' => 'Select',
                    'type' => 'menu_selector',
                    'default' => null,
                    'previews' => [
                        '_default' => 'default.png',
                        'frontend_menu' => 'frontend_menu.png',
                        'commerce_top_nav' => 'commerce_top_nav.png',
                    ],
                    'options' => [
                        'required' => false,
                        'scope_type' => 'menu_frontend_visibility',
                        'configs' => []
                    ],
                ],
                [
                    'name' => 'general-menu-choice',
                    'form_type' => MenuChoiceType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Select',
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-menu-choice'

                        ],
                        'scope_type' => 'menu_frontend_visibility',
                        'configs' => [],
                        'choice_attr' => function () {
                        }
                    ],
                ],
            ],
        ];
    }
}
