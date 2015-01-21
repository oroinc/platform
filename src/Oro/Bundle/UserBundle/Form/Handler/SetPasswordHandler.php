<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\PasswordManager;

class SetPasswordHandler
{
    /** @var Request */
    protected $request;

    /** @var Translator */
    protected $translator;

    /** @var FormInterface */
    protected $form;

    /** @var PasswordManager */
    protected $mailerProcessor;

    /**
     * @param Request             $request
     * @param Translator          $translator
     * @param FormInterface       $form
     * @param PasswordManager     $passwordManager
     */
    public function __construct(
        Request          $request,
        Translator       $translator,
        FormInterface    $form,
        PasswordManager  $passwordManager
    ) {
        $this->request         = $request;
        $this->translator      = $translator;
        $this->form            = $form;
        $this->passwordManager = $passwordManager;
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
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $newPassword = $this->form->get('password')->getData();
                if ($this->passwordManager->changePassword($entity, $newPassword)) {
                    return true;
                } else {
                    if ($this->passwordManager->hasError()) {
                        $error = new FormError($this->passwordManager->getError());
                    } else {
                        $error = new FormError($this->translator->trans('oro.email.handler.unable_to_send_email'));
                    }
                    $this->form->addError($error);
                }
            }
        }

        return false;
    }
}
