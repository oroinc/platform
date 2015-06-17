<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Oro\Bundle\UserBundle\Entity\UserInterface;

class Processor extends BaseProcessor
{
    const TEMPLATE_USER_RESET_PASSWORD          = 'user_reset_password';
    const TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN = 'user_reset_password_as_admin';
    const TEMPLATE_USER_CHANGE_PASSWORD         = 'user_change_password';

    /**
     * @param UserInterface $user
     *
     * @return bool
     */
    public function sendChangePasswordEmail(UserInterface $user)
    {
        return $this->getEmailTemplateAndSendEmail(
            $user,
            $this->getUserChangePasswordTemplate(),
            ['entity' => $user, 'plainPassword' => $user->getPlainPassword()]
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return bool
     */
    public function sendResetPasswordEmail(UserInterface $user)
    {
        return $this->getEmailTemplateAndSendEmail(
            $user,
            $this->getUserResetPasswordTemplate(),
            ['entity' => $user]
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return bool
     */
    public function sendResetPasswordAsAdminEmail(UserInterface $user)
    {
        return $this->getEmailTemplateAndSendEmail(
            $user,
            $this->getUserResetPasswordAsAdminTemplate(),
            ['entity' => $user]
        );
    }

    /**
     * @return string
     */
    protected function getUserChangePasswordTemplate()
    {
        return static::TEMPLATE_USER_CHANGE_PASSWORD;
    }

    /**
     * @return string
     */
    protected function getUserResetPasswordTemplate()
    {
        return static::TEMPLATE_USER_RESET_PASSWORD;
    }

    /**
     * @return string
     */
    protected function getUserResetPasswordAsAdminTemplate()
    {
        return static::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN;
    }
}
