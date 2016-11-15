<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Extension;

use Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationButton;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class OperationButtonProviderExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'stdClass';
    const ENTITY_ID = 42;
    const ROUTE_NAME = 'test_route_name';
    const EXECUTION_ROUTE_NAME = 'test_execution_route_name';
    const DIALOG_ROUTE_NAME = 'test_dialog_route_name';
    const DATAGRID_NAME = 'test_datagrid_name';
    const REFERRER_URL = '/test/referrer/utl';
    const GROUP = 'test_group';

    /** @var OperationButtonProviderExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationRegistry */
    protected $operationRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ApplicationsHelperInterface */
    protected $applicationsHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationRegistry = $this->getMockBuilder(OperationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextHelper = $this->getMockBuilder(ContextHelper::class)->disableOriginalConstructor()->getMock();

        $this->applicationsHelper = $this->getMock(ApplicationsHelperInterface::class);
        $this->applicationsHelper->expects($this->any())
            ->method('getExecutionRoute')
            ->willReturn(self::EXECUTION_ROUTE_NAME);
        $this->applicationsHelper->expects($this->any())->method('getDialogRoute')->willReturn(self::DIALOG_ROUTE_NAME);

        $this->extension = new OperationButtonProviderExtension(
            $this->operationRegistry,
            $this->contextHelper,
            $this->applicationsHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->extension, $this->contextHelper, $this->operationRegistry, $this->applicationsHelper);
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
        $this->assertContextHelperCalled($operations);

        $this->assertEquals($expected, $this->extension->find($buttonSearchContext));
    }

    /**
     * @return array
     */
    public function findDataProvider()
    {
        $operation1 = $this->createOperationMock(true);
        $operation2 = $this->createOperationMock(true, true);

        $buttonSearchContext = $this->createButtonSearchContext();

        $buttonContext1 = $this->createButtonContext($buttonSearchContext);
        $buttonContext2 = $this->createButtonContext($buttonSearchContext, true);

        return [
            'single' => [
                'operations' => [$operation1, $this->createOperationMock(false), $operation2],
                'buttonSearchContext' => $buttonSearchContext,
                'expected' => [
                    new OperationButton($operation1, $buttonContext1),
                    new OperationButton($operation2, $buttonContext2)
                ]
            ],
            'not available' => [
                'operations' => [$this->createOperationMock(false)],
                'buttonSearchContext' => $buttonSearchContext,
                'expected' => []
            ]
        ];
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
        $operation->expects($this->once())->method('isAvailable')->with(new ActionData())->willReturn($isAvailable);
        $operation->expects($this->once())->method('hasForm')->willReturn($withForm);

        return $operation;
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
            ->setExecutionUrl(self::EXECUTION_ROUTE_NAME);

        if ($isForm) {
            $context->setDialogUrl(self::DIALOG_ROUTE_NAME);
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
     * @param array $operations
     */
    private function assertContextHelperCalled(array $operations = [])
    {
        $this->contextHelper->expects($this->exactly(count($operations)))
            ->method('getActionData')
            ->willReturn(new ActionData());
    }
}
