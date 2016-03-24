<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsUrlHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OptionsAssembler;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;

use Oro\Component\Action\Model\ContextAccessor;

class OptionsHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextHelper;

    /** @var ApplicationsUrlHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationsUrlHelper;

    /** @var OptionsAssembler|\PHPUnit_Framework_MockObject_MockObject */
    protected $optionsAssembler;

    /** @var OptionsHelper */
    protected $helper;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockTranslator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->applicationsUrlHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsUrlHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->optionsAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockTranslator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')->getMock();

        $this->helper = new OptionsHelper(
            $this->contextHelper,
            $this->optionsAssembler,
            new ContextAccessor(),
            $this->applicationsUrlHelper,
            $this->mockTranslator
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getFrontendOptionsProvider
     */
    public function testGetFrontendOptions(array $inputData, array $expectedData)
    {
        $this->contextHelper->expects($this->once())
            ->method('getContext')
            ->with($inputData['context'])
            ->willReturn($inputData['context']);

        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->with($inputData['context'])
            ->willReturn($inputData['actionData']);

        $this->optionsAssembler->expects($this->at(0))
            ->method('assemble')
            ->willReturn($inputData['frontendOptions']);

        $this->optionsAssembler->expects($this->at(1))
            ->method('assemble')
            ->willReturn($inputData['buttonOptions']);

        $this->applicationsUrlHelper->expects($this->once())
            ->method('getExecutionUrl')
            ->with($inputData['routerContext'])
            ->willReturn($inputData['executionUrl']);

        $this->applicationsUrlHelper->expects($this->once())
            ->method('getDialogUrl')
            ->with($inputData['routerContext'])
            ->willReturn($inputData['dialogUrl']);

        $this->mockTranslator->expects($this->once())
            ->method('trans')
            ->willReturnCallback(
                function ($label) {
                    if (strpos($label, '3')) {
                        return $label;
                    }
                    return strtoupper($label);
                }
            );

        $this->assertEquals(
            $expectedData,
            $this->helper->getFrontendOptions($inputData['operation'], $inputData['context'])
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getFrontendOptionsProvider()
    {
        return [
            'empty context and parameters' => [
                'input' => [
                    'context' => [],
                    'actionData' => new ActionData(),
                    'operation' => $this->getOperation('operation1'),
                    'buttonOptions' => [],
                    'frontendOptions' => [],
                    'formOptions' => [],
                    'routerContext' => [
                        'operationName' => 'operation1',
                    ],
                    'executionUrl' => 'execution-url',
                    'dialogUrl' => 'dialog-url',
                ],
                'expected' => [
                    'options' => [
                        'hasDialog' => false,
                        'showDialog' => false,
                        'dialogOptions' => [
                            'title' => 'OPERATION1', //translated
                            'dialogOptions' => [],
                        ],
                        'executionUrl' => 'execution-url',
                        'dialogUrl' => 'dialog-url',
                        'url' => 'execution-url',
                    ],
                    'data' => [],
                ],
            ],
            'optional parameters' => [
                'input' => [
                    'context' => [],
                    'actionData' => new ActionData(['key1' => 'value1']),
                    'operation' => $this->getOperation('operation2'),
                    'frontendOptions' => [
                        'confirmation' => [
                            'option1' => 'value1',
                            'key1' => new PropertyPath('key1')
                        ],
                    ],
                    'buttonOptions' => [
                        'page_component_module' => 'module1',
                        'page_component_options' => ['option2' => 'value2'],
                        'data' => ['key1' => 'value1'],
                    ],
                    'formOptions' => [],
                    'routerContext' => [
                        'operationName' => 'operation2',
                    ],
                    'executionUrl' => 'execution-url2',
                    'dialogUrl' => 'dialog-url2',
                ],
                'expected' => [
                    'options' => [
                        'hasDialog' => false,
                        'showDialog' => false,
                        'dialogOptions' => [
                            'title' => 'OPERATION2', //translated
                            'dialogOptions' => [],
                        ],
                        'executionUrl' => 'execution-url2',
                        'dialogUrl' => 'dialog-url2',
                        'url' => 'execution-url2',
                        'confirmation' => [
                            'option1' => 'value1',
                            'key1' => 'value1',
                        ],
                    ],
                    'data' => [
                        'page-component-module' => 'module1',
                        'page-component-options' => ['option2' => 'value2'],
                        'key1' => 'value1',
                    ],
                ],
            ],
            'not translated title' => [
                'input' => [
                    'context' => [
                        'param1' => 'value1',
                    ],
                    'actionData' => new ActionData(),
                    'operation' => $this->getOperation('operation3', true),
                    'buttonOptions' => [],
                    'frontendOptions' => [],
                    'routerContext' => [
                        'param1' => 'value1',
                        'operationName' => 'operation3',
                    ],
                    'executionUrl' => 'execution-url3',
                    'dialogUrl' => 'dialog-url3',
                ],
                'expected' => [
                    'options' => [
                        'hasDialog' => true,
                        'showDialog' => false,
                        'dialogOptions' => [
                            'title' => 'operation3', //NOT TRANSLATED (see closure for translator mock return)
                            'dialogOptions' => [],
                        ],
                        'executionUrl' => 'execution-url3',
                        'dialogUrl' => 'dialog-url3',
                        'url' => 'dialog-url3',
                    ],
                    'data' => [],
                ],
            ],
            'full context and parameters' => [
                'input' => [
                    'context' => [
                        'param1' => 'value1',
                    ],
                    'actionData' => new ActionData(),
                    'operation' => $this->getOperation('operation3', true),
                    'buttonOptions' => [],
                    'frontendOptions' => [
                        'show_dialog' => true,
                        'title' => 'Custom dialog title',
                        'options' => ['option1' => 'value1'],
                    ],
                    'routerContext' => [
                        'param1' => 'value1',
                        'operationName' => 'operation3',
                    ],
                    'executionUrl' => 'execution-url3',
                    'dialogUrl' => 'dialog-url3',
                ],
                'expected' => [
                    'options' => [
                        'hasDialog' => true,
                        'showDialog' => true,
                        'dialogOptions' => [
                            'title' => 'CUSTOM DIALOG TITLE',
                            'dialogOptions' => ['option1' => 'value1'],
                        ],
                        'executionUrl' => 'execution-url3',
                        'dialogUrl' => 'dialog-url3',
                        'url' => 'dialog-url3',
                    ],
                    'data' => [],
                ],
            ],
        ];
    }

    /**
     * @param string $operationName
     * @param bool $hasForm
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOperation($operationName, $hasForm = false)
    {
        $definition = new OperationDefinition();
        $definition->setName($operationName)->setLabel($operationName);

        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getDefinition', 'hasForm'])
            ->getMock();

        $operation->expects($this->any())->method('getDefinition')->willReturn($definition);
        $operation->expects($this->any())->method('hasForm')->willReturn($hasForm);

        return $operation;
    }
}
