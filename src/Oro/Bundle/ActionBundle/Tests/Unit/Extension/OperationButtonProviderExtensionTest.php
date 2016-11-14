<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Extension;

use Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationButton;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class OperationButtonProviderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OperationButtonProviderExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper
     */
    protected $contextHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OperationRegistry
     */
    protected $operationRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ApplicationsHelperInterface
     */
    protected $applicationsHelper;

    protected $actionData;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationRegistry = $this
            ->getMockBuilder(OperationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextHelper = $this
            ->getMockBuilder(ContextHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->applicationsHelper = $this->getMock(ApplicationsHelperInterface::class);

        $this->extension = new OperationButtonProviderExtension(
            $this->operationRegistry,
            $this->contextHelper,
            $this->applicationsHelper
        );

        $this->actionData = $this->getMockBuilder(ActionData::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->contextHelper,
            $this->operationRegistry,
            $this->applicationsHelper,
            $this->actionData
        );
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param array $operations
     * @param ButtonSearchContext $buttonSearchContext
     * @param $resultCount
     */
    public function testFind(array $operations, ButtonSearchContext $buttonSearchContext, $resultCount)
    {
        $this->operationRegistry
            ->expects($this->once())
            ->method('find')
            ->with(
                $buttonSearchContext->getEntityClass(),
                $buttonSearchContext->getRouteName(),
                $buttonSearchContext->getGridName(),
                $buttonSearchContext->getGroup()
            )
            ->willReturn($operations);

        $this->contextHelper
            ->expects($this->exactly(count($operations)))
            ->method('getActionData')
            ->withAnyParameters()
            ->willReturn($this->actionData);

        $result = $this->extension->find($buttonSearchContext);
        $this->assertCount($resultCount, $result);

        foreach ($result as $item) {
            $this->assertInstanceOf(OperationButton::class, $item);
        }
    }

    public function findDataProvider()
    {
        return [
            'correct' => [
                'operations' => [
                    $this->createOperationMock(),
                    $this->createOperationMock(false),
                    $this->createOperationMock(),
                ],
                'buttonSearchContext' => $this->createButtonSearchContext(),
                'resultCount' => 2,
            ]
        ];
    }

    /**
     * @param bool $isAvailable
     *
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOperationMock($isAvailable = true)
    {
        $operation = $this
            ->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $operation
            ->expects($this->once())
            ->method('isAvailable')
            ->withAnyParameters()
            ->willReturn($isAvailable);

        return $operation;
    }

    /**
     * @return ButtonSearchContext
     */
    private function createButtonSearchContext()
    {
        $buttonSearchContext = new ButtonSearchContext();

        return $buttonSearchContext
            ->setRouteName(uniqid())
            ->setEntity(uniqid(), mt_rand(1, 100))
            ->setGridName(uniqid())
            ->setGroup(uniqid())
            ->setReferrer(uniqid());
    }
}
