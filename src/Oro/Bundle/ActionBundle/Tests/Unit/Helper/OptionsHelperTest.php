<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Operation\Execution\FormProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class OptionsHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var Router|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var FormProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $formProvider;

    /** @var OptionsHelper */
    protected $helper;

    /** @var HtmlTagHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $htmlTagHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->router = self::createMock(Router::class);
        $this->router->expects(self::any())->method('generate')->willReturn('generated-url');

        $this->translator = self::createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id, $parameters) {
                $parameters = implode('_', $parameters);
                return sprintf('[trans]%s[%s][/trans]', $id, $parameters);
            });

        $this->formProvider = self::createMock(FormProvider::class);
        $this->htmlTagHelper = self::createMock(HtmlTagHelper::class);
        $this->htmlTagHelper->expects(self::any())
            ->method('escape')
            ->willReturnCallback(function ($value) {
                return $value . '_escaped';
            });

        $this->helper = new OptionsHelper(
            $this->router,
            $this->translator,
            $this->formProvider,
            $this->htmlTagHelper
        );
    }

    /**
     * @param ButtonInterface $button
     * @param array $expectedData
     *
     * @dataProvider getFrontendOptionsProvider
     */
    public function testGetFrontendOptions(ButtonInterface $button, array $expectedData)
    {
        $this->assertEquals($expectedData, $this->helper->getFrontendOptions($button));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getFrontendOptionsProvider()
    {
        $defaultData = [
            'options' => [
                'hasDialog' => null,
                'showDialog' => false,
                'executionUrl' => 'generated-url',
                'url' => 'generated-url',
                'jsDialogWidget' => ButtonInterface::DEFAULT_JS_DIALOG_WIDGET,
            ],
            'data' => [],
        ];

        return [
            'empty options' => [
                'button' => $this->getButton('test label', []),
                'expectedData' => $defaultData,
            ],
            'filled options' => [
                'button' => $this->getButton('test label', [
                    'hasForm' => true,
                    'hasDialog' => true,
                    'showDialog' => true,
                    'frontendOptions' => [
                        'title' => 'custom title',
                        'title_parameters' => ['param1' => 'value1'],
                        'message' => [
                            'message' => 'message1',
                            'message_parameters' => ['param1' => 'value1'],
                        ],
                    ],
                    'buttonOptions' => [
                        'data' => [
                            'some' => 'data',
                        ],
                    ],
                ]),
                'expectedData' => [
                    'options' => [
                        'hasDialog' => true,
                        'showDialog' => true,
                        'dialogOptions' => [
                            'title' => '[trans]custom title[value1_escaped][/trans]',
                            'dialogOptions' => [],
                        ],
                        'dialogUrl' => 'generated-url',
                        'executionUrl' => 'generated-url',
                        'url' => 'generated-url',
                        'jsDialogWidget' => ButtonInterface::DEFAULT_JS_DIALOG_WIDGET,
                    ],
                    'data' => [
                        'some' => 'data',
                    ],
                ],
            ],
            'options with message' => [
                'button' => $this->getButton('test label', [
                    'hasForm' => false,
                    'frontendOptions' => [
                        'message' => [
                            'title' => 'title1',
                            'content' => 'message1',
                            'message_parameters' => ['param1' => 'value1']
                        ],
                    ]
                ]),
                'expectedData' => [
                    'options' => [
                        'hasDialog' => false,
                        'showDialog' => false,
                        'executionUrl' => 'generated-url',
                        'url' => 'generated-url',
                        'jsDialogWidget' => ButtonInterface::DEFAULT_JS_DIALOG_WIDGET,
                        'message' => [
                            'title' => 'title1',
                            'content' => '[trans]message1[value1_escaped][/trans]',
                            'message_parameters' => ['param1' => 'value1']
                        ],
                    ],
                    'data' => [],
                ],
            ],
            'options with untranslated message' => [
                'button' => $this->getButton('test label', [
                    'hasForm' => false,
                    'frontendOptions' => [
                        'message' => [
                            'message' => 'untranslated',
                            'message_parameters' => ['param1' => 'value1']
                        ],
                    ]
                ]),
                'expectedData' => [
                    'options' => [
                        'hasDialog' => false,
                        'showDialog' => false,
                        'executionUrl' => 'generated-url',
                        'url' => 'generated-url',
                        'jsDialogWidget' => ButtonInterface::DEFAULT_JS_DIALOG_WIDGET,
                    ],
                    'data' => [],
                ],
            ],
            'options with frontend confirmation message' => [
                'button' => $this->getButton('test label', [
                    'hasForm' => false,
                    'frontendOptions' => [
                        'confirmation' => [
                            'title' => 'title1',
                            'okText' => 'okText1',
                            'message' => 'message1',
                            'message_parameters' => [
                                'username' => 'username'
                            ],
                        ],
                    ],
                ]),
                'expectedData' => [
                    'options' => [
                        'hasDialog' => false,
                        'showDialog' => false,
                        'executionUrl' => 'generated-url',
                        'url' => 'generated-url',
                        'jsDialogWidget' => ButtonInterface::DEFAULT_JS_DIALOG_WIDGET,
                        'confirmation' => [
                            'title' => 'title1',
                            'okText' => 'okText1',
                            'message' => 'message1',
                            'message_parameters' => [
                                'username' => 'username_escaped'
                            ],
                        ],
                    ],
                    'data' => [],
                ],
            ],
        ];
    }

    /**
     * @param string $label
     * @param array $templateData
     *
     * @return ButtonInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getButton($label, array $templateData)
    {
        $button = $this->createMock(ButtonInterface::class);
        $button->expects($this->any())->method('getTemplateData')->willReturn($templateData);
        $button->expects($this->any())->method('getLabel')->willReturn($label);

        return $button;
    }
}
