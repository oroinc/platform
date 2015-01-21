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
        $this->reset();

        if (!$asAdmin) {
            if ($user->isPasswordRequestNonExpired($this->ttl)) {
                $this->addError('oro.user.password.reset.ttl_already_requested.message');

                return false;
            }
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        // todo move to processor
        $message = $this->createMessage($user);
        $this->mailer->send($message);

        $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->userManager->updateUser($user);

        return true;
    }

    public function changePassword(User $user, $password)
    {
        $user->setPlainPassword($password);
        $user->setPasswordChangedAt(new \DateTime());
        $this->userManager->updateUser($user);

        $this->mailProcessor->sendChangePasswordEmail($user);
    }

    /**
     * Resets password manager
     */
    public function reset()
    {
        $this->error = null;
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
        return $this->error != null;
    }

    /**
     * @param string $error
     */
    protected function addError($error)
    {
        $this->error = $error;
    }

    // todo move to processor method
    /**
     * @param User $user
     *
     * @return \Swift_Message
     */
    protected function createMessage(User $user)
    {
        $senderEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $senderName  = $this->configManager->get('oro_notification.email_notification_sender_name');

        return \Swift_Message::newInstance()
            ->setSubject($this->translator->trans('Reset password'))
            ->setFrom($senderEmail, $senderName)
            ->setTo($user->getEmail())
            ->setBody(
                $this->templating->render('OroUserBundle:Mail:reset.html.twig', ['user' => $user]),
                'text/html'
            );
    }
}
