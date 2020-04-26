<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Action\Action\FlashMessage;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashMessageTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var MockObject|TranslatorInterface */
    protected $translator;

    /** @var MockObject|HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var MockObject|RequestStack */
    protected $requestStack;

    /** @var FlashMessage */
    protected $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->requestStack = new RequestStack();
        $this->action = new class(
            $this->contextAccessor,
            $this->translator,
            $this->htmlTagHelper,
            $this->requestStack
        ) extends FlashMessage {
            public function xgetMessage()
            {
                return $this->message;
            }

            public function xgetType()
            {
                return $this->type;
            }

            public function xgetMessageParameters()
            {
                return $this->messageParameters;
            }
        };

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->action->setDispatcher($dispatcher);
    }

    public function testInitializeException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Message parameter is required');

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
        static::assertEquals($this->action, $this->action->initialize($options));
        static::assertEquals($options['message'], $this->action->xgetMessage());
        static::assertEquals($options['type'], $this->action->xgetType());
        static::assertEquals($options['message_parameters'], $this->action->xgetMessageParameters());
    }

    public function testExecuteNoRequest()
    {
        $options = ['message' => 'test'];
        $context = [];
        $this->action->initialize($options);
        $this->translator->expects(static::never())->method(static::anything());

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
        $flashBag->expects(static::once())
            ->method('add')
            ->with('concreteType', $sanitizedMessage);

        $session = $this->createMock(Session::class);
        $session->expects(static::once())->method('getFlashBag')->willReturn($flashBag);

        $request = $this->createMock(Request::class);
        $request->expects(static::once())->method('getSession')->willReturn($session);

        $this->translator->expects(static::once())
            ->method('trans')
            ->with('concreteMessage', ['%some%' => 'other', '%other%' => 'val1'])
            ->willReturn($translatedMessage);

        $this->htmlTagHelper->expects(static::once())
            ->method('sanitize')
            ->with($translatedMessage)
            ->willReturn($sanitizedMessage);

        $this->requestStack->push($request);
        $this->action->execute($context);
    }
}
