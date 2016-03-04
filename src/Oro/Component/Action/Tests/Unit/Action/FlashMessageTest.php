<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Action\Action\FlashMessage;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class FlashMessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var FlashMessage
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();
        $this->action = new FlashMessage($this->contextAccessor, $this->translator);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Message parameter is required
     */
    public function testInitializeException()
    {
        $options = [];
        $this->action->initialize($options);
    }

    public function testInitialize()
    {
        $options = [
            'message' => 'test',
            'type' => 'error',
            'message_parameters' => [
                'some' => 'other'
            ]
        ];
        $this->assertEquals($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($options['message'], 'message', $this->action);
        $this->assertAttributeEquals($options['type'], 'type', $this->action);
        $this->assertAttributeEquals($options['message_parameters'], 'messageParameters', $this->action);
    }

    public function testExecuteNoRequest()
    {
        $options = ['message' => 'test'];
        $context = [];
        $this->action->initialize($options);
        $this->translator->expects($this->never())
            ->method($this->anything());

        $this->action->execute($context);
    }

    public function testExecute()
    {
        $contextData = [
            'path1' => 'val1',
            'type_path' => 'concreteType',
            'message_path' => 'concreteMessage'
        ];
        $context = new ItemStub($contextData);
        $translatedMessage = 'Translated';
        $options = [
            'message' => new PropertyPath('message_path'),
            'type' => new PropertyPath('type_path'),
            'message_parameters' => [
                'some' => 'other',
                'other' => new PropertyPath('path1')
            ]
        ];
        $this->action->initialize($options);

        $flashBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface')
            ->getMock();
        $flashBag->expects($this->once())
            ->method('add')
            ->with('concreteType', $translatedMessage);

        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('getFlashBag')
            ->will($this->returnValue($flashBag));

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('concreteMessage', ['%some%' => 'other', '%other%' => 'val1'])
            ->will($this->returnValue($translatedMessage));

        $this->action->setRequest($request);
        $this->action->execute($context);
    }
}
