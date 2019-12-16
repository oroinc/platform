<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\EventListener\MultiFileBlockListener;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\Entity\TestEntity1;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class MultiFileBlockListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var MultiFileBlockListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new MultiFileBlockListener($this->configProvider, $this->translator);
    }

    public function testOnBeforeFormRenderWithoutEntity()
    {
        $event = new BeforeFormRenderEvent(
            $this->createMock(FormView::class),
            [],
            $this->createMock(\Twig\Environment::class),
            null
        );

        $this->configProvider->expects(self::never())
            ->method('getIds');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->translator->expects(self::never())
            ->method('trans');

        $this->listener->onBeforeFormRender($event);
    }

    public function testOnBeforeFormRenderWithoutConfigs()
    {
        $event = new BeforeFormRenderEvent(
            $this->createMock(FormView::class),
            [],
            $this->createMock(\Twig\Environment::class),
            new TestEntity1()
        );

        $this->configProvider->expects(self::once())
            ->method('getIds')
            ->with(TestEntity1::class);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->translator->expects(self::never())
            ->method('trans');

        $this->listener->onBeforeFormRender($event);
    }

    /**
     * @dataProvider onBeforeFormRenderProvider
     *
     * @param array $inputData
     * @param array $expectedResult
     */
    public function testOnBeforeFormRender(array $inputData, array $expectedResult)
    {
        $event = new BeforeFormRenderEvent(
            $this->createMock(FormView::class),
            $inputData,
            $this->createMock(\Twig\Environment::class),
            new TestEntity1()
        );

        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'fieldName', 'fieldType');
        $multiFileConfigId = new FieldConfigId('extend', TestEntity1::class, 'multiFileField', 'multiFile');
        $multiImageConfigId = new FieldConfigId('extend', TestEntity1::class, 'multiImageField', 'multiImage');

        $multiFileConfig = new Config($multiFileConfigId, [
            'label' => 'multiFileLabel',
        ]);
        $multiImageConfig = new Config($multiImageConfigId, [
            'label' => 'multiImageLabel',
        ]);

        $this->configProvider->expects(self::once())
            ->method('getIds')
            ->with(TestEntity1::class)
            ->willReturn([
                $fieldConfigId,
                $multiFileConfigId,
                $multiImageConfigId,
            ]);

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->will($this->returnValueMap([
                [TestEntity1::class, 'multiFileField', $multiFileConfig],
                [TestEntity1::class, 'multiImageField', $multiImageConfig],
            ]));

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->will($this->returnValueMap([
                ['multiFileLabel', [], null, null, 'translated multiFileLabel'],
                ['multiImageLabel', [], null, null, 'translated multiImageLabel'],
            ]));

        $this->listener->onBeforeFormRender($event);

        $this->assertEquals($expectedResult, $event->getFormData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function onBeforeFormRenderProvider(): array
    {
        return [
            'with remove additional section' => [
                'input' => [
                    ScrollData::DATA_BLOCKS => [
                        'general' => [
                            ScrollData::SUB_BLOCKS => [
                                'block1' => [],
                            ],
                        ],
                        UiExtension::ADDITIONAL_SECTION_KEY => [
                            'title' => 'Additional',
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'multiFileField' => 'html content for multiFileField',
                                        'multiImageField' => 'html content for multiImageField',
                                    ],
                                ],
                            ],
                        ],
                    ]
                ],
                'expected' => [
                    ScrollData::DATA_BLOCKS => [
                        'general' => [
                            ScrollData::SUB_BLOCKS => ['block1' => []],
                        ],
                        'multiFileField_block_section' => [
                            'title' => 'translated multiFileLabel',
                            'useSubBlockDivider' => true,
                            'priority' => UiExtension::ADDITIONAL_SECTION_PRIORITY - 1,
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'multiFileField' => 'html content for multiFileField',
                                    ],
                                ],
                            ],
                        ],
                        'multiImageField_block_section' => [
                            'title' => 'translated multiImageLabel',
                            'useSubBlockDivider' => true,
                            'priority' => UiExtension::ADDITIONAL_SECTION_PRIORITY - 1,
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'multiImageField' => 'html content for multiImageField',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'without remove additional section' => [
                'input' => [
                    ScrollData::DATA_BLOCKS => [
                        'general' => [
                            ScrollData::SUB_BLOCKS => [
                                'block1' => [],
                            ],
                        ],
                        UiExtension::ADDITIONAL_SECTION_KEY => [
                            'title' => 'Additional',
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'block1' => 'html content for block1',
                                        'multiFileField' => 'html content for multiFileField',
                                        'multiImageField' => 'html content for multiImageField',
                                    ],
                                ],
                            ],
                        ],
                    ]
                ],
                'expected' => [
                    ScrollData::DATA_BLOCKS => [
                        'general' => [
                            ScrollData::SUB_BLOCKS => ['block1' => []],
                        ],
                        'multiFileField_block_section' => [
                            'title' => 'translated multiFileLabel',
                            'useSubBlockDivider' => true,
                            'priority' => UiExtension::ADDITIONAL_SECTION_PRIORITY - 1,
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'multiFileField' => 'html content for multiFileField',
                                    ],
                                ],
                            ],
                        ],
                        'multiImageField_block_section' => [
                            'title' => 'translated multiImageLabel',
                            'useSubBlockDivider' => true,
                            'priority' => UiExtension::ADDITIONAL_SECTION_PRIORITY - 1,
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'multiImageField' => 'html content for multiImageField',
                                    ],
                                ],
                            ],
                        ],
                        UiExtension::ADDITIONAL_SECTION_KEY => [
                            'title' => 'Additional',
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'block1' => 'html content for block1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
