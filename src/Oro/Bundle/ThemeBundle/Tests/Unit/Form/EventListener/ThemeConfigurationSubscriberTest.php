<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\EventListener\ThemeConfigurationSubscriber;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

class ThemeConfigurationSubscriberTest extends TestCase
{
    private ThemeConfigurationProvider|MockObject $provider;

    private ThemeConfigurationSubscriber $subscriber;

    private array $themeDefinition = [
        'configuration' => [
            'sections' => [
                'general' => [
                    'options' => [
                        'option_1' => ['label' => 'label_text'],
                        'option_2' => ['label' => 'label_text2'],
                    ],
                ],
                'additional' => [
                    'options' => [
                        'option_3' => ['label' => 'label_text3'],
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->provider = $this->createMock(ThemeConfigurationProvider::class);

        $this->subscriber = new ThemeConfigurationSubscriber($this->provider);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = ThemeConfigurationSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        self::assertArrayHasKey(FormEvents::SUBMIT, $events);
    }

    /**
     * @dataProvider preSetDataDataProvider
     */
    public function testPreSetData(array $expected, array $configuration, array $definition): void
    {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formType = $this->createMock(FormTypeInterface::class);
        $resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $form = $this->createMock(FormInterface::class);

        $form->expects(self::once())
            ->method('add')
            ->with('configuration', get_class($formType), $expected);

        $form->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['theme'], ['configuration'])
            ->willReturnOnConsecutiveCalls($form, $form);

        $form->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig->expects(self::once())
            ->method('getType')
            ->willReturn($resolvedFormType);

        $resolvedFormType->expects(self::once())
            ->method('getInnerType')
            ->willReturn($formType);

        $this->provider->expects(self::once())
            ->method('getThemeDefinition')
            ->with('theme_name')
            ->willReturn($definition);

        $themeConfiguration = new ThemeConfiguration();
        $themeConfiguration->setTheme('theme_name');
        $themeConfiguration->setConfiguration($configuration);
        if ($configuration) {
            ReflectionUtil::setPropertyValue($themeConfiguration, 'id', 1);
        }

        $event = new FormEvent($form, $themeConfiguration);
        $this->subscriber->preSetData($event);
    }

    public function preSetDataDataProvider(): array
    {
        $outdatedDefinition = $this->themeDefinition;
        unset($outdatedDefinition['configuration']['sections']['additional']);

        return [
            'new theme configuration' => [
                'expected' => ['theme_configuration' => $this->themeDefinition['configuration']],
                'configuration' => [],
                'definition' => $this->themeDefinition,
            ],
            'replace options value' => [
                'expected' => [
                    'theme_configuration' => [
                        'sections' => [
                            'general' => [
                                'options' => [
                                    'option_1' => ['label' => 'label_text', 'default' => 'value'],
                                    'option_2' => ['label' => 'label_text2', 'default' => 'value2'],
                                ]
                            ],
                            'additional' => [
                                'options' => [
                                    'option_3' => ['label' => 'label_text3', 'default' => null],
                                ]
                            ]
                        ]
                    ]
                ],
                'configuration' => [
                    'general-option_1' => 'value',
                    'general-option_2' => 'value2',
                ],
                'definition' => $this->themeDefinition,
            ],
            'outdated options' => [
                'expected' => [
                    'theme_configuration' => [
                        'sections' => [
                            'general' => [
                                'options' => [
                                    'option_1' => ['label' => 'label_text', 'default' => 'value'],
                                    'option_2' => ['label' => 'label_text2', 'default' => 'value2'],
                                ]
                            ]
                        ]
                    ]
                ],
                'configuration' => [
                    'general-option_1' => 'value',
                    'general-option_2' => 'value2',
                    'additional-option_3' => 'value3'
                ],
                'definition' => $outdatedDefinition
            ]
        ];
    }

    public function testPreSetDataIfThemeDefinitionNotExists(): void
    {
        $form = $this->createMock(FormInterface::class);

        $this->provider->expects(self::once())
            ->method('getThemeDefinition')
            ->with('theme_name')
            ->willReturn(null);

        $configuration = new ThemeConfiguration();
        $configuration->setTheme('theme_name');
        $event = new FormEvent($form, $configuration);
        $this->subscriber->preSetData($event);
    }

    public function testPreSetDataShouldReturnDefaultIfEntityThemeValueIsEmpty(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formType = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['theme'], ['configuration'])
            ->willReturnOnConsecutiveCalls($formType, $formType);

        $formType->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig->expects(self::once())
            ->method('getOption')
            ->with('choices')
            ->willReturn(['Default' => 'default']);

        $formConfig->expects(self::once())
            ->method('getOptions')
            ->willReturn([]);

        $this->provider->expects(self::once())
            ->method('getThemeDefinition')
            ->with('default')
            ->willReturn($this->themeDefinition);

        $configuration = new ThemeConfiguration();
        $event = new FormEvent($form, $configuration);
        $this->subscriber->preSetData($event);
    }

    public function testOnSubmitRemoveOutdatedOptions(): void
    {
        $outdatedDefinition = $this->themeDefinition;
        unset($outdatedDefinition['configuration']['sections']['additional']);

        $form = $this->createMock(FormInterface::class);
        $formType = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form->expects(self::once())
            ->method('get')
            ->with('theme')
            ->willReturn($formType);

        $formType->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig->expects(self::once())
            ->method('getOption')
            ->with('choices')
            ->willReturn(['Default' => 'default']);

        $this->provider->expects(self::once())
            ->method('getThemeDefinition')
            ->with('theme_name')
            ->willReturn($outdatedDefinition);

        $themeConfiguration = new ThemeConfiguration();
        $themeConfiguration->setTheme('theme_name');
        $themeConfiguration->setConfiguration([
            'general-option_1' => 'value',
            'general-option_2' => 'value2',
            'additional-option_3' => 'value3'
        ]);

        $event = new FormEvent($form, $themeConfiguration);
        $this->subscriber->onSubmit($event);

        $expected = ['general-option_1' => 'value', 'general-option_2' => 'value2'];
        self::assertEquals($expected, $event->getData()->getConfiguration());
    }
}
