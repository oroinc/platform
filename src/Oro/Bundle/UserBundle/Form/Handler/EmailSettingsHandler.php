<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\UserBundle\Entity\User;

class EmailSettingsHandler implements FormAwareInterface
{
    const FORM = 'oro_user_emailsettings';

    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * Process form.
     *
     * @param User $user
     * @return bool True on success.
     */
    public function process(User $user)
    {
        $this->form->setData($user);
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->handleRequest($this->request);
            if ($this->form->isValid()) {
                $this->onSuccess();
                return true;
            }
        }

        return false;
    }

    /**
     * Form validated and can be processed.
     */
    protected function onSuccess()
    {
        /** @var User $user */
        $user = $this->form->getData();
        $this->manager->persist($user);
        $this->manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->form;
    }
}
