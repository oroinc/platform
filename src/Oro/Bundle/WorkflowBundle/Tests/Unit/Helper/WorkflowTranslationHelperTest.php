<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class WorkflowTranslationHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $translationHelper;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var WorkflowTranslationHelper */
    private $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $this->manager = $this->getMockBuilder(TranslationManager::class)->disableOriginalConstructor()->getMock();

        $this->translationHelper = $this->createMock(TranslationHelper::class);

        $this->helper = new WorkflowTranslationHelper($this->translator, $this->manager, $this->translationHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->translator, $this->manager, $this->helper, $this->translationHelper);
    }

    public function testFindWorkflowTranslations()
    {
        $workflowName = 'test_workflow';
        $locale = 'fr';
        $data = ['data'];

        $this->translationHelper->expects($this->once())
            ->method('findValues')
            ->with(
                WorkflowTemplate::KEY_PREFIX . '.' . $workflowName,
                $locale,
                WorkflowTranslationHelper::TRANSLATION_DOMAIN
            )
            ->willReturn($data);

        $this->assertEquals($data, $this->helper->findWorkflowTranslations($workflowName, $locale));
    }

    /**
     * @dataProvider findTranslationProvider
     *
     * @param string|null $locale
     * @param string|null $value
     */
    public function testFindWorkflowTranslation($locale, $value)
    {
        $key = 'oro.workflow.test_workflow.test.key';
        $workflowName = 'test_workflow';
        $translatorLocale = 'jp';
        $fallbackValue = 'fallback data';

        $this->translator->expects($this->any())->method('getLocale')->willReturn($translatorLocale);

        $this->translationHelper->expects($this->any())
            ->method('findValues')
            ->willReturnMap(
                [
                    [
                        WorkflowTemplate::KEY_PREFIX . '.' . $workflowName,
                        $locale,
                        WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                        ['key1' => 'value1', 'key2' => 'value2', $key => $value]
                    ],
                    [
                        WorkflowTemplate::KEY_PREFIX . '.' . $workflowName,
                        $translatorLocale,
                        WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                        ['key1' => 'value1', 'key2' => 'value2', $key => $value]
                    ],
                    [
                        WorkflowTemplate::KEY_PREFIX . '.' . $workflowName,
                        Translator::DEFAULT_LOCALE,
                        WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                        ['key1' => 'value1', 'key2' => 'value2', $key => $fallbackValue]
                    ],
                ]
            );

        $this->assertEquals(
            $value ?: $fallbackValue,
            $this->helper->findWorkflowTranslation($key, $workflowName, $locale)
        );
    }

    public function testSaveTranslation()
    {
        $this->translator->expects($this->exactly(2))->method('getLocale')->willReturn('en');
        $this->manager->expects($this->exactly(2))
            ->method('saveTranslation')
            ->with(
                'test_key',
                'test_value',
                'en',
                WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                Translation::SCOPE_UI
            );
        $this->helper->saveTranslation('test_key', 'test_value');
        $this->helper->saveTranslation('test_key', 'test_value');
    }

    public function testSaveTranslationWithNotDefaultLocale()
    {
        $this->translator->expects($this->once())->method('getLocale')->willReturn('pl');

        $this->manager->expects($this->at(0))
            ->method('saveTranslation')
            ->with('test_key', 'test_value', 'pl', WorkflowTranslationHelper::TRANSLATION_DOMAIN);
        $this->manager->expects($this->at(1))
            ->method('saveTranslation')
            ->with(
                'test_key',
                'test_value',
                Translator::DEFAULT_LOCALE,
                WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                Translation::SCOPE_UI
            );

        $this->helper->saveTranslation('test_key', 'test_value');
    }

    public function testSaveTranslationAsSystem()
    {
        $this->translator->expects($this->once())->method('getLocale')->willReturn('en');
        $this->manager->expects($this->once())
            ->method('saveTranslation')
            ->with(
                'test_key',
                'test_value',
                'en',
                WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                Translation::SCOPE_SYSTEM
            );
        $this->helper->saveTranslationAsSystem('test_key', 'test_value');
    }

    /**
     * @dataProvider findTranslationProvider
     *
     * @param string|null $locale
     * @param string $value
     */
    public function testFindTranslation($locale, $value)
    {
        $key = 'oro.workflow.test_workflow.test.key';
        $translatorLocale = 'jp';
        $fallbackValue = 'fallback data';

        $this->translator->expects($this->any())->method('getLocale')->willReturn($translatorLocale);

        $this->translationHelper->expects($this->any())
            ->method('findValue')
            ->willReturnMap(
                [
                    [
                        $key,
                        $locale,
                        WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                        $value
                    ],
                    [
                        $key,
                        $translatorLocale,
                        WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                        $value
                    ],
                    [
                        $key,
                        Translator::DEFAULT_LOCALE,
                        WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                        $fallbackValue
                    ],
                ]
            );

        $this->assertEquals($value ?: $fallbackValue, $this->helper->findTranslation($key, $locale));
    }

    /**
     * @return array
     */
    public function findTranslationProvider()
    {
        return [
            'with locale' => [
                'locale' => 'test_locale',
                'value' => 'expected translation'
            ],
            'without locale' => [
                'locale' => null,
                'value' => 'expected translation'
            ],
            'used fallback' => [
                'locale' => 'test_locale',
                'value' => null
            ]
        ];
    }

    public function testFlushTranslations()
    {
        $this->manager->expects($this->once())->method('flush');

        $this->helper->flushTranslations();
    }

    /**
     * @param string|null $expected
     *
     * @dataProvider findValueDataProvider
     */
    public function testFindValue($expected)
    {
        $key = 'key';
        $locale = null;

        $this->translationHelper->expects($this->once())->method('findValue')->with(
            $key,
            $locale,
            WorkflowTranslationHelper::TRANSLATION_DOMAIN
        )->willReturn($expected);

        $this->assertEquals($expected, $this->helper->findValue($key, $locale));
    }

    /**
     * @return \Generator
     */
    public function findValueDataProvider()
    {
        yield 'string value' => ['expected' => 'string'];
        yield 'null value' => ['expected' => null];
    }

    public function testGenerateDefinitionTranslationKeys()
    {
        $definition = new WorkflowDefinition();
        $definition->setLabel('test.definition')
            ->setConfiguration(
                [
                    WorkflowConfiguration::NODE_STEPS => [
                        ['label' => 'test.step']
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        ['label' => 'test.attribute']
                    ],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        [
                            'label' => 'test.transition',
                            'button_label' => 'test.button.label',
                            'button_title' => 'test.button.title',
                            'message' => 'test.transition.message'
                        ]
                    ],
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            [
                                'label' => 'test.variable',
                                'options' => [
                                    'form_options' => [
                                        'tooltip' => 'test.variable.tooltip'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            );

        $this->assertEquals(
            [
                'test.definition',
                'test.step',
                'test.attribute',
                'test.transition',
                'test.button.label',
                'test.button.title',
                'test.transition.message',
                'test.variable',
                'test.variable.tooltip'
            ],
            $this->helper->generateDefinitionTranslationKeys($definition)
        );
    }

    /**
     * @param string $workflowName
     * @return TranslationKeySource
     */
    protected function getWorkflowSource($workflowName)
    {
        $translationKeySource = new TranslationKeySource(
            new WorkflowTemplate(),
            ['workflow_name' => $workflowName]
        );

        return $translationKeySource;
    }
}
