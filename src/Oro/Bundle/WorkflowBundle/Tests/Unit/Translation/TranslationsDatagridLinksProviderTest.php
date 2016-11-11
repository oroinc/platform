<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;
use Oro\Bundle\WorkflowBundle\Translation\TranslationsDatagridLinksProvider;

class TranslationsDatagridLinksProviderTest extends \PHPUnit_Framework_TestCase
{
    const NODE = 'test_node';
    const ATTRIBUTE_NAME = 'test_attr_name';
    const WORKFLOW_LABEL = 'test.workflow.label.key';

    /** @var TranslationsDatagridRouteHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $routeHelper;

    /** @var LanguageProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $languageProvider;

    /** @var TranslationsDatagridLinksProvider */
    private $linksProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->routeHelper = $this->getMockBuilder(TranslationsDatagridRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->languageProvider = $this->getMockBuilder(LanguageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->linksProvider = new TranslationsDatagridLinksProvider(
            $this->routeHelper,
            $this->languageProvider
        );
    }

    protected function tearDown()
    {
        unset($this->linksProvider, $this->routeHelper, $this->linksProvider);
    }

    /**
     * @dataProvider getWorkflowTranslateLinksDataProvider
     *
     * @param array $config
     * @param bool $languagesAvailable
     * @param array $expected
     */
    public function testGetWorkflowTranslateLinks(array $config, $languagesAvailable, array $expected)
    {
        $definition = new WorkflowDefinition();
        $definition->setName('test_workflow')->setLabel(self::WORKFLOW_LABEL)->setConfiguration($config);

        $this->languageProvider->expects($this->once())->method('getAvailableLanguages')->willReturn(
            $languagesAvailable ? ['en'] : []
        );

        $this->routeHelper
            ->expects($languagesAvailable ? $this->atLeastOnce() : $this->never())
            ->method('generate')
            ->willReturnCallback(function ($data) {
                return sprintf('link_to_%s', $data['key']);
            });

        $this->assertEquals($expected, $this->linksProvider->getWorkflowTranslateLinks($definition));
    }

    /**
     * @return array
     */
    public function getWorkflowTranslateLinksDataProvider()
    {
        return [
            'empty' => [
                'config' => [
                    WorkflowConfiguration::NODE_STEPS => ['test_node' => []],
                ],
                'languagesAvailable' => true,
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
                            'form_options' => [
                                'attribute_fields' => [
                                    'attr1' => [
                                        'options' => [
                                            'label' => 'attr_label1'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'languagesAvailable' => true,
                'expected' => [
                    'label' => 'link_to_' . self::WORKFLOW_LABEL,
                    WorkflowConfiguration::NODE_STEPS => ['step1' => ['label' => 'link_to_step_label1']],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        'trans1' => [
                            'label' => 'link_to_trans_label1',
                            'message' => 'link_to_trans_message1'
                        ]
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => ['attr1' => [
                        'trans1' => ['label' => 'link_to_attr_label1']]
                    ]
                ],
            ],
            'full, languages not available' => [
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
                'languagesAvailable' => false,
                'expected' => [],
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
