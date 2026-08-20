<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Async\Topic;

/**
 * Processes a forgot password request submitted in the back-office.
 */
class UserPasswordResetRequestTopic extends AbstractPasswordResetRequestTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.user.password_reset_request';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Sends the reset password email if the submitted username or email belongs to a user account.';
    }
}
