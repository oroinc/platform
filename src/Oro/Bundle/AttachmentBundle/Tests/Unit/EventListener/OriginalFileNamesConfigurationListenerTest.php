<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\EventListener\OriginalFileNamesConfigurationListener;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class OriginalFileNamesConfigurationListenerTest extends \PHPUnit\Framework\TestCase
{
    private Session|\PHPUnit\Framework\MockObject\MockObject $session;

    private OriginalFileNamesConfigurationListener $listener;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(fn ($id) => $id . '_translated');

        $this->session = $this->createMock(Session::class);

        $this->listener = new OriginalFileNamesConfigurationListener(
            $this->session,
            $translator
        );
    }

    public function testAfterUpdateDoesNothingForNonOriginalFileNamesEnabledConfig(): void
    {
        $this->session->expects(self::never())
            ->method('getFlashBag');

        $configUpdateEvent = new ConfigUpdateEvent([
            'oro_test.test' => ['old' => 'Foo', 'new' => 'Bar'],
        ]);
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

        $configUpdateEvent = new ConfigUpdateEvent([
            'oro_test.test' => ['old' => 'Foo', 'new' => 'Bar'],
            'oro_attachment.original_file_names_enabled' => ['old' => false, 'new' => true],
        ]);
        $this->listener->afterUpdate($configUpdateEvent);
    }
}
