<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Form\Handler\TagHandlerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;

class UserHandler extends AbstractUserHandler implements TagHandlerInterface
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var string
     */
    protected $platformEmail;

    /**
     * @var DelegatingEngine
     */
    protected $templating;

    /**
     * @var TagManager
     */
    protected $tagManager;

    /**
     * @var BusinessUnitManager
     */
    protected $businessUnitManager;

    /**
     * @param FormInterface    $form
     * @param Request          $request
     * @param UserManager      $manager
     * @param DelegatingEngine $templating
     * @param string           $platformEmail
     * @param \Swift_Mailer    $mailer
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        UserManager $manager,
        DelegatingEngine $templating,
        $platformEmail,
        \Swift_Mailer $mailer = null
    ) {
        $this->form          = $form;
        $this->request       = $request;
        $this->manager       = $manager;
        $this->platformEmail = $platformEmail;
        $this->mailer        = $mailer;
        $this->templating    = $templating;
    }

    /**
     * {@inheritdoc}
     */
    public function process(User $user)
    {
        $this->form->setData($user);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $businessUnits = $this->request->get('businessUnits', array());
                if ($businessUnits) {
                    $businessUnits = array_keys($businessUnits);
                }
                if ($this->businessUnitManager) {
                    $this->businessUnitManager->assignBusinessUnits($user, $businessUnits);
                }
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
            $this->sendInviteMail($user, $this->form->get('plainPassword')->getViewData()['first']);
        }

        // Reloads the user to reset its username. This is needed when the
        // username or password have been changed to avoid issues with the
        // security layer.
        $this->manager->reloadUser($user);
    }

    /**
     * Send invite email to new user
     *
     * @param User $user
     * @param string $plainPassword
     */
    protected function sendInviteMail(User $user, $plainPassword)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('Invite user')
            ->setFrom($this->platformEmail)
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
