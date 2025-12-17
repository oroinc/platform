<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Event;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before password change/reset operations to allow extensions to prevent the operation.
 */
class PasswordChangeEvent extends Event
{
    public const string BEFORE_PASSWORD_CHANGE = 'oro_user.before_password_change';
    public const string BEFORE_PASSWORD_RESET = 'oro_user.before_password_reset';

    protected User $user;
    protected bool $allowed = true;
    protected ?string $notAllowedMessage = null;
    protected ?string $notAllowedLogMessage = null;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function disablePasswordChange(string $message, ?string $logMessage = null): void
    {
        $this->allowed = false;
        $this->notAllowedMessage = $message;
        $this->notAllowedLogMessage = $logMessage;
    }

    public function getNotAllowedMessage(): ?string
    {
        return $this->notAllowedMessage;
    }

    public function getNotAllowedLogMessage(): ?string
    {
        return $this->notAllowedLogMessage;
    }
}
