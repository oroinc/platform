<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\EventListener\OriginalFileNamesConfigurationListener;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class OriginalFileNamesConfigurationListenerTest extends TestCase
{
    private Session&MockObject $session;
    private RequestStack&MockObject $requestStack;
    private OriginalFileNamesConfigurationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(fn ($id) => $id . '_translated');

        $this->session = $this->createMock(Session::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
        $this->listener = new OriginalFileNamesConfigurationListener(
            $this->requestStack,
            $translator
        );
    }

    public function testAfterUpdateDoesNothingForNonOriginalFileNamesEnabledConfig(): void
    {
        $this->session->expects(self::never())
            ->method('getFlashBag');

        $configUpdateEvent = new ConfigUpdateEvent([
            'oro_test.test' => ['old' => 'Foo', 'new' => 'Bar'],
        ], 'global', 0);
        $this->listener->afterUpdate($configUpdateEvent);
    }

    public function testAfterUpdateAddsMessage(): void
    {
        $flashBag = $this->createMock(FlashBag::class);
        $flashBag->expects(self::once())
            ->method('add')
            ->with(
                'warning',
                'oro.attachment.config.notice.storage_check_space_translated'
            );

        $this->session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(self::once())
            ->method('getSession')
            ->willReturn($this->session);
        $requestMock->expects(self::once())
            ->method('hasSession')
            ->willReturn(true);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $configUpdateEvent = new ConfigUpdateEvent([
            'oro_test.test' => ['old' => 'Foo', 'new' => 'Bar'],
            'oro_attachment.original_file_names_enabled' => ['old' => false, 'new' => true],
        ], 'global', 0);
        $this->listener->afterUpdate($configUpdateEvent);
    }
}
