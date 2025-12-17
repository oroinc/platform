<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Unit\Event;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;

class PasswordChangeEventTest extends \PHPUnit\Framework\TestCase
{
    private User $user;
    private PasswordChangeEvent $event;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->event = new PasswordChangeEvent($this->user);
    }

    public function testGetUser(): void
    {
        self::assertSame($this->user, $this->event->getUser());
    }

    public function testIsAllowedDefaultValue(): void
    {
        self::assertTrue($this->event->isAllowed());
    }

    public function testGetNotAllowedMessageDefaultValue(): void
    {
        self::assertNull($this->event->getNotAllowedMessage());
    }

    public function testGetNotAllowedLogMessageDefaultValue(): void
    {
        self::assertNull($this->event->getNotAllowedLogMessage());
    }

    public function testDisablePasswordChangeWithMessageOnly(): void
    {
        $message = 'Password change is not allowed';

        $this->event->disablePasswordChange($message);

        self::assertFalse($this->event->isAllowed());
        self::assertSame($message, $this->event->getNotAllowedMessage());
        self::assertNull($this->event->getNotAllowedLogMessage());
    }

    public function testDisablePasswordChangeWithMessageAndLogMessage(): void
    {
        $message = 'Password change is not allowed';
        $logMessage = 'User account is managed by external system';

        $this->event->disablePasswordChange($message, $logMessage);

        self::assertFalse($this->event->isAllowed());
        self::assertSame($message, $this->event->getNotAllowedMessage());
        self::assertSame($logMessage, $this->event->getNotAllowedLogMessage());
    }

    public function testEventConstants(): void
    {
        self::assertSame('oro_user.before_password_change', PasswordChangeEvent::BEFORE_PASSWORD_CHANGE);
        self::assertSame('oro_user.before_password_reset', PasswordChangeEvent::BEFORE_PASSWORD_RESET);
    }
}
