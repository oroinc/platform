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
    private const ENTITY_CLASS = 'stdClass';
    private const ENTITY_ID = 42;
    private const ROUTE_NAME = 'test_route_name';
    private const EXECUTION_ROUTE_NAME = 'test_execution_route_name';
    private const FORM_DIALOG_ROUTE_NAME = 'test_form_dialog_route_name';
    private const FORM_PAGE_ROUTE_NAME = 'test_form_page_route_name';
    private const DATAGRID_NAME = 'test_datagrid_name';
    private const REFERRER_URL = '/test/referrer/utl';
    private const GROUP = 'test_group';

    /** @var ContextHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $contextHelper;

    /** @var OperationRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $operationRegistry;

    /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsResolver;

    /** @var OperationButtonProviderExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->operationRegistry = $this->createMock(OperationRegistry::class);
        $this->contextHelper = $this->createMock(ContextHelper::class);
        $this->optionsResolver = $this->createMock(OptionsResolver::class);

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
            $routeProvider,
            $this->optionsResolver
        );
    }

    /**
     * @dataProvider findDataProvider
     */
    public function testFind(array $operations, ButtonSearchContext $buttonSearchContext, array $expected)
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
    public function testIsAvailable(OperationButton $button, bool $expected)
    {
        $definition = $button->getOperation()->getDefinition();
        $definition->setFrontendOptions(['frontend' => 'not resolved'])->setButtonOptions(['button' => 'not resolved']);
        $this->optionsResolver->expects($this->exactly(2))
            ->method('resolveOptions')
            ->withConsecutive(
                [$this->anything(), $definition->getFrontendOptions()],
                [$this->anything(), $definition->getButtonOptions()]
            )
            ->willReturnOnConsecutiveCalls(
                ['frontend' => 'resolved'],
                ['button' => 'resolved']
            );

        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->willReturn(new ActionData());

        $this->assertEquals($expected, $this->extension->isAvailable($button, $this->createButtonSearchContext()));
        $this->assertEquals(['frontend' => 'resolved'], $definition->getFrontendOptions());
        $this->assertEquals(['button' => 'resolved'], $definition->getButtonOptions());
    }

    public function isAvailableDataProvider(): array
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
        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->willReturn(new ActionData());

        $button = $this->createOperationButton(false);
        /** @var Operation|\PHPUnit\Framework\MockObject\MockObject $operation */
        $operation = $button->getOperation();
        $definition = $operation->getDefinition();
        $definition->setFrontendOptions(['frontend' => 'not resolved'])->setButtonOptions(['button' => 'not resolved']);
        $this->optionsResolver->expects($this->exactly(2))
            ->method('resolveOptions')
            ->withConsecutive(
                [$this->anything(), $definition->getFrontendOptions()],
                [$this->anything(), $definition->getButtonOptions()]
            )
            ->willReturnOnConsecutiveCalls(
                ['frontend' => 'resolved'],
                ['button' => 'resolved']
            );

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

    public function testSupports()
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
