<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\OptionsAssembler;

use Oro\Component\Action\Model\ContextAccessor;

class OptionsHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextHelper;

    /** @var ApplicationsHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationsHelper;

    /** @var OptionsAssembler|\PHPUnit_Framework_MockObject_MockObject */
    protected $optionsAssembler;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var OptionsHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->applicationsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextAccessor = new ContextAccessor();
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->helper = new OptionsHelper(
            $this->contextHelper,
            $this->applicationsHelper,
            $this->optionsAssembler,
            $this->contextAccessor,
            $this->router
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

        $this->applicationsHelper->expects($this->once())
            ->method('getExecutionRoute')
            ->willReturn('execution_route');

        $this->applicationsHelper->expects($this->once())
            ->method('getDialogRoute')
            ->willReturn('dialog_route');

        $this->router->expects($this->at(0))
            ->method('generate')
            ->with('execution_route', $inputData['routerContext'])
            ->willReturn($inputData['executionUrl']);

        $this->router->expects($this->at(1))
            ->method('generate')
            ->with('dialog_route', $inputData['routerContext'])
            ->willReturn($inputData['dialogUrl']);

        $this->assertEquals(
            $expectedData,
            $this->helper->getFrontendOptions($inputData['action'], $inputData['context'])
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
                    'action' => $this->getAction('action1'),
                    'buttonOptions' => [],
                    'frontendOptions' => [],
                    'formOptions' => [],
                    'routerContext' => [
                        'actionName' => 'action1',
                    ],
                    'executionUrl' => 'execution-url',
                    'dialogUrl' => 'dialog-url',
                ],
                'expected' => [
                    'hasDialog' => false,
                    'showDialog' => false,
                    'dialogOptions' => [
                        'title' => 'action1',
                        'dialogOptions' => [],
                    ],
                    'executionUrl' => 'execution-url',
                    'dialogUrl' => 'dialog-url',
                    'url' => 'execution-url',
                ],
            ],
            'optional parameters' => [
                'input' => [
                    'context' => [],
                    'actionData' => new ActionData(['key1' => 'value1']),
                    'action' => $this->getAction('action2'),
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
                        'actionName' => 'action2',
                    ],
                    'executionUrl' => 'execution-url2',
                    'dialogUrl' => 'dialog-url2',
                ],
                'expected' => [
                    'hasDialog' => false,
                    'showDialog' => false,
                    'dialogOptions' => [
                        'title' => 'action2',
                        'dialogOptions' => [],
                    ],
                    'executionUrl' => 'execution-url2',
                    'dialogUrl' => 'dialog-url2',
                    'url' => 'execution-url2',
                    'confirmation' => [
                        'option1' => 'value1',
                        'key1' => 'value1',
                    ],
                    'pageComponentModule' => 'module1',
                    'pageComponentOptions' => ['option2' => 'value2'],
                    'key1' => 'value1',
                ],
            ],
            'full context and parameters' => [
                'input' => [
                    'context' => [
                        'param1' => 'value1',
                    ],
                    'actionData' => new ActionData(),
                    'action' => $this->getAction('action3', true),
                    'buttonOptions' => [],
                    'frontendOptions' => [
                        'show_dialog' => true,
                        'options' => ['option1' => 'value1'],
                    ],
                    'routerContext' => [
                        'param1' => 'value1',
                        'actionName' => 'action3',
                    ],
                    'executionUrl' => 'execution-url3',
                    'dialogUrl' => 'dialog-url3',
                ],
                'expected' => [
                    'hasDialog' => true,
                    'showDialog' => true,
                    'dialogOptions' => [
                        'title' => 'action3',
                        'dialogOptions' => ['option1' => 'value1'],
                    ],
                    'executionUrl' => 'execution-url3',
                    'dialogUrl' => 'dialog-url3',
                    'url' => 'dialog-url3',
                ],
            ],
        ];
    }

    /**
     * @param string $actionName
     * @param bool $hasForm
     * @return Action|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAction($actionName, $hasForm = false)
    {
        $definition = new ActionDefinition();
        $definition
            ->setName($actionName)
            ->setLabel($actionName);

        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->setMethods(['getDefinition', 'hasForm'])
            ->getMock();

        $action->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $action->expects($this->any())
            ->method('hasForm')
            ->willReturn($hasForm);

        return $action;
    }
}
