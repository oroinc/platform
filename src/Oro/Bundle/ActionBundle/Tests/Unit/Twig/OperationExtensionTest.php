<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Twig;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\ActionBundle\Twig\OperationExtension;

class OperationExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ROUTE = 'test_route';
    const REQUEST_URI = '/test/request/uri';

    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationManager */
    protected $operationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ApplicationsHelper */
    protected $appsHelper;

    /** @var OperationExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsHelper */
    protected $optionsHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ButtonProvider */
    protected $buttonProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ButtonSearchContextProvider */
    protected $buttonSearchContextProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->appsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->optionsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\OptionsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->buttonProvider = $this->getMockBuilder('Oro\Bundle\ActionBundle\Provider\ButtonProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->buttonSearchContextProvider = $this
            ->getMockBuilder('Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OperationExtension(
            $this->operationManager,
            $this->appsHelper,
            $this->contextHelper,
            $this->optionsHelper,
            $this->buttonProvider,
            $this->buttonSearchContextProvider
        );
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->actionManager,
            $this->appsHelper,
            $this->contextHelper,
            $this->optionsHelper,
            $this->buttonProvider,
            $this->buttonSearchContextProvider
        );
    }

    public function testGetName()
    {
        $this->assertEquals(OperationExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(5, $functions);

        $expectedFunctions = [
            'oro_action_widget_parameters' => [
                true,
                'Oro\Bundle\ActionBundle\Helper\ContextHelper',
                'getActionParameters',
            ],
            'oro_action_widget_route' => [
                false,
                'Oro\Bundle\ActionBundle\Helper\ApplicationsHelper',
                'getWidgetRoute',
            ],
            'has_operations' => [
                false,
                'Oro\Bundle\ActionBundle\Model\OperationManager',
                'hasOperations',
            ],
            'oro_action_frontend_options' => [
                false,
                'Oro\Bundle\ActionBundle\Helper\OptionsHelper',
                'getFrontendOptions',
            ],
            'oro_action_has_buttons' => [
                false,
                '\Oro\Bundle\ActionBundle\Twig\OperationExtension',
                'hasButtons',
            ],
        ];

        /** @var \Twig_SimpleFunction $function */
        foreach ($functions as $function) {
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $this->assertArrayHasKey($function->getName(), $expectedFunctions);
            $expectedFunction = $expectedFunctions[$function->getName()];
            $this->assertEquals($expectedFunction[0], $function->needsContext());

            $callable = $function->getCallable();
            $this->assertInstanceOf($expectedFunction[1], $callable[0]);
            $this->assertEquals($expectedFunction[2], $callable[1]);
        }
    }

    /**
     * @dataProvider hasButtonsDataProvider
     *
     * @param bool $value
     *
     * @throws \PHPUnit_Framework_Exception
     */
    public function testHasButtons($value)
    {
        $this->contextHelper->expects($this->once())
            ->method('getContext')
            ->willReturn([]);

        $this->buttonSearchContextProvider
            ->expects($this->once())
            ->method('buildFromContext')
            ->willReturn(new ButtonSearchContext());

        $this->buttonProvider->expects($this->once())->method('hasButtons')->willReturn($value);

        $this->extension->hasButtons([]);
    }

    /**
     * @return array
     */
    public function hasButtonsDataProvider()
    {
        return [
            'has_buttons' => [true],
            'has_no_buttons' => [false],
        ];
    }
}
