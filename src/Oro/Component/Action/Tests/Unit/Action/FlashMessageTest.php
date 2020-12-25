<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Action\Action\FlashMessage;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashMessageTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var FlashMessage */
    private $action;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->requestStack = new RequestStack();

        $this->action = new FlashMessage(
            new ContextAccessor(),
            $this->translator,
            $this->htmlTagHelper,
            $this->requestStack
        );
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    /**
     * @param Request|\PHPUnit\Framework\MockObject\MockObject $request
     *
     * @return FlashBagInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectGetFlashBag(Request $request): FlashBagInterface
    {
        $flashBag = $this->createMock(FlashBagInterface::class);
        $session = $this->createMock(Session::class);
        $request->expects(self::once())
            ->method('getSession')
            ->willReturn($session);
        $session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        return $flashBag;
    }

    public function testInitializeWithoutMessageParameter()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "message" is required.');

        $this->action->initialize([]);
    }

    public function testInitializeWithEmptyMessageParameter()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "message" is required.');

        $this->action->initialize(['message' => '']);
    }

    public function testExecuteWithoutRequest()
    {
        $this->action->initialize(['message' => 'test']);
        $this->action->execute([]);
    }

    public function testExecute()
    {
        $type = 'test_type';
        $message = 'test message';
        $translatedMessage = 'Translated';
        $sanitizedMessage = 'Sanitized';

        $this->action->initialize([
            'type'               => new PropertyPath('type_path'),
            'message'            => new PropertyPath('message_path'),
            'message_parameters' => [
                'some'  => 'other',
                'other' => new PropertyPath('path1')
            ]
        ]);

        $request = $this->createMock(Request::class);

        $flashBag = $this->expectGetFlashBag($request);
        $flashBag->expects(self::once())
            ->method('add')
            ->with($type, $sanitizedMessage);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($message, ['%some%' => 'other', '%other%' => 'val1'])
            ->willReturn($translatedMessage);
        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with($translatedMessage)
            ->willReturn($sanitizedMessage);

        $this->requestStack->push($request);
        $this->action->execute(new ItemStub([
            'path1'        => 'val1',
            'type_path'    => $type,
            'message_path' => $message
        ]));
    }

    public function testExecuteWithoutTranslation()
    {
        $type = 'test_type';
        $message = 'test message, %some%, %other%, %missing%';
        $updatedMessage = 'test message, other, val1, %missing%';
        $sanitizedMessage = 'Sanitized';

        $this->action->initialize([
            'type'               => new PropertyPath('type_path'),
            'message'            => new PropertyPath('message_path'),
            'translate'          => new PropertyPath('translate_path'),
            'message_parameters' => [
                'some'  => 'other',
                'other' => new PropertyPath('path1')
            ]
        ]);

        $request = $this->createMock(Request::class);

        $flashBag = $this->expectGetFlashBag($request);
        $flashBag->expects(self::once())
            ->method('add')
            ->with($type, $sanitizedMessage);

        $this->translator->expects(self::never())
            ->method('trans');
        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with($updatedMessage)
            ->willReturn($sanitizedMessage);

        $this->requestStack->push($request);
        $this->action->execute(new ItemStub([
            'path1'          => 'val1',
            'type_path'      => $type,
            'message_path'   => $message,
            'translate_path' => false
        ]));
    }

    public function testExecuteWithoutTranslationAndWithoutMessageParameters()
    {
        $type = 'test_type';
        $message = 'test message';
        $sanitizedMessage = 'Sanitized';

        $this->action->initialize([
            'type'      => new PropertyPath('type_path'),
            'message'   => new PropertyPath('message_path'),
            'translate' => new PropertyPath('translate_path')
        ]);

        $request = $this->createMock(Request::class);

        $flashBag = $this->expectGetFlashBag($request);
        $flashBag->expects(self::once())
            ->method('add')
            ->with($type, $sanitizedMessage);

        $this->translator->expects(self::never())
            ->method('trans');
        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with($message)
            ->willReturn($sanitizedMessage);

        $this->requestStack->push($request);
        $this->action->execute(new ItemStub([
            'type_path'      => $type,
            'message_path'   => $message,
            'translate_path' => false
        ]));
    }
}
