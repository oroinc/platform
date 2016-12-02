<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Extension;

use Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationButton;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;

class OperationButtonProviderExtensionTest extends \PHPUnit_Framework_TestCase
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

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationRegistry */
    protected $operationRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RouteProviderInterface */
    protected $routeProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationRegistry = $this->getMockBuilder(OperationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextHelper = $this->getMockBuilder(ContextHelper::class)->disableOriginalConstructor()->getMock();

        $this->routeProvider = $this->getMock(RouteProviderInterface::class);

        $this->routeProvider->expects($this->any())
            ->method('getExecutionRoute')
            ->willReturn(self::EXECUTION_ROUTE_NAME);

        $this->routeProvider->expects($this->any())
            ->method('getFormDialogRoute')
            ->willReturn(self::FORM_DIALOG_ROUTE_NAME);

        $this->routeProvider->expects($this->any())
            ->method('getFormPageRoute')
            ->willReturn(self::FORM_PAGE_ROUTE_NAME);

        $this->extension = new OperationButtonProviderExtension(
            $this->operationRegistry,
            $this->contextHelper,
            $this->routeProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->extension, $this->contextHelper, $this->operationRegistry, $this->routeProvider);
    }

    public function testFind()
    {
        $operation1 = $this->createOperationMock(true);
        $operation2 = $this->createOperationMock(true, true);
        $operationNotAvailable = $this->createOperationMock(false);

        $buttonSearchContext = $this->createButtonSearchContext();
        $buttonContext1 = $this->createButtonContext($buttonSearchContext);
        $buttonContext2 = $this->createButtonContext($buttonSearchContext, true);

        $this->assertOperationRegistryMethodsCalled(
            [$operation1, $operationNotAvailable, $operation2],
            $buttonSearchContext
        );
        $this->assertContextHelperCalled();

        $this->assertEquals(
            [
                new OperationButton($operation1, $buttonContext1, new ActionData()),
                new OperationButton($operationNotAvailable, $buttonContext1, new ActionData()),
                new OperationButton($operation2, $buttonContext2, new ActionData()),
            ],
            $this->extension->find($buttonSearchContext)
        );
    }

    /**
     * @dataProvider isAvailableDataProvider
     *
     * @param ButtonInterface $button
     * @param bool $expected
     */
    public function testIsAvailable(ButtonInterface $button, $expected)
    {
        $this->assertContextHelperCalled((int) ($button instanceof OperationButton));
        $this->assertEquals($expected, $this->extension->isAvailable($button, $this->createButtonSearchContext()));
    }

    /**
     * @return array
     */
    public function isAvailableDataProvider()
    {
        $operationButtonAvailable = $this->createOperationButton(true);
        $operationButtonNotAvailable = $this->createOperationButton(false);
        $notOperationButtonAvailable = $this->getMock(ButtonInterface::class);

        return [
            'available' => [
                'button' => $operationButtonAvailable,
                'expected' => true
            ],
            'not available' => [
                'button' => $operationButtonNotAvailable,
                'expected' => false
            ],
            'not supported' => [
                'button' => $notOperationButtonAvailable,
                'expected' => false
            ],
        ];
    }

    public function testSupports()
    {
        $this->assertTrue($this->extension->supports($this->createOperationButton()));
        $this->assertFalse($this->extension->supports($this->getMock(ButtonInterface::class)));
    }

    /**
     * @param bool $isAvailable
     * @param bool $withForm
     *
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOperationMock($isAvailable = false, $withForm = false)
    {
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $operation->expects($this->any())->method('isAvailable')->willReturn($isAvailable);
        $operation->expects($this->any())->method('hasForm')->willReturn($withForm);

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

        return new OperationButton($this->createOperationMock($isOperationAvailable), $buttonContext, $data);
    }

    /**
     * @return ButtonSearchContext
     */
    private function createButtonSearchContext()
    {
        $buttonSearchContext = new ButtonSearchContext();

        return $buttonSearchContext->setRouteName(self::ROUTE_NAME)
            ->setEntity(self::ENTITY_CLASS, self::ENTITY_ID)
            ->setGridName(self::DATAGRID_NAME)
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
            ->setDatagridName($buttonSearchContext->getGridName())
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
            ->with(
                $buttonSearchContext->getEntityClass(),
                $buttonSearchContext->getRouteName(),
                $buttonSearchContext->getGridName(),
                $buttonSearchContext->getGroup()
            )
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
