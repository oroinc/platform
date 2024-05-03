<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\Configuration\CheckboxBuilder;
use Oro\Bundle\ThemeBundle\Form\Configuration\ConfigurationChildBuilderInterface;
use Oro\Bundle\ThemeBundle\Form\Configuration\RadioBuilder;
use Oro\Bundle\ThemeBundle\Form\Configuration\SelectBuilder;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Bundle\ThemeBundle\Form\Type\ConfigurationType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

final class ConfigurationTypeTest extends FormIntegrationTestCase
{
    private ConfigurationType $type;
    private Packages|MockBuilder $packages;

    public function setUp(): void
    {
        $this->packages = $this->createMock(Packages::class);
        $builders = new \ArrayIterator(
            [
                new SelectBuilder($this->packages),
                new CheckboxBuilder($this->packages),
                new RadioBuilder($this->packages)
            ]
        );

        $configurationBuildersProvider = new ConfigurationBuildersProvider($builders);
        $this->type = new ConfigurationType($configurationBuildersProvider);

        parent::setUp();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildForm(): void
    {
        $selectKey = LayoutThemeConfiguration::buildOptionKey('general', 'select');
        $checkboxKey = LayoutThemeConfiguration::buildOptionKey('general', 'checkbox');
        $radioKey = LayoutThemeConfiguration::buildOptionKey('general', 'radio');

        $this->packages
            ->expects(self::exactly(9))
            ->method('getUrl')
            ->withConsecutive(
                ['radio.png'],
                ['radio2.png'],
                ['option.png'],
                ['option2.png'],
                ['option.png'],
                ['default.png'],
                ['checked.png'],
                ['checked.png'],
                ['radio.png']
            )
            ->willReturnOnConsecutiveCalls(
                '/radio.png',
                '/radio2.png',
                '/option.png',
                '/option2.png',
                '/option.png',
                '/default.png',
                '/checked.png',
                '/checked.png',
                '/radio.png'
            );

        $form = $this->factory->create(
            ConfigurationType::class,
            $this->getDefaultData(),
            $this->getDefaultOptions()
        );

        self::assertTrue($form->has($selectKey));
        self::assertTrue($form->has($checkboxKey));
        self::assertTrue($form->has($radioKey));

        $formView = $form->createView();

        self::assertEquals('oro_theme_configuration_list', $formView->vars['block_prefixes'][1]);

        $selectFormView = $formView->children[$selectKey];

        self::assertEquals('oro_theme_configuration_list_item', $selectFormView->vars['block_prefixes'][3]);
        self::assertEquals($selectKey, $selectFormView->vars['attr']['data-preview-key']);
        self::assertEquals('/option.png', $selectFormView->vars['attr']['data-preview']);
        self::assertEquals(
            ConfigurationChildBuilderInterface::DATA_ROLE_CHANGE_PREVIEW,
            $selectFormView->vars['attr']['data-role']
        );
        self::assertEquals(['data-preview' => '/option.png'], $selectFormView->vars['choices'][0]->attr);
        self::assertEquals(['data-preview' => '/option2.png'], $selectFormView->vars['choices'][1]->attr);
        self::assertEquals(['data-preview' => '/option.png'], $selectFormView->vars['choices'][0]->attr);
        self::assertEquals($selectFormView->vars['group_attr'], [
            'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
            'data-page-component-options' => [
                'autoRender' => true,
                'previewSource' => '/option.png',
                'defaultPreview' => ''
            ]
        ]);

        $checkboxFormView = $formView->children[$checkboxKey];

        self::assertEquals('oro_theme_configuration_list_item', $checkboxFormView->vars['block_prefixes'][3]);
        self::assertEquals($checkboxKey, $checkboxFormView->vars['attr']['data-preview-key']);
        self::assertEquals('/checked.png', $checkboxFormView->vars['attr']['data-preview']);
        self::assertEquals('/checked.png', $checkboxFormView->vars['attr']['data-preview-unchecked']);
        self::assertEquals('/default.png', $checkboxFormView->vars['attr']['data-default-preview']);
        self::assertEquals(
            ConfigurationChildBuilderInterface::DATA_ROLE_CHANGE_PREVIEW,
            $checkboxFormView->vars['attr']['data-role']
        );
        self::assertEquals($checkboxFormView->vars['group_attr'], [
            'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
            'data-page-component-options' => [
                'autoRender' => true,
                'previewSource' => '/checked.png',
                'defaultPreview' => '/default.png'
            ]
        ]);

        $radioFormView = $formView->children[$radioKey];

        self::assertEquals('oro_theme_configuration_list_item', $radioFormView->vars['block_prefixes'][3]);
        self::assertEquals($radioKey, $radioFormView->vars['attr']['data-preview-key']);
        self::assertEquals('/radio.png', $radioFormView->vars['attr']['data-preview']);
        self::assertEquals(
            ConfigurationChildBuilderInterface::DATA_ROLE_CHANGE_PREVIEW,
            $radioFormView->vars['attr']['data-role']
        );
        self::assertEquals(['data-preview' => '/radio.png'], $radioFormView->vars['choices'][0]->attr);
        self::assertEquals(['data-preview' => '/radio2.png'], $radioFormView->vars['choices'][1]->attr);
        self::assertEquals($radioFormView->vars['group_attr'], [
            'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
            'data-page-component-options' => [
                'autoRender' => true,
                'previewSource' => '/radio.png',
                'defaultPreview' => ''
            ]
        ]);
    }

    public function testValueNotValid(): void
    {
        $form = $this->factory->create(
            ConfigurationType::class,
            $this->getDefaultData(),
            $this->getDefaultOptions()
        );

        self::assertEquals($this->getDefaultData(), $form->getData());

        $form->submit(
            array_merge(
                $this->getDefaultData(),
                [LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_3']
            )
        );

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($this->getDefaultData(), $form->getData());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $defaultData, array $submittedData, array $expectedData): void
    {
        $form = $this->factory->create(ConfigurationType::class, $defaultData, $this->getDefaultOptions());

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function testBuildFormWithMultipleSelect(): void
    {
        $options = [
            'theme_configuration' => [
                'sections' => [
                    'general' => [
                        'options' => [
                            'select' => [
                                'label' => 'Select',
                                'type' => 'select',
                                'default' => 'option_1',
                                'values' => ['option_1' => 'Option 1', 'option_2' => 'Option 2'],
                                'options' => ['multiple' => true]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $form = $this->factory->create(ConfigurationType::class, [], $options);
        $select = $form->get(LayoutThemeConfiguration::buildOptionKey('general', 'select'));

        self::assertEquals(['option_1'], $select->getData());
        self::assertTrue($select->getConfig()->getOption('multiple'));
    }

    public function submitProvider(): array
    {
        return [
            'test submit' => [
                'defaultData' => $this->getDefaultData(),
                'submittedData' => [
                    LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_2',
                    LayoutThemeConfiguration::buildOptionKey('general', 'checkbox') => false,
                    LayoutThemeConfiguration::buildOptionKey('general', 'radio') => 'radio_2',
                ],
                'expectedData' => [
                    LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_2',
                    LayoutThemeConfiguration::buildOptionKey('general', 'checkbox') => false,
                    LayoutThemeConfiguration::buildOptionKey('general', 'radio') => 'radio_2',
                ],
            ],
            'test submit with not configured options' => [
                'defaultData' => $this->getDefaultData(),
                'submittedData' => [
                    LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_2',
                    LayoutThemeConfiguration::buildOptionKey('general', 'checkbox') => false,
                    LayoutThemeConfiguration::buildOptionKey('general', 'radio') => 'radio_2',
                    LayoutThemeConfiguration::buildOptionKey('not', 'configured') => 'not-configured',
                ],
                'expectedData' => [
                    LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_2',
                    LayoutThemeConfiguration::buildOptionKey('general', 'checkbox') => false,
                    LayoutThemeConfiguration::buildOptionKey('general', 'radio') => 'radio_2',
                ],
            ],
            'test submit partial data' => [
                'defaultData' => $this->getDefaultData(),
                'submittedData' => [
                    LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_2'
                ],
                'expectedData' => [
                    LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_2',
                    LayoutThemeConfiguration::buildOptionKey('general', 'checkbox') => false,
                    LayoutThemeConfiguration::buildOptionKey('general', 'radio') => null,
                ],
            ],
            'test submit with extra default data' => [
                'defaultData' => array_merge($this->getDefaultData(), ['extra' => 'value', 'extra__2' => 'value']),
                'submittedData' => [
                    LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_1',
                    LayoutThemeConfiguration::buildOptionKey('general', 'checkbox') => false,
                    LayoutThemeConfiguration::buildOptionKey('general', 'radio') => 'radio_2',
                ],
                'expectedData' => [
                    'extra' => 'value',
                    LayoutThemeConfiguration::buildOptionKey('extra', '2') => 'value',
                    LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_1',
                    LayoutThemeConfiguration::buildOptionKey('general', 'checkbox') => false,
                    LayoutThemeConfiguration::buildOptionKey('general', 'radio') => 'radio_2',
                ],
            ]
        ];
    }

    private function getDefaultData(): array
    {
        return [
            LayoutThemeConfiguration::buildOptionKey('general', 'select') => 'option_1',
            LayoutThemeConfiguration::buildOptionKey('general', 'checkbox') => false,
            LayoutThemeConfiguration::buildOptionKey('general', 'radio') => 'radio_1',
        ];
    }

    private function getDefaultOptions(): array
    {
        return [
            'theme_configuration' => [
                'sections' => [
                    'general' => [
                        'options' => [
                            'select' => [
                                'label' => 'Select',
                                'type' => 'select',
                                'default' => 'option_1',
                                'values' => [
                                    'option_1' => 'Option 1',
                                    'option_2' => 'Option 2'
                                ],
                                'previews' => [
                                    'option_1' => 'option.png',
                                    'option_2' => 'option2.png'
                                ]
                            ],
                            'checkbox' => [
                                'label' => 'Checkbox',
                                'type' => 'checkbox',
                                'default' => 'unchecked',
                                'previews' => [
                                    'unchecked' => 'checked.png',
                                    ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png'
                                ]
                            ],
                            'radio' => [
                                'label' => 'Select',
                                'type' => 'radio',
                                'default' => 'radio_1',
                                'values' => [
                                    'radio_1' => 'Option 1',
                                    'radio_2' => 'Option 2'
                                ],
                                'previews' => [
                                    'radio_1' => 'radio.png',
                                    'radio_2' => 'radio2.png'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type
                ],
                []
            ),
        ];
    }
}
