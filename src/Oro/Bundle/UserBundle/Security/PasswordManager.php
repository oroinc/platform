<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;

/**
 * Class PasswordManager
 *
 * @package Oro\Bundle\UserBundle\Security
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PasswordManager
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var Processor
     */
    protected $mailProcessor;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @var string
     */
    protected $error;

    /**
     * @param UserManager $userManager
     * @param Processor   $processor
     * @param             $ttl
     */
    public function __construct(
        UserManager $userManager,
        Processor $processor,
        $ttl
    ) {
        $this->userManager   = $userManager;
        $this->mailProcessor = $processor;
        $this->ttl           = $ttl;
    }

    /**
     * Sends reset password email to user
     *
     * @param User $user
     * @param bool $asAdmin
     *
     * @return bool
     */
    public function resetPassword(User $user, $asAdmin = false)
    {
        $this->setError(null);

        if (!$asAdmin) {
            if ($user->isPasswordRequestNonExpired($this->ttl)) {
                $this->setError('oro.user.password.reset.ttl_already_requested.message');

                return false;
            }
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        if ($asAdmin) {
            $isEmailSent = $this->mailProcessor->sendResetPasswordAsAdminEmail($user);
        } else {
            $isEmailSent = $this->mailProcessor->sendResetPasswordEmail($user);
        }

        if ($isEmailSent) {
            $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $this->userManager->updateUser($user);

            return true;
        } else {
            if ($this->mailProcessor->hasError()) {
                $this->setError($this->mailProcessor->getError());
            }

            return false;
        }
    }

    /**
     * @param User $user
     * @param      $password
     *
     * @return bool
     */
    public function changePassword(User $user, $password)
    {
        $user->setPlainPassword($password);
        $user->setPasswordChangedAt(new \DateTime());

        if ($this->mailProcessor->sendChangePasswordEmail($user)) {
            $this->userManager->updateUser($user);

            return true;
        } else {
            if ($this->mailProcessor->hasError()) {
                $this->setError($this->mailProcessor->getError());
            }

            return false;
        }
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return !is_null($this->error);
    }

    /**
     * @param string $error
     */
    protected function setError($error)
    {
        $this->error = $error;
    }
}
