<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

abstract class AbstractUserHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UserManager
     */
    protected $manager;

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
     * @param FormInterface $form
     * @param Request $request
     * @param UserManager $manager
     * @param DelegatingEngine $templating
     * @param string $platformEmail
     * @param \Swift_Mailer $mailer
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
     * Process form
     *
     * @param  User $user
     * @return bool True on successfull processing, false otherwise
     */
    public function process(User $user)
    {
        $this->form->setData($user);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($user);

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param User $user
     */
    abstract protected function onSuccess(User $user);
}
