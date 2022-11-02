<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\TranslationsDatagridLinksProvider;

class TranslationsDatagridLinksProviderTest extends \PHPUnit\Framework\TestCase
{
    private const WORKFLOW_LABEL = 'test.workflow.label.key';

    /** @var TranslationsDatagridRouteHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $routeHelper;

    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $languageProvider;

    /** @var TranslationsDatagridLinksProvider */
    private $linksProvider;

    protected function setUp(): void
    {
        $this->routeHelper = $this->createMock(TranslationsDatagridRouteHelper::class);
        $this->languageProvider = $this->createMock(LanguageProvider::class);

        $this->linksProvider = new TranslationsDatagridLinksProvider(
            $this->routeHelper,
            $this->languageProvider
        );
    }

    /**
     * @dataProvider getWorkflowTranslateLinksDataProvider
     */
    public function testGetWorkflowTranslateLinks(array $config, bool $languagesAvailable, array $expected)
    {
        $definition = new WorkflowDefinition();
        $definition->setName('test_workflow')->setLabel(self::WORKFLOW_LABEL)->setConfiguration($config);

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->willReturn($languagesAvailable ? ['en'] : []);

        $this->routeHelper->expects($languagesAvailable ? $this->atLeastOnce() : $this->never())
            ->method('generate')
            ->willReturnCallback(function ($data) {
                return sprintf('link_to_%s', $data['key']);
            });

        $this->assertEquals($expected, $this->linksProvider->getWorkflowTranslateLinks($definition));
    }

    public function getWorkflowTranslateLinksDataProvider(): array
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
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [],
                    ],
                ],
            ],
            'full' => [
                'config' => [
                    WorkflowConfiguration::NODE_STEPS => ['step1' => ['label' => 'step_label1']],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        'trans1' => [
                            'label' => 'trans_label1',
                            'button_label' => 'trans_button_label1',
                            'button_title' => 'trans_button_title1',
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
                    ],
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            'var1' => [
                                'label' => 'var_label',
                                'form_options' => [
                                    'tooltip' => 'var_tootltip',
                                ],
                            ],
                        ],
                    ],
                ],
                'languagesAvailable' => true,
                'expected' => [
                    'label' => 'link_to_' . self::WORKFLOW_LABEL,
                    WorkflowConfiguration::NODE_STEPS => ['step1' => ['label' => 'link_to_step_label1']],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        'trans1' => [
                            'label' => 'link_to_trans_label1',
                            'button_label' => 'link_to_trans_button_label1',
                            'button_title' => 'link_to_trans_button_title1',
                            'message' => 'link_to_trans_message1'
                        ]
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => ['attr1' => [
                        'trans1' => ['label' => 'link_to_attr_label1']]
                    ],
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            'var1' => 'link_to_var1',
                        ],
                    ],
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
}
