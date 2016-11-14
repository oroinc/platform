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

    public function testFind()
    {
        $buttonSearchContext = $this->createButtonSearchContext();

        $this->operationRegistry->expects($this->once())
            ->method('find')
            ->with(
                $buttonSearchContext->getEntityClass(),
                $buttonSearchContext->getRouteName(),
                $buttonSearchContext->getGridName(),
                $buttonSearchContext->getGroup()
            )
            ->willReturn(
                [
                    $this->createOperationMock(),
                    $this->createOperationMock(false),
                    $this->createOperationMock()
                ]
            );

        $this->contextHelper->expects($this->exactly(3))->method('getActionData')->willReturn(new ActionData());

        $result = $this->extension->find($buttonSearchContext);
        $this->assertCount(2, $result);

        foreach ($result as $item) {
            $this->assertInstanceOf(OperationButton::class, $item);
        }
    }

    /**
     * @param bool $isAvailable
     *
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOperationMock($isAvailable = true)
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
}
