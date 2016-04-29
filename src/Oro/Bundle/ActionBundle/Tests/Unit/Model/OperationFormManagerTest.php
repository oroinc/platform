<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationFormManager;
use Oro\Bundle\ActionBundle\Model\OperationManager;

class OperationFormManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Operation */
    protected $operation;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface */
    protected $formFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationManager */
    protected $operationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var OperationFormManager */
    protected $manager;

    protected function setUp()
    {
        $this->operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->operationManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new OperationFormManager($this->formFactory, $this->operationManager, $this->contextHelper);
    }

    protected function tearDown()
    {
        unset($this->manager, $this->formFactory, $this->operationManager, $this->contextHelper, $this->operation);
    }

    public function testGetOperationForm()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OperationDefinition $definition */
        $definition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getFormType')
            ->willReturn('form_type');

        $this->operation->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $this->operation->expects($this->once())
            ->method('getFormOptions')
            ->willReturn(['some_option' => 'option_value']);

        $this->operationManager->expects($this->once())
            ->method('getOperation')
            ->willReturnCallback(function ($operationName) {
                $this->operation->expects($this->any())
                    ->method('getName')
                    ->willReturn($operationName);

                return $this->operation;
            });

        $data = new ActionData(['data' => ['param']]);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                'form_type',
                $data,
                [
                    'some_option' => 'option_value',
                    'operation' => $this->operation
                ]
            )
            ->willReturn($form);

        $this->assertSame($form, $this->manager->getOperationForm($data, new ActionData(['data' => ['param']])));
    }
}
