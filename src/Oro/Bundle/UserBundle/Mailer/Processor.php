<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class Processor extends BaseProcessor
{
    const TEMPLATE_USER_RESET_PASSWORD          = 'user_reset_password';
    const TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN = 'user_reset_password_as_admin';
    const TEMPLATE_USER_CHANGE_PASSWORD         = 'user_change_password';
    const TEMPLATE_USER_IMPERSONATE             = 'user_impersonate';
    const TEMPLATE_USER_AUTO_DEACTIVATE         = 'auto_deactivate_failed_logins';

    /**
     * @param UserInterface $user
     *
     * @return int
     */
    public function sendChangePasswordEmail(UserInterface $user)
    {
        return $this->getEmailTemplateAndSendEmail(
            $user,
            static::TEMPLATE_USER_CHANGE_PASSWORD,
            ['entity' => $user, 'plainPassword' => $user->getPlainPassword()]
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return int
     */
    public function sendResetPasswordEmail(UserInterface $user)
    {
        return $this->getEmailTemplateAndSendEmail(
            $user,
            static::TEMPLATE_USER_RESET_PASSWORD,
            ['entity' => $user]
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return int
     */
    public function sendResetPasswordAsAdminEmail(UserInterface $user)
    {
        return $this->getEmailTemplateAndSendEmail(
            $user,
            static::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
            ['entity' => $user]
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return int
     */
    public function sendImpersonateEmail(UserInterface $user)
    {
        return $this->getEmailTemplateAndSendEmail(
            $user,
            static::TEMPLATE_USER_IMPERSONATE,
            ['entity' => $user]
        );
    }

    /**
     * @param UserInterface $user
     * @param int $limit The exceed limit
     *
     * @return int
     */
    public function sendAutoDeactivateEmail(UserInterface $user, $limit)
    {
        $emailTemplate = $this->findEmailTemplateByName(static::TEMPLATE_USER_AUTO_DEACTIVATE);

        return $this->sendEmail(
            $user,
            $this->renderer->compileMessage(
                $emailTemplate,
                ['entity' => $user, 'limit' => $limit]
            ),
            $this->getEmailTemplateType($emailTemplate),
            $this->getUserEmails(User::ROLE_ADMINISTRATOR)
        );
    }

    /**
     * @param string $roleName
     *
     * @return string[]
     */
    protected function getUserEmails($roleName)
    {
        $users = $this->managerRegistry
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->findActiveUsersByRole($roleName);

        return array_map(
            function ($user) {
                return $user->getEmail();
            },
            $users
        );
    }
}
