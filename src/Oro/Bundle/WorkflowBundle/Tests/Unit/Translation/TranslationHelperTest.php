<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Helper\TranslationRouteHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\TranslationHelper;

class TranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    const NODE = 'test_node';
    const ATTRIBUTE_NAME = 'test_attr_name';
    const WORKFLOW_LABEL = 'test.workflow.label.key';

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var TranslationRouteHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $routeHelper;

    /** @var TranslationHelper */
    private $helper;

    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $this->manager = $this->getMockBuilder(TranslationManager::class)->disableOriginalConstructor()->getMock();
        $this->routeHelper = $this->getMockBuilder(TranslationRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper = new TranslationHelper($this->translator, $this->manager, $this->routeHelper);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->manager, $this->helper, $this->routeHelper);
    }

    public function testSaveTranslation()
    {
        // current locale retrieve only once
        $this->translator->expects($this->once())->method('getLocale')->willReturn('en');
        $this->manager
            ->expects($this->exactly(2))
            ->method('saveValue')
            ->with('test_key', 'test_value', 'en', TranslationHelper::WORKFLOWS_DOMAIN);
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
}
