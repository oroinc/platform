<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Send notification template emails to user.
 */
class Processor
{
    public const TEMPLATE_USER_RESET_PASSWORD          = 'user_reset_password';
    public const TEMPLATE_USER_CHANGE_PASSWORD         = 'user_change_password';
    public const TEMPLATE_FORCE_RESET_PASSWORD         = 'force_reset_password';
    public const TEMPLATE_USER_IMPERSONATE             = 'user_impersonate';

    public const string CONFIRMATION_TOKEN_TEMPLATE_PARAM = 'confirmationToken';

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
            [
                'entity' => $user,
                static::CONFIRMATION_TOKEN_TEMPLATE_PARAM => $this->getConfirmationToken($user),
            ]
        );
    }

    public function sendForcedResetPasswordAsAdminEmail(UserInterface $user): int
    {
        return $this->userTemplateEmailSender->sendUserTemplateEmail(
            $user,
            static::TEMPLATE_FORCE_RESET_PASSWORD,
            [
                'entity' => $user,
                static::CONFIRMATION_TOKEN_TEMPLATE_PARAM => $this->getConfirmationToken($user),
            ]
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

    /**
     * Returns the confirmation token to render in the email template, or null when there is none, so that the
     * "default" filter of the template takes over instead of an empty route parameter.
     */
    private function getConfirmationToken(UserInterface $user): ?string
    {
        if (!$user instanceof AbstractUser) {
            return null;
        }

        return $user->getConfirmationToken() ?: null;
    }
}
