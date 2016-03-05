<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\ActionFormManager;
use Oro\Bundle\ActionBundle\Model\ActionManager;

class ActionFormManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Operation */
    protected $operation;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface */
    protected $formFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $actionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var ActionFormManager */
    protected $manager;

    protected function setUp()
    {
        $this->operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->actionManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ActionFormManager($this->formFactory, $this->actionManager, $this->contextHelper);
    }

    protected function tearDown()
    {
        unset($this->manager, $this->formFactory, $this->actionManager, $this->contextHelper, $this->action);
    }

    public function testGetActionForm()
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

        $this->actionManager->expects($this->once())
            ->method('getAction')
            ->willReturnCallback(function ($actionName) {
                $this->operation->expects($this->any())
                    ->method('getName')
                    ->willReturn($actionName);

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
                    'action' => $this->operation
                ]
            )
            ->willReturn($form);

        $this->assertSame($form, $this->manager->getActionForm($data, new ActionData(['data' => ['param']])));
    }
}
