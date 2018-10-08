<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Action\Action\FlashMessage;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Translation\TranslatorInterface;

class FlashMessageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|HtmlTagHelper
     */
    protected $htmlTagHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * @var FlashMessage
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->requestStack = new RequestStack();
        $this->action = new FlashMessage(
            $this->contextAccessor,
            $this->translator,
            $this->htmlTagHelper,
            $this->requestStack
        );

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
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
        $sanitizedMessage = 'Sanitized';

        $options = [
            'message' => new PropertyPath('message_path'),
            'type' => new PropertyPath('type_path'),
            'message_parameters' => [
                'some' => 'other',
                'other' => new PropertyPath('path1')
            ]
        ];
        $this->action->initialize($options);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('concreteType', $sanitizedMessage);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getSession')
            ->willReturn($session);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('concreteMessage', ['%some%' => 'other', '%other%' => 'val1'])
            ->willReturn($translatedMessage);

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with($translatedMessage)
            ->willReturn($sanitizedMessage);

        $this->requestStack->push($request);
        $this->action->execute($context);
    }
}
