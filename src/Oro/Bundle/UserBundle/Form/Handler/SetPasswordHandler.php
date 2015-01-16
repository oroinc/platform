<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;


class SetPasswordHandler
{
    /** @var Request */
    protected $request;

    /** @var  UserManager */
    protected $userManager;

    /** @var FormInterface */
    protected $form;

    /**
     * @param FormInterface       $form
     * @param Request             $request
     * @param UserManager         $userManager
     */
    public function __construct(
        Request         $request,
        UserManager     $userManager,
        FormInterface   $form
    ) {
        $this->request      = $request;
        $this->userManager  = $userManager;
        $this->form         = $form;
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
                $entity->setPlainPassword($this->form->get('password')->getData());
                $this->userManager->updateUser($entity);

                return true;
            }
        }

        return false;
    }
}
