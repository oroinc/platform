<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\EventListener\MultiFileBlockListener;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\Entity\TestEntity1;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MultiFileBlockListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var MultiFileBlockListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new MultiFileBlockListener($this->configProvider, $this->translator);
    }

    public function testOnBeforeValueRender()
    {
        $entity = new TestEntity1();
        $fieldValue = 'value1';
        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'fieldName', 'fieldType');

        $event = new ValueRenderEvent($entity, $fieldValue, $fieldConfigId);

        $this->listener->onBeforeValueRender($event);

        $this->assertTrue($event->isFieldVisible());
        $this->assertEquals('value1', $event->getFieldViewValue());
    }

    public function testOnBeforeValueRenderWithMultiFile()
    {
        $entity = new TestEntity1();
        $fieldValue = 'value1';
        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'multiFileField', 'multiFile');

        $event = new ValueRenderEvent($entity, $fieldValue, $fieldConfigId);

        $this->listener->onBeforeValueRender($event);

        $this->assertFalse($event->isFieldVisible());
        $this->assertEquals('value1', $event->getFieldViewValue());
    }

    public function testOnBeforeValueRenderWithMultiImage()
    {
        $entity = new TestEntity1();
        $fieldValue = 'value1';
        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'multiImageField', 'multiImage');

        $event = new ValueRenderEvent($entity, $fieldValue, $fieldConfigId);

        $this->listener->onBeforeValueRender($event);

        $this->assertFalse($event->isFieldVisible());
        $this->assertEquals('value1', $event->getFieldViewValue());
    }

    public function testOnBeforeValueRenderWithFile()
    {
        $entity = new TestEntity1();
        $fieldValue = 'value1';
        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'fileField', 'file');

        $event = new ValueRenderEvent($entity, $fieldValue, $fieldConfigId);

        $this->listener->onBeforeValueRender($event);

        $this->assertTrue($event->isFieldVisible());
        $this->assertEquals(
            [
                'template' => '@OroAttachment/Twig/dynamicField.html.twig',
                'fieldConfigId' => $fieldConfigId,
                'entity' => $entity,
                'value' => $fieldValue,
            ],
            $event->getFieldViewValue()
        );
    }

    public function testOnBeforeValueRenderWithImage()
    {
        $entity = new TestEntity1();
        $fieldValue = 'value1';
        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'imageField', 'image');

        $event = new ValueRenderEvent($entity, $fieldValue, $fieldConfigId);

        $this->listener->onBeforeValueRender($event);

        $this->assertTrue($event->isFieldVisible());
        $this->assertEquals(
            [
                'template' => '@OroAttachment/Twig/dynamicField.html.twig',
                'fieldConfigId' => $fieldConfigId,
                'entity' => $entity,
                'value' => $fieldValue,
            ],
            $event->getFieldViewValue()
        );
    }

    public function testOnBeforeViewRenderWithotEntity()
    {
        $event = new BeforeViewRenderEvent(
            $this->createMock(\Twig\Environment::class),
            [],
            null
        );

        $this->configProvider->expects(self::never())
            ->method('getIds');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->translator->expects(self::never())
            ->method('trans');

        $this->listener->onBeforeViewRender($event);

        $this->assertEquals([], $event->getData());
    }

    public function testOnBeforeViewRenderWithoutConfigs()
    {
        $event = new BeforeViewRenderEvent(
            $this->createMock(\Twig\Environment::class),
            [],
            new TestEntity1()
        );

        $this->configProvider->expects(self::once())
            ->method('getIds')
            ->with(TestEntity1::class);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->translator->expects(self::never())
            ->method('trans');

        $this->listener->onBeforeViewRender($event);

        $this->assertEquals([], $event->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnBeforeViewRender()
    {
        $twig = $this->createMock(\Twig\Environment::class);

        $entity = new TestEntity1();

        $event = new BeforeViewRenderEvent(
            $twig,
            [
                ScrollData::DATA_BLOCKS => [
                    'general' => [
                        ScrollData::SUB_BLOCKS => [
                            'block1' => [],
                        ],
                    ],
                ],

            ],
            $entity
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
            ->willReturn([$fieldConfigId, $multiFileConfigId, $multiImageConfigId]);

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [TestEntity1::class, 'multiFileField', $multiFileConfig],
                [TestEntity1::class, 'multiImageField', $multiImageConfig],
            ]);

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['multiFileLabel', [], null, null, 'translated multiFileLabel'],
                ['multiImageLabel', [], null, null, 'translated multiImageLabel'],
            ]);

        $twig->expects(self::exactly(2))
            ->method('render')
            ->willReturnMap([
                [
                    '@OroAttachment/Twig/dynamicField.html.twig',
                    ['data' => ['entity' => $entity, 'fieldConfigId' => $multiFileConfigId]],
                    'multiFile html'
                ],
                [
                    '@OroAttachment/Twig/dynamicField.html.twig',
                    ['data' => ['entity' => $entity, 'fieldConfigId' => $multiImageConfigId]],
                    'multiImage html'
                ],
            ]);

        $this->listener->onBeforeViewRender($event);

        $this->assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'general' => [
                        ScrollData::SUB_BLOCKS => [
                            'block1' => [],
                        ],
                    ],
                    'multiFileField_block_section' => [
                        'title' => 'translated multiFileLabel',
                        'useSubBlockDivider' => true,
                        'priority' => MultiFileBlockListener::ADDITIONAL_SECTION_PRIORITY - 1,
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'multiFileField' => 'multiFile html',
                                ],
                            ],
                        ],
                    ],
                    'multiImageField_block_section' => [
                        'title' => 'translated multiImageLabel',
                        'useSubBlockDivider' => true,
                        'priority' => MultiFileBlockListener::ADDITIONAL_SECTION_PRIORITY - 1,
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'multiImageField' => 'multiImage html',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $event->getData()
        );
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

        $this->assertEquals([], $event->getFormData());
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

        $this->assertEquals([], $event->getFormData());
    }

    /**
     * @dataProvider onBeforeFormRenderProvider
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
            ->willReturn([$fieldConfigId, $multiFileConfigId, $multiImageConfigId]);

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [TestEntity1::class, 'multiFileField', $multiFileConfig],
                [TestEntity1::class, 'multiImageField', $multiImageConfig],
            ]);

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['multiFileLabel', [], null, null, 'translated multiFileLabel'],
                ['multiImageLabel', [], null, null, 'translated multiImageLabel'],
            ]);

        $this->listener->onBeforeFormRender($event);

        $this->assertEquals($expectedResult, $event->getFormData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                    ],
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
