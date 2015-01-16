<?php

namespace Oro\Bundle\UserBundle\Security;

use BeSimple\SoapCommon\Type\KeyValue\DateTime;
use Swift_Mailer;

use Symfony\Component\Templating\DelegatingEngine;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\UserManager;

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
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var DelegatingEngine
     */
    protected $templating;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @var string
     */
    protected $error;

    /**
     * @param ConfigManager    $configManager
     * @param Translator       $translator
     * @param DelegatingEngine $templating
     * @param Swift_Mailer     $mailer
     * @param UserManager      $userManager
     * @param int              $ttl
     */
    public function __construct(
        ConfigManager $configManager,
        Translator $translator,
        DelegatingEngine $templating,
        \Swift_Mailer $mailer,
        UserManager $userManager,
        $ttl
    ) {
        $this->configManager = $configManager;
        $this->translator    = $translator;
        $this->templating    = $templating;
        $this->mailer        = $mailer;
        $this->userManager   = $userManager;
        $this->ttl           = $ttl;
    }

    /**
     * @param User $user
     * @param boolean $check
     *
     * @return bool
     */
    public function setResetPasswordEmail(User $user, $check = true)
    {
        $this->reset();

        if ($check) {
            if ($user->isPasswordRequestNonExpired($this->ttl)) {
                $this->addError(
                    $this->translator->trans(
                        'oro.user.password.reset.ttl_already_requested.message'
                    )
                );

                return false;
            }
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        $message = $this->createMessage($user);
        $this->mailer->send($message);

        $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->userManager->updateUser($user);

        return true;
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
