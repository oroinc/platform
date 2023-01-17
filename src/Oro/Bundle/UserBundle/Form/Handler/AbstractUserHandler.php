<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Abstract handler for user form.
 */
abstract class AbstractUserHandler
{
    use RequestHandlerTrait;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var UserManager
     */
    protected $manager;

    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        UserManager $manager
    ) {
        $this->form    = $form;
        $this->requestStack = $requestStack;
        $this->manager = $manager;
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

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);

            if ($this->form->isValid()) {
                $this->onSuccess($user);

                return true;
            }

            $this->onFail($user);
        }

        return false;
    }

    /**
     * "Fail" form handler
     */
    protected function onFail(User $user): void
    {
    }

    /**
     * "Success" form handler
     */
    abstract protected function onSuccess(User $user);
}
