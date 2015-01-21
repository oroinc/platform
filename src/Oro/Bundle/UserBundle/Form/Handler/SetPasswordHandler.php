<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Provider\SendmailProvider;

class SetPasswordHandler
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /** @var Request */
    protected $request;

    /** @var  UserManager */
    protected $userManager;

    /** @var FormInterface */
    protected $form;

    /** @var SendmailProvider */
    protected $sendmailProvider;

    /**
     * @param ObjectManager       $objectManager
     * @param Request             $request
     * @param UserManager         $userManager
     * @param FormInterface       $form
     * @param SendmailProvider     $sendmailProvider
     */
    public function __construct(
        ObjectManager    $objectManager,
        Request          $request,
        UserManager      $userManager,
        FormInterface    $form,
        SendmailProvider  $sendmailProvider
    ) {
        $this->objectManager = $objectManager;
        $this->request       = $request;
        $this->userManager   = $userManager;
        $this->form          = $form;
        $this->sendmailProvider = $sendmailProvider;
    }

    /**
     * Process form
     *
     * @param  User $entity
     *
     * @return bool  True on successful processing, false otherwise
     */
    public function process(User $entity)
    {
        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $this->sendmailProvider->checkSendmailConfigured();

                $entity->setPlainPassword($this->form->get('password')->getData());
                $this->userManager->updateUser($entity);
                $plainPassword = $this->form->get('password')->getData();

                $emailTemplate = $this->objectManager->getRepository('OroEmailBundle:EmailTemplate')
                    ->findByName('user_change_password');

                $this->sendmailProvider->sendEmail($entity, $emailTemplate, ['plainPassword' => $plainPassword]);
                return true;
            }
        }

        return false;
    }
}
