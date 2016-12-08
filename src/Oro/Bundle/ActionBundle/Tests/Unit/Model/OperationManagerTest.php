<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OperationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationRegistry */
    protected $operationRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionGroupRegistry */
    protected $actionGroupRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var OperationManager */
    protected $manager;

    /** @var Collection|\PHPUnit_Framework_MockObject_MockObject */
    private $errorsCollection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionGroupRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->errorsCollection = new ArrayCollection();

        $this->manager = new OperationManager(
            $this->operationRegistry,
            $this->actionGroupRegistry,
            $this->contextHelper
        );
    }

    /**
     * @param array $expectedOperations
     * @param array $inputContext
     */
    protected function assertGetOperations(array $expectedOperations, array $inputContext)
    {
        $this->contextHelper->expects($this->any())
            ->method('getContext')
            ->willReturnCallback(function ($context) {
                return array_merge(
                    [
                        'route' => null,
                        'entityId' => null,
                        'entityClass' => null,
                        'datagrid' => null,
                        'group' => null
                    ],
                    $context
                );
            });

        $this->contextHelper->expects($this->any())
            ->method('getActionData')
            ->willReturn(new ActionData());

        $this->assertEquals($expectedOperations, array_keys($this->manager->getOperations($inputContext)));
    }

    /**
     * @param array $context
     * @param int $getContextCalls
     * @param int $getActionDataCalls
     */
    protected function assertContextHelperCalled(array $context = [], $getContextCalls = 1, $getActionDataCalls = 1)
    {
        $this->contextHelper->expects($this->exactly($getContextCalls))
            ->method('getContext')
            ->willReturn(
                array_merge(
                    [
                        'route' => null,
                        'entityId' => null,
                        'entityClass' => null,
                        'datagrid' => null,
                        'group' => null
                    ],
                    $context
                )
            );

        $this->contextHelper->expects($this->exactly($getActionDataCalls))
            ->method('getActionData')
            ->willReturn(new ActionData());
    }

    /**
     * @param string $name
     * @return array
     */
    protected function getOperations($name = null)
    {
        $operations = [
            'operation1' => $this->getOperation('operation1', 50, ['show_dialog' => false]),
            'operation2' => $this->getOperation('operation2', 40, ['show_dialog' => true]),
            'operation3' => $this->getOperation(
                'operation3',
                30,
                ['show_dialog' => true],
                ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1']
            ),
            'operation4' => $this->getOperation(
                'operation4',
                20,
                ['template' => 'test.html.twig', 'show_dialog' => true]
            ),
            'operation5' => $this->getOperation(
                'operation5',
                10,
                ['show_dialog' => true],
                false
            ),
            'operation6' => $this->getOperation(
                'operation6',
                50,
                ['show_dialog' => true]
            ),
            'operation7' => $this->getOperation(
                'operation7',
                50,
                ['show_dialog' => true]
            )
        ];

        return $name ? $operations[$name] : $operations;
    }

    /**
     * @param string $name
     * @param int $order
     * @param array $frontendOptions
     * @param bool $enabled
     * @return Operation
     */
    protected function getOperation($name, $order = 10, array $frontendOptions = [], $enabled = true)
    {
        $definition = new OperationDefinition();
        $definition
            ->setName($name)
            ->setLabel('Label ' . $name)
            ->setEnabled($enabled)
            ->setOrder($order)
            ->setFrontendOptions($frontendOptions);

        /* @var $actionFactory \PHPUnit_Framework_MockObject_MockObject|ActionFactory */
        $actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $conditionFactory \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory */
        $conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $attributeAssembler \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler */
        $attributeAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $formOptionsAssembler \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler */
        $formOptionsAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        return new Operation(
            $actionFactory,
            $conditionFactory,
            $attributeAssembler,
            $formOptionsAssembler,
            $definition
        );
    }

    /**
     * @param bool $isAvailable
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOperationMock($isAvailable = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $operation->expects($this->once())->method('isAvailable')->willReturn($isAvailable);

        return $operation;
    }

    /**
     * @param bool $isAllowed
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createActionGroupMock($isAllowed = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->any())->method('isAllowed')->willReturn($isAllowed);

        return $operation;
    }
}
