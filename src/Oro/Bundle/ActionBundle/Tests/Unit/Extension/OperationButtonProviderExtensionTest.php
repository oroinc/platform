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
     *
     * @return ButtonContext
     */
    public function testFind(array $operations, ButtonSearchContext $buttonSearchContext, array $expected)
    {
        $this->assertOperationRegistryMethodsCalled($operations, $buttonSearchContext);

        $this->assertContextHelperCalled($operations);

        $result = $this->extension->find($buttonSearchContext);

        $this->assertEquals($result, $expected);
    }

    /**
     * @return array
     */
    public function findDataProvider()
    {
        $operation1 = $this->createOperationMock(true);
        $buttonSearchContext = $this->createButtonSearchContext();
        $buttonContext = $this->createButtonContext($buttonSearchContext);

        return [
            'single' => [
                'operations' => [
                    $operation1,
                    $this->createOperationMock(false),
                ],
                'buttonSearchContext' => $buttonSearchContext,
                'expected' => [
                    new OperationButton($operation1, $buttonContext),
                ]
            ],
            'not available' => [
                'operations' => [
                    $this->createOperationMock(false),
                ],
                'buttonSearchContext' => $buttonSearchContext,
                'expected' => [
                ],
            ]
        ];
    }

    /**
     * @param bool $isAvailable
     *
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOperationMock($isAvailable = false)
    {
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $operation->expects($this->once())
            ->method('isAvailable')
            ->with(new ActionData())
            ->willReturn($isAvailable);

        return $operation;
    }

    /**
     * @return ButtonSearchContext
     */
    private function createButtonSearchContext()
    {
        $buttonSearchContext = new ButtonSearchContext();

        return $buttonSearchContext->setRouteName(uniqid())
            ->setEntity(uniqid(), mt_rand(1, 100))
            ->setGridName(uniqid())
            ->setGroup(uniqid())
            ->setReferrer(uniqid());
    }

    /**
     * @param ButtonSearchContext $buttonSearchContext
     *
     * @return ButtonContext
     */
    private function createButtonContext(ButtonSearchContext $buttonSearchContext)
    {
        return (new ButtonContext())->setUnavailableHidden(true)
            ->setDatagridName($buttonSearchContext->getGridName())
            ->setEntity($buttonSearchContext->getEntityClass(), $buttonSearchContext->getEntityId())
            ->setRouteName($buttonSearchContext->getRouteName())
            ->setGroup($buttonSearchContext->getGroup());
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
