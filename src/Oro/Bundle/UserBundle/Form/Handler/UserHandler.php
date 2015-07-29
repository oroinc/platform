<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Manager\UserConfigManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Form\Handler\TagHandlerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * Class UserHandler
 *
 * @package Oro\Bundle\UserBundle\Form\Handler
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class UserHandler extends AbstractUserHandler implements TagHandlerInterface
{
    /** @var DelegatingEngine */
    protected $templating;

    /** ConfigManager */
    protected $cm;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var FlashBagInterface */
    protected $flashBag;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var LoggerInterface */
    protected $logger;

    /** @var TagManager */
    protected $tagManager;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var UserConfigManager */
    protected $userConfigManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param UserManager $manager
     * @param UserConfigManager $userConfigManager
     * @param ConfigManager $cm
     * @param DelegatingEngine $templating
     * @param \Swift_Mailer $mailer
     * @param FlashBagInterface $flashBag
     * @param TranslatorInterface $translator
     * @param LoggerInterface $logger
     * @param ServiceLink $serviceLink
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        UserManager $manager,
        UserConfigManager $userConfigManager = null,
        ConfigManager $cm = null,
        DelegatingEngine $templating = null,
        \Swift_Mailer $mailer = null,
        FlashBagInterface $flashBag = null,
        TranslatorInterface $translator = null,
        LoggerInterface $logger = null,
        ServiceLink $serviceLink = null
    ) {
        parent::__construct($form, $request, $manager);
        $this->userConfigManager = $userConfigManager;
        $this->templating = $templating;
        $this->cm = $cm;
        $this->mailer = $mailer;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->logger = $logger;
        if ($serviceLink !== null) {
            $this->securityFacade = $serviceLink->getService();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(User $user)
    {
        if ($this->securityFacade !== null && $user->getCurrentOrganization() === null) {
            $user->setCurrentOrganization($this->securityFacade->getOrganization());
        }
        $this->form->setData($user);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($user);

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setTagManager(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
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
        $this->manager->updateUser($user);
        $this->tagManager->saveTagging($user);

        if ($this->form->has('inviteUser')
            && $this->form->has('plainPassword')
            && $this->form->get('inviteUser')->getViewData()
            && $this->form->get('plainPassword')->getViewData()
        ) {
            try {
                $this->sendInviteMail($user, $this->form->get('plainPassword')->getViewData()['first']);
            } catch (\Exception $ex) {
                $this->logger->error('Invitation email sending failed.', ['exception' => $ex]);
                $this->flashBag->add(
                    'warning',
                    $this->translator->trans('oro.user.controller.invite.fail.message')
                );
            }
        }

        // Reloads the user to reset its username. This is needed when the
        // username or password have been changed to avoid issues with the
        // security layer.
        $this->manager->reloadUser($user);
        if ($this->form->has('signature')) {
            $this->userConfigManager->saveUserConfigSignature($this->form->get('signature')->getData());
        }
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
        if (in_array(null, [$this->cm, $this->mailer, $this->templating], true)) {
            throw new \RuntimeException('Unable to send invitation email, unmet dependencies detected.');
        }
        $senderEmail = $this->cm->get('oro_notification.email_notification_sender_email');
        $senderName = $this->cm->get('oro_notification.email_notification_sender_name');

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
