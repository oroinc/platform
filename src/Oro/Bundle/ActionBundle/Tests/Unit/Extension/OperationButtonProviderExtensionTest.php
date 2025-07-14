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
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\StubButton;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OperationButtonProviderExtensionTest extends TestCase
{
    private const ENTITY_CLASS = 'stdClass';
    private const ENTITY_ID = 42;
    private const ROUTE_NAME = 'test_route_name';
    private const EXECUTION_ROUTE_NAME = 'test_execution_route_name';
    private const FORM_DIALOG_ROUTE_NAME = 'test_form_dialog_route_name';
    private const FORM_PAGE_ROUTE_NAME = 'test_form_page_route_name';
    private const DATAGRID_NAME = 'test_datagrid_name';
    private const REFERRER_URL = '/test/referrer/utl';
    private const GROUP = 'test_group';

    private ContextHelper&MockObject $contextHelper;
    private OperationRegistry&MockObject $operationRegistry;
    private OperationButtonProviderExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->operationRegistry = $this->createMock(OperationRegistry::class);
        $this->contextHelper = $this->createMock(ContextHelper::class);

        $routeProvider = $this->createMock(RouteProviderInterface::class);
        $routeProvider->expects($this->any())
            ->method('getExecutionRoute')
            ->willReturn(self::EXECUTION_ROUTE_NAME);
        $routeProvider->expects($this->any())
            ->method('getFormDialogRoute')
            ->willReturn(self::FORM_DIALOG_ROUTE_NAME);
        $routeProvider->expects($this->any())
            ->method('getFormPageRoute')
            ->willReturn(self::FORM_PAGE_ROUTE_NAME);

        $this->extension = new OperationButtonProviderExtension(
            $this->operationRegistry,
            $this->contextHelper,
            $routeProvider
        );
    }

    /**
     * @dataProvider findDataProvider
     */
    public function testFind(array $operations, ButtonSearchContext $buttonSearchContext, array $expected): void
    {
        $this->operationRegistry->expects($this->once())
            ->method('find')
            ->with(OperationFindCriteria::createFromButtonSearchContext($buttonSearchContext))
            ->willReturn($operations);
        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->willReturn(new ActionData());

        $this->assertEquals($expected, $this->extension->find($buttonSearchContext));
    }

    public function findDataProvider(): array
    {
        $operation1 = $this->createOperation('operation1', true);
        $operationSubstitution = $this->createOperation('operation_substitution', true, true);
        $operationNotAvailable = $this->createOperation('operation_not_available', false);

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
     */
    public function testIsAvailable(bool $expected): void
    {
        $context = $this->createButtonSearchContext();
        $buttonContext = $this->createMock(ButtonContext::class);
        $button = $this->createMock(OperationButton::class);
        $operation = $this->createMock(Operation::class);
        $actionData = $this->createMock(ActionData::class);
        $definition = $this->createMock(OperationDefinition::class);

        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->with($this->equalTo([
                ContextHelper::ENTITY_ID_PARAM => 42,
                ContextHelper::ENTITY_CLASS_PARAM => 'stdClass',
                ContextHelper::DATAGRID_PARAM => 'test_datagrid_name',
                ContextHelper::FROM_URL_PARAM => '/test/referrer/utl',
                ContextHelper::ROUTE_PARAM => 'test_route_name',
            ]))
            ->willReturn($actionData);

        $button->expects($this->exactly(2))
            ->method('getOperation')
            ->willReturn($operation);
        $button->expects($this->once())
            ->method('getButtonContext')
            ->willReturn($buttonContext);
        $button->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($actionData));

        $operation->expects($this->once())
            ->method('isAvailable')
            ->with($actionData)
            ->willReturn($expected);
        $operation->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $definition->expects($this->once())
            ->method('getEnabled')
            ->willReturn($expected);

        $buttonContext->expects($this->once())
            ->method('setEnabled')
            ->with($this->equalTo($expected));

        $this->assertEquals($expected, $this->extension->isAvailable($button, $context));
    }

    public function isAvailableDataProvider(): array
    {
        return [
            'available' => [
                'expected' => true,
            ],
            'not available' => [
                'expected' => false
            ]
        ];
    }

    public function isAvailableExceptionDataProvider(): array
    {
        return [
            'with errors' => [
                new ArrayCollection()
            ],
            'no errors' => [
                null
            ]
        ];
    }

    /**
     * @dataProvider isAvailableExceptionDataProvider
     */
    public function testIsAvailableException(?ArrayCollection $errors): void
    {
        $context = $this->createButtonSearchContext();
        $buttonContext = $this->createMock(ButtonContext::class);
        $button = $this->createMock(OperationButton::class);
        $operation = $this->createMock(Operation::class);
        $actionData = $this->createMock(ActionData::class);
        $definition = $this->createMock(OperationDefinition::class);
        $exception = new \Exception('Exception text');

        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->with($this->equalTo([
                ContextHelper::ENTITY_ID_PARAM => 42,
                ContextHelper::ENTITY_CLASS_PARAM => 'stdClass',
                ContextHelper::DATAGRID_PARAM => 'test_datagrid_name',
                ContextHelper::FROM_URL_PARAM => '/test/referrer/utl',
                ContextHelper::ROUTE_PARAM => 'test_route_name',
            ]))
            ->willReturn($actionData);

        $button->expects($this->atMost(3))
            ->method('getOperation')
            ->willReturn($operation);
        $button->expects($this->once())
            ->method('getButtonContext')
            ->willReturn($buttonContext);
        $button->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($actionData));

        $operation->expects($this->once())
            ->method('isAvailable')
            ->with($actionData)
            ->willThrowException($exception);
        if ($errors) {
            $operation->expects($this->once())
                ->method('getName')
                ->willReturn('Operation name');
        }
        $operation->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $definition->expects($this->once())
            ->method('getEnabled')
            ->willReturn(true);

        $buttonContext->expects($this->once())
            ->method('setEnabled')
            ->with($this->equalTo(true));

        $this->assertEquals(false, $this->extension->isAvailable($button, $context, $errors));

        if ($errors) {
            $this->assertEquals([
                'message' => sprintf(
                    'Checking conditions of operation "%s" failed.',
                    'Operation name'
                ),
                'parameters' => ['exception' => $exception]
            ], $errors->first());
        }
    }

    public function testIsAvailableExceptionUnsupportedButton(): void
    {
        $this->contextHelper->expects($this->never())
            ->method('getActionData');

        $stubButton = new StubButton();

        $this->expectException(UnsupportedButtonException::class);
        $this->expectExceptionMessage(
            'Button Oro\Bundle\ActionBundle\Tests\Unit\Stub\StubButton is not supported by ' .
            'Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension. Can not determine availability'
        );

        $this->extension->isAvailable($stubButton, $this->createButtonSearchContext());
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->extension->supports($this->createOperationButton(false)));
        $this->assertFalse($this->extension->supports($this->createMock(ButtonInterface::class)));
    }

    private function createOperation(string $name, bool $isAvailable, bool $withForm = false): Operation
    {
        $operation = $this->createMock(Operation::class);
        $definition = new OperationDefinition();
        $operation->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $operation->expects($this->any())
            ->method('isAvailable')
            ->willReturn($isAvailable);
        $operation->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
        $operation->expects($this->any())
            ->method('hasForm')
            ->willReturn($withForm);
        $operation->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $operation;
    }

    private function createOperationButton(bool $isOperationAvailable): OperationButton
    {
        $buttonSearchContext = $this->createButtonSearchContext();
        $buttonContext = $this->createButtonContext($buttonSearchContext);
        $data = new ActionData();

        $name = uniqid('operation_', true);

        return new OperationButton(
            $name,
            $this->createOperation($name, $isOperationAvailable),
            $buttonContext,
            $data
        );
    }

    private function createButtonSearchContext(): ButtonSearchContext
    {
        $buttonSearchContext = new ButtonSearchContext();

        return $buttonSearchContext->setRouteName(self::ROUTE_NAME)
            ->setEntity(self::ENTITY_CLASS, self::ENTITY_ID)
            ->setDatagrid(self::DATAGRID_NAME)
            ->setGroup(self::GROUP)
            ->setReferrer(self::REFERRER_URL);
    }

    private function createButtonContext(ButtonSearchContext $buttonSearchContext, bool $isForm = false): ButtonContext
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
}
