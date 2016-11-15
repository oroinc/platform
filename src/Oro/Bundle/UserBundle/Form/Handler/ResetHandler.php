<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class ResetHandler extends AbstractUserHandler
{
    /** @var EnumValueProvider */
    private $enumValueProvider;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param UserManager $manager
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        UserManager $manager,
        EnumValueProvider $enumValueProvider
    ) {
        parent::__construct($form, $request, $manager);

        $this->enumValueProvider = $enumValueProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function onSuccess(User $user)
    {
        $user
            ->setPlainPassword($this->form->getData()->getPlainPassword())
            ->setConfirmationToken(null)
            ->setPasswordRequestedAt(null)
            ->setEnabled(true);

        $user->setAuthStatus($this->enumValueProvider->getEnumValueByCode('auth_status', 'available'));

        $this->manager->updateUser($user);
    }
}
