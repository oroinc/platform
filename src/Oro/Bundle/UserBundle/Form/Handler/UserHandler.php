<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handle User forms
 */
class UserHandler extends AbstractUserHandler
{
    public const INVITE_USER_TEMPLATE = 'invite_user';

    /** @var DelegatingEngine */
    protected $templating;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var FlashBagInterface */
    protected $flashBag;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var LoggerInterface */
    protected $logger;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var ConfigManager */
    protected $userConfigManager;

    /** @var TemplateEmailManager */
    private $templateEmailManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param UserManager $manager
     * @param ConfigManager $userConfigManager
     * @param DelegatingEngine $templating
     * @param \Swift_Mailer $mailer
     * @param FlashBagInterface $flashBag
     * @param TranslatorInterface $translator
     * @param LoggerInterface $logger
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        UserManager $manager,
        ConfigManager $userConfigManager = null,
        DelegatingEngine $templating = null,
        \Swift_Mailer $mailer = null,
        FlashBagInterface $flashBag = null,
        TranslatorInterface $translator = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($form, $request, $manager);

        $this->userConfigManager = $userConfigManager;
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @param TemplateEmailManager $templateEmailManager
     */
    public function setTemplateEmailManager(TemplateEmailManager $templateEmailManager)
    {
        $this->templateEmailManager = $templateEmailManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(User $user)
    {
        $isUpdated = false;
        $this->form->setData($user);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($user);

                $isUpdated = true;
            }
        }

        // Reloads the user to reset its username. This is needed when the
        // username or password have been changed to avoid issues with the
        // security layer.
        if ($user->getId()) {
            $this->manager->reloadUser($user);
        }

        return $isUpdated;
    }

    /**
     * @param BusinessUnitManager $businessUnitManager
     */
    public function setBusinessUnitManager(BusinessUnitManager $businessUnitManager)
    {
        $this->businessUnitManager = $businessUnitManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function onSuccess(User $user)
    {
        if (null === $user->getAuthStatus()) {
            $this->manager->setAuthStatus($user, UserManager::STATUS_ACTIVE);
        }

        $isNewUser = !$user->getId();
        $plainPassword = $this->handleNewUser($user);

        $this->manager->updateUser($user);

        if ($isNewUser && $this->form->has('inviteUser') && $this->form->get('inviteUser')->getViewData()) {
            try {
                $this->sendInviteMail($user, $plainPassword);
            } catch (\Exception $ex) {
                $this->logger->error('Invitation email sending failed.', ['exception' => $ex]);
                $this->flashBag->add(
                    'warning',
                    $this->translator->trans('oro.user.controller.invite.fail.message')
                );
            }
        }
    }

    /**
     * @param User $user
     * @return string
     */
    protected function handleNewUser(User $user)
    {
        if ($user->getId()) {
            return '';
        }

        $sendPasswordInEmail = $this->userConfigManager &&
            $this->userConfigManager->get('oro_user.send_password_in_invitation_email');

        if (!$sendPasswordInEmail && !$user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        if ($this->form->has('passwordGenerate') && $this->form->get('passwordGenerate')->getData()) {
            $user->setPlainPassword($this->manager->generatePassword(10));
        }

        return $sendPasswordInEmail ? $user->getPlainPassword() : '';
    }

    /**
     * Send invite email to new user
     *
     * @param User $user
     * @param string $plainPassword
     *
     * @throws \RuntimeException
     */
    protected function sendInviteMail(User $user, $plainPassword)
    {
        if (in_array(null, [$this->userConfigManager, $this->mailer, $this->templating], true)) {
            throw new \RuntimeException('Unable to send invitation email, unmet dependencies detected.');
        }
        $senderEmail = $this->userConfigManager->get('oro_notification.email_notification_sender_email');
        $senderName = $this->userConfigManager->get('oro_notification.email_notification_sender_name');

        if ($this->templateEmailManager) {
            $this->templateEmailManager->sendTemplateEmail(
                From::emailAddress($senderEmail, $senderName),
                [$user],
                new EmailTemplateCriteria(self::INVITE_USER_TEMPLATE, User::class),
                ['user' => $user, 'password' => $plainPassword]
            );
        } else {
            $this->sendEmail($senderEmail, $senderName, $user, $plainPassword);
        }
    }

    /**
     * @param string $senderEmail
     * @param null|string $senderName
     * @param User $user
     * @param string $plainPassword
     * @deprecated since 2.6, will be removed in 3.1
     */
    private function sendEmail(string $senderEmail, ?string $senderName, User $user, string $plainPassword)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('Invite user')
            ->setFrom($senderEmail, $senderName)
            ->setTo($user->getEmail())
            ->setBody(
                $this->templating->render(
                    'OroUserBundle:Mail:invite.html.twig',
                    ['user' => $user, 'password' => $plainPassword]
                ),
                'text/html'
            );
        $this->mailer->send($message);
    }
}
