<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Send notification template emails to user.
 */
class Processor
{
    const TEMPLATE_USER_RESET_PASSWORD          = 'user_reset_password';
    const TEMPLATE_USER_CHANGE_PASSWORD         = 'user_change_password';
    const TEMPLATE_FORCE_RESET_PASSWORD         = 'force_reset_password';
    const TEMPLATE_USER_IMPERSONATE             = 'user_impersonate';

    /**
     * @var UserTemplateEmailSender
     */
    private $userTemplateEmailSender;

    public function __construct(UserTemplateEmailSender $userTemplateEmailSender)
    {
        $this->userTemplateEmailSender = $userTemplateEmailSender;
    }

    public function sendChangePasswordEmail(UserInterface $user): int
    {
        return $this->userTemplateEmailSender->sendUserTemplateEmail(
            $user,
            static::TEMPLATE_USER_CHANGE_PASSWORD,
            ['entity' => $user, 'plainPassword' => $user->getPlainPassword()]
        );
    }

    public function sendResetPasswordEmail(UserInterface $user): int
    {
        return $this->userTemplateEmailSender->sendUserTemplateEmail(
            $user,
            static::TEMPLATE_USER_RESET_PASSWORD,
            ['entity' => $user]
        );
    }

    public function sendForcedResetPasswordAsAdminEmail(UserInterface $user): int
    {
        return $this->userTemplateEmailSender->sendUserTemplateEmail(
            $user,
            static::TEMPLATE_FORCE_RESET_PASSWORD,
            ['entity' => $user]
        );
    }

    public function sendImpersonateEmail(UserInterface $user): int
    {
        return $this->userTemplateEmailSender->sendUserTemplateEmail(
            $user,
            static::TEMPLATE_USER_IMPERSONATE,
            ['entity' => $user]
        );
    }
}
