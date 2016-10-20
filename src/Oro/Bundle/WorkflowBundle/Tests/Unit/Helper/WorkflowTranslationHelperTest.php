<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class WorkflowTranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    const NODE = 'test_node';
    const ATTRIBUTE_NAME = 'test_attr_name';
    const WORKFLOW_LABEL = 'test.workflow.label.key';

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var TranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $translationHelper;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var TranslationsDatagridRouteHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $routeHelper;

    /** @var WorkflowTranslationHelper */
    private $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $this->manager = $this->getMockBuilder(TranslationManager::class)->disableOriginalConstructor()->getMock();

        $this->translationHelper = $this->getMockBuilder(TranslationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->routeHelper = $this->getMockBuilder(TranslationsDatagridRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new WorkflowTranslationHelper(
            $this->translator,
            $this->manager,
            $this->translationHelper,
            $this->routeHelper
        );
    }

    protected function tearDown()
    {
        unset($this->translator, $this->manager, $this->helper, $this->translationHelper, $this->routeHelper);
    }

    public function testSaveTranslation()
    {
        $this->translator->expects($this->exactly(2))->method('getLocale')->willReturn('en');
        $this->manager
            ->expects($this->exactly(2))
            ->method('saveValue')
            ->with('test_key', 'test_value', 'en', WorkflowTranslationHelper::TRANSLATION_DOMAIN);
        $this->helper->saveTranslation('test_key', 'test_value');
        $this->helper->saveTranslation('test_key', 'test_value');
    }

    public function testEnsureTranslationKey()
    {
        $key = 'key_to_be_sure_that_exists';
        $this->manager->expects($this->once())->method('findTranslationKey')->with($key, 'workflows');
        $this->helper->ensureTranslationKey($key);
    }

    public function testRemoveTranslationKey()
    {
        $key = 'key_to_remove';
        $this->manager->expects($this->once())->method('removeTranslationKey')->with($key, 'workflows');
        $this->helper->removeTranslationKey($key);
    }

    public function testPrepareTranslations()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('locale1');

        $this->translationHelper->expects($this->once())
            ->method('prepareValues')
            ->with('oro.workflow.workflow1', 'locale1', WorkflowTranslationHelper::TRANSLATION_DOMAIN);

        $this->helper->prepareTranslations('workflow1');
    }

    public function testFindTranslation()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('locale1');

        $this->translationHelper->expects($this->once())
            ->method('findValue')
            ->with('test-key', 'locale1', WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturn('value');

        $this->assertEquals('value', $this->helper->findTranslation('test-key'));
    }

    public function testGetTranslation()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('locale1');

        $this->translationHelper->expects($this->once())
            ->method('getValue')
            ->with('test-key', 'locale1', WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturn('value');

        $this->assertEquals('value', $this->helper->getTranslation('test-key'));
    }

    public function testExtractTranslations()
    {
        $definition = (new WorkflowDefinition())
            ->setName('definition1')
            ->setLabel('definition1.label')
            ->setSteps([
                (new WorkflowStep())->setLabel('step2.label'),
            ])
            ->setConfiguration([
                'steps' => [
                    'step1' => ['label' => 'step1.label'],
                ],
                'transitions' => [
                    'transition1' => [
                        'label' => 'transition1.label',
                        'message' => 'transition1.message',
                    ]
                ],
                'attributes' => [
                    'attribute1' => ['label' => 'attribute1.label'],
                ],
            ]);

        $expectedDefinition = (new WorkflowDefinition())
            ->setName('definition1')
            ->setLabel('definition1.label-locale1.workflows')
            ->setSteps([
                (new WorkflowStep())->setLabel('step2.label'),
            ])
            ->setConfiguration([
                'steps' => [
                    'step1' => ['label' => 'step1.label-locale1.workflows'],
                ],
                'transitions' => [
                    'transition1' => [
                        'label' => 'transition1.label-locale1.workflows',
                        'message' => 'transition1.message-locale1.workflows',
                    ]
                ],
                'attributes' => [
                    'attribute1' => ['label' => 'attribute1.label-locale1.workflows'],
                ],
                // @todo: update in BAP-12019
                'label' => '-locale1.workflows',
            ]);

        $this->translator->expects($this->any())->method('getLocale')->willReturn('locale1');
        $this->translationHelper->expects($this->once())->method('prepareValues');

        // label + iterated keys
        // @todo: update in BAP-12019
        $this->translationHelper->expects($this->exactly(6))
            ->method('getValue')
            ->will($this->returnCallback(function ($key, $locale, $domain) {
                return sprintf('%s-%s.%s', $key, $locale, $domain);
            }));

        $this->helper->extractTranslations($definition, 'workflow1');

        $this->assertEquals($expectedDefinition, $definition);
    }

    public function testExtractTranslationsWithWorkflowName()
    {
        $definition = (new WorkflowDefinition())
            ->setName('definition1')
            ->setConfiguration(['steps' => [], 'transitions' => [], 'attributes' => []]);

        $this->helper->extractTranslations($definition, 'customName');
    }

    /**
     * @dataProvider getWorkflowTranslateLinksDataProvider
     *
     * @param array $config
     * @param array $expected
     */
    public function testGetWorkflowTranslateLinks(array $config, array $expected)
    {
        $definition = new WorkflowDefinition();
        $definition->setLabel(self::WORKFLOW_LABEL)
            ->setConfiguration($config);

        $this->routeHelper->expects($this->atLeastOnce())->method('generate')->willReturnCallback(function ($data) {
            return sprintf('link_to_%s', $data['key']);
        });

        $this->assertEquals($expected, $this->helper->getWorkflowTranslateLinks($definition));
    }

    /**
     * @return array
     */
    public function getWorkflowTranslateLinksDataProvider()
    {
        return [
            'empty' => [
                'config' => [],
                'expected' => [
                    'label' => 'link_to_' . self::WORKFLOW_LABEL,
                    WorkflowConfiguration::NODE_STEPS => [],
                    WorkflowConfiguration::NODE_TRANSITIONS => [],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [],
                ],
            ],
            'full' => [
                'config' => [
                    WorkflowConfiguration::NODE_STEPS => ['step1' => ['label' => 'step_label1']],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        'trans1' => [
                            'label' => 'trans_label1',
                            'message' => 'trans_message1',
                        ]
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => ['attr1' => ['label' => 'attr_label1']],
                ],
                'expected' => [
                    'label' => 'link_to_' . self::WORKFLOW_LABEL,
                    WorkflowConfiguration::NODE_STEPS => ['step1' => ['label' => 'link_to_step_label1']],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        'trans1' => [
                            'label' => 'link_to_trans_label1',
                            'message' => 'link_to_trans_message1'
                        ]
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => ['attr1' => ['label' => 'link_to_attr_label1']],
                ],
            ],
        ];
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
