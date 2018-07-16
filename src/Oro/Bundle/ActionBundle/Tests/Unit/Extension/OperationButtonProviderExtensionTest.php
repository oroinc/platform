<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\StubButton;

class OperationButtonProviderExtensionTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'stdClass';
    const ENTITY_ID = 42;
    const ROUTE_NAME = 'test_route_name';
    const EXECUTION_ROUTE_NAME = 'test_execution_route_name';
    const FORM_DIALOG_ROUTE_NAME = 'test_form_dialog_route_name';
    const FORM_PAGE_ROUTE_NAME = 'test_form_page_route_name';
    const DATAGRID_NAME = 'test_datagrid_name';
    const REFERRER_URL = '/test/referrer/utl';
    const GROUP = 'test_group';

    /** @var OperationButtonProviderExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContextHelper */
    protected $contextHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OperationRegistry */
    protected $operationRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RouteProviderInterface */
    protected $routeProvider;

    /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $optionsResolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationRegistry = $this->getMockBuilder(OperationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextHelper = $this->getMockBuilder(ContextHelper::class)->disableOriginalConstructor()->getMock();

        $this->routeProvider = $this->createMock(RouteProviderInterface::class);

        $this->routeProvider->expects($this->any())
            ->method('getExecutionRoute')
            ->willReturn(self::EXECUTION_ROUTE_NAME);

        $this->routeProvider->expects($this->any())
            ->method('getFormDialogRoute')
            ->willReturn(self::FORM_DIALOG_ROUTE_NAME);

        $this->routeProvider->expects($this->any())
            ->method('getFormPageRoute')
            ->willReturn(self::FORM_PAGE_ROUTE_NAME);

        $this->optionsResolver = $this->createMock(OptionsResolver::class);

        $this->extension = new OperationButtonProviderExtension(
            $this->operationRegistry,
            $this->contextHelper,
            $this->routeProvider,
            $this->optionsResolver
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->extension, $this->contextHelper, $this->operationRegistry, $this->routeProvider);
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param array $operations
     * @param ButtonSearchContext $buttonSearchContext
     * @param array $expected
     */
    public function testFind(array $operations, ButtonSearchContext $buttonSearchContext, array $expected)
    {
        $this->assertOperationRegistryMethodsCalled($operations, $buttonSearchContext);
        $this->assertContextHelperCalled();

        $this->assertEquals($expected, $this->extension->find($buttonSearchContext));
    }

    /**
     * @return array
     */
    public function findDataProvider()
    {
        $operation1 = $this->createOperationMock('operation1', true);
        $operationSubstitution = $this->createOperationMock('operation_substitution', true, true);
        $operationNotAvailable = $this->createOperationMock('operation_not_available', false);

        $buttonSearchContext = $this->createButtonSearchContext();

        $buttonContext1 = $this->createButtonContext($buttonSearchContext);
        $buttonContext2 = $this->createButtonContext($buttonSearchContext, true);

        $actionData = new ActionData();

        return [
            'array' => [
                'operations' => [
                    'operation1' => $operation1,
                    'operation_not_available' => $operationNotAvailable,
                    'original_operation_name' => $operationSubstitution
                ],
                'buttonSearchContext' => $buttonSearchContext,
                'expected' => [
                    new OperationButton('operation1', $operation1, $buttonContext1, $actionData),
                    new OperationButton(
                        'operation_not_available',
                        $operationNotAvailable,
                        $buttonContext1,
                        $actionData
                    ),
                    new OperationButton(
                        'original_operation_name',
                        $operationSubstitution,
                        $buttonContext2,
                        $actionData
                    ),
                ]
            ],
            'not available' => [
                'operations' => ['operation_not_available' => $operationNotAvailable],
                'buttonSearchContext' => $buttonSearchContext,
                'expected' => [
                    new OperationButton(
                        'operation_not_available',
                        $operationNotAvailable,
                        $buttonContext1,
                        $actionData
                    )
                ]
            ]
        ];
    }

    /**
     * @dataProvider isAvailableDataProvider
     *
     * @param OperationButton $button
     * @param bool $expected
     */
    public function testIsAvailable(OperationButton $button, $expected)
    {
        $definition = $button->getOperation()->getDefinition();
        $definition->setFrontendOptions(['frontend' => 'not resolved'])->setButtonOptions(['button' => 'not resolved']);
        $this->optionsResolver->expects($this->at(0))
            ->method('resolveOptions')
            ->with($this->anything(), $definition->getFrontendOptions())
            ->willReturn(['frontend' => 'resolved']);
        $this->optionsResolver->expects($this->at(1))
            ->method('resolveOptions')
            ->with($this->anything(), $definition->getButtonOptions())
            ->willReturn(['button' => 'resolved']);

        $this->assertContextHelperCalled((int)($button instanceof OperationButton));
        $this->assertEquals($expected, $this->extension->isAvailable($button, $this->createButtonSearchContext()));

        $this->assertEquals(['frontend' => 'resolved'], $definition->getFrontendOptions());
        $this->assertEquals(['button' => 'resolved'], $definition->getButtonOptions());
    }

    /**
     * @return array
     */
    public function isAvailableDataProvider()
    {
        $operationButtonAvailable = $this->createOperationButton(true);
        $operationButtonNotAvailable = $this->createOperationButton(false);

        return [
            'available' => [
                'button' => $operationButtonAvailable,
                'expected' => true
            ],
            'not available' => [
                'button' => $operationButtonNotAvailable,
                'expected' => false
            ]
        ];
    }

    public function testIsAvailableException()
    {
        $this->assertContextHelperCalled(1);

        $button = $this->createOperationButton();
        /** @var Operation|\PHPUnit\Framework\MockObject\MockObject $operation */
        $operation = $button->getOperation();
        $definition = $operation->getDefinition();
        $definition->setFrontendOptions(['frontend' => 'not resolved'])->setButtonOptions(['button' => 'not resolved']);
        $this->optionsResolver->expects($this->at(0))
            ->method('resolveOptions')
            ->with($this->anything(), $definition->getFrontendOptions())
            ->willReturn(['frontend' => 'resolved']);
        $this->optionsResolver->expects($this->at(1))
            ->method('resolveOptions')
            ->with($this->anything(), $definition->getButtonOptions())
            ->willReturn(['button' => 'resolved']);

        $exception = new \Exception('exception when check conditions');

        $operation->expects($this->any())
            ->method('isAvailable')
            ->willThrowException($exception);

        $errors = new ArrayCollection();
        $this->extension->isAvailable($button, $this->createButtonSearchContext(), $errors);
        $this->assertCount(1, $errors);
        $this->assertEquals(
            $errors->first(),
            [
                'message' => sprintf(
                    'Checking conditions of operation "%s" failed.',
                    $operation->getName()
                ),
                'parameters' => ['exception' => $exception]
            ]
        );
    }

    public function testIsAvailableExceptionUnsupportedButton()
    {
        $this->assertContextHelperCalled(0);

        $stubButton = new StubButton();

        $this->expectException(UnsupportedButtonException::class);
        $this->expectExceptionMessage(
            'Button Oro\Bundle\ActionBundle\Tests\Unit\Stub\StubButton is not supported by ' .
            'Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension. Can not determine availability'
        );

        $this->extension->isAvailable($stubButton, $this->createButtonSearchContext());
    }

    public function testSupports()
    {
        $this->assertTrue($this->extension->supports($this->createOperationButton()));
        $this->assertFalse($this->extension->supports($this->createMock(ButtonInterface::class)));
    }

    /**
     * @param string $name
     * @param bool $isAvailable
     * @param bool $withForm
     * @return Operation|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createOperationMock($name, $isAvailable = false, $withForm = false)
    {
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $definition = new OperationDefinition();
        $operation->expects($this->any())->method('getName')->willReturn($name);
        $operation->expects($this->any())->method('isAvailable')->willReturn($isAvailable);
        $operation->expects($this->any())->method('isEnabled')->willReturn(true);
        $operation->expects($this->any())->method('hasForm')->willReturn($withForm);
        $operation->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $operation;
    }

    /**
     * @param bool $isOperationAvailable
     *
     * @return OperationButton
     */
    private function createOperationButton($isOperationAvailable = false)
    {
        $buttonSearchContext = $this->createButtonSearchContext();
        $buttonContext = $this->createButtonContext($buttonSearchContext);
        $data = new ActionData();

        $name = uniqid('operation_', true);

        return new OperationButton(
            $name,
            $this->createOperationMock($name, $isOperationAvailable),
            $buttonContext,
            $data
        );
    }

    /**
     * @return ButtonSearchContext
     */
    private function createButtonSearchContext()
    {
        $buttonSearchContext = new ButtonSearchContext();

        return $buttonSearchContext->setRouteName(self::ROUTE_NAME)
            ->setEntity(self::ENTITY_CLASS, self::ENTITY_ID)
            ->setDatagrid(self::DATAGRID_NAME)
            ->setGroup(self::GROUP)
            ->setReferrer(self::REFERRER_URL);
    }

    /**
     * @param ButtonSearchContext $buttonSearchContext
     * @param bool $isForm
     *
     * @return ButtonContext
     */
    private function createButtonContext(ButtonSearchContext $buttonSearchContext, $isForm = false)
    {
        $context = new ButtonContext();
        $context->setUnavailableHidden(true)
            ->setDatagridName($buttonSearchContext->getDatagrid())
            ->setEntity($buttonSearchContext->getEntityClass(), $buttonSearchContext->getEntityId())
            ->setRouteName($buttonSearchContext->getRouteName())
            ->setGroup($buttonSearchContext->getGroup())
            ->setExecutionRoute(self::EXECUTION_ROUTE_NAME);

        if ($isForm) {
            $context->setFormDialogRoute(self::FORM_DIALOG_ROUTE_NAME);
            $context->setFormPageRoute(self::FORM_PAGE_ROUTE_NAME);
        }

        return $context;
    }

    /**
     * @param array $operations
     * @param ButtonSearchContext $buttonSearchContext
     */
    private function assertOperationRegistryMethodsCalled(array $operations, ButtonSearchContext $buttonSearchContext)
    {
        $this->operationRegistry->expects($this->once())
            ->method('find')
            ->with(OperationFindCriteria::createFromButtonSearchContext($buttonSearchContext))
            ->willReturn($operations);
    }

    /**
     * @param int $callsCount
     */
    private function assertContextHelperCalled($callsCount = 1)
    {
        $this->contextHelper->expects($this->exactly($callsCount))
            ->method('getActionData')
            ->willReturn(new ActionData());
    }
}
