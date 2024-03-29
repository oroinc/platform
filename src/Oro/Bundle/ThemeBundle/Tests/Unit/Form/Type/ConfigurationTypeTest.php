<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ThemeBundle\Form\Configuration\CheckboxBuilder;
use Oro\Bundle\ThemeBundle\Form\Configuration\RadioBuilder;
use Oro\Bundle\ThemeBundle\Form\Configuration\SelectBuilder;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Bundle\ThemeBundle\Form\Type\ConfigurationType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

final class ConfigurationTypeTest extends FormIntegrationTestCase
{
    private ConfigurationType $type;

    public function setUp(): void
    {
        $builders = new \ArrayIterator(
            [
                new SelectBuilder(),
                new CheckboxBuilder(),
                new RadioBuilder()
            ]
        );

        $configurationBuildersProvider = new ConfigurationBuildersProvider($builders);
        $this->type = new ConfigurationType($configurationBuildersProvider);

        parent::setUp();
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(
            ConfigurationType::class,
            $this->getDefaultData(),
            $this->getDefaultOptions()
        );

        self::assertTrue($form->has('general-select'));
        self::assertTrue($form->has('general-checkbox'));
        self::assertTrue($form->has('general-radio'));
    }

    public function testValueNotValid(): void
    {
        $form = $this->factory->create(
            ConfigurationType::class,
            $this->getDefaultData(),
            $this->getDefaultOptions()
        );

        self::assertEquals($this->getDefaultData(), $form->getData());

        $form->submit(['general-select' => 'option_3']);

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

    public function submitProvider(): array
    {
        return [
            'test submit' => [
                'defaultData' => $this->getDefaultData(),
                'submittedData' => [
                    'general-select' => 'option_2',
                    'general-checkbox' => false,
                    'general-radio' => 'radio_2',
                ],
                'expectedData' => [
                    'general-select' => 'option_2',
                    'general-checkbox' => false,
                    'general-radio' => 'radio_2',
                ],
            ],
            'test submit with not configured options' => [
                'defaultData' => $this->getDefaultData(),
                'submittedData' => [
                    'general-select' => 'option_2',
                    'general-checkbox' => false,
                    'general-radio' => 'radio_2',
                    'not-configured' => 'not-configured',
                ],
                'expectedData' => [
                    'general-select' => 'option_2',
                    'general-checkbox' => false,
                    'general-radio' => 'radio_2',
                ],
            ],
            'test submit partial data' => [
                'defaultData' => $this->getDefaultData(),
                'submittedData' => [
                    'general-select' => 'option_2'
                ],
                'expectedData' => [
                    'general-select' => 'option_2',
                    'general-checkbox' => false,
                    'general-radio' => 'radio_1',
                ],
            ],
            'test submit with extra default data' => [
                'defaultData' => array_merge($this->getDefaultData(), ['extra' => 'value', 'extra-2' => 'value']),
                'submittedData' => [
                    'general-select' => 'option_2',
                    'general-checkbox' => false,
                    'general-radio' => 'radio_2',
                ],
                'expectedData' => [
                    'extra' => 'value',
                    'extra-2' => 'value',
                    'general-select' => 'option_2',
                    'general-checkbox' => false,
                    'general-radio' => 'radio_2',
                ],
            ]
        ];
    }

    private function getDefaultData(): array
    {
        return [
            'general-select' => 'option_1',
            'general-checkbox' => false,
            'general-radio' => 'radio_1',
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
                                ]
                            ],
                            'checkbox' => [
                                'label' => 'Checkbox',
                                'type' => 'checkbox',
                                'default' => 'unchecked'
                            ],
                            'radio' => [
                                'label' => 'Select',
                                'type' => 'radio',
                                'default' => 'radio_1',
                                'values' => [
                                    'radio_1' => 'Option 1',
                                    'radio_2' => 'Option 2'
                                ],
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
