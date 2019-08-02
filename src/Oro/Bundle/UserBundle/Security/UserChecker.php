<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Exception\CredentialsResetException;
use Oro\Bundle\UserBundle\Exception\EmptyOwnerException;
use Oro\Bundle\UserBundle\Exception\OrganizationException;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserChecker as BaseUserChecker;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks User state during authentication.
 */
class UserChecker extends BaseUserChecker
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var FlashBagInterface */
    protected $flashBag;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param FlashBagInterface     $flashBag
     * @param TranslatorInterface   $translator
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPostAuth(UserInterface $user)
    {
        parent::checkPostAuth($user);

        if ($user instanceof User) {
            if (null !== $user->getAuthStatus() && !$this->hasOrganization($user)) {
                $exception = new OrganizationException();
                $exception->setUser($user);

                throw $exception;
            }
            if (!$user->getOwner()) {
                $exception = new EmptyOwnerException('The user does not have an owner.');
                $exception->setUser($user);

                throw $exception;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkPreAuth(UserInterface $user)
    {
        parent::checkPreAuth($user);

        if (!$user instanceof User) {
            return;
        }

        if (null !== $this->tokenStorage->getToken()
            && null !== $user->getPasswordChangedAt()
            && null !== $user->getLastLogin()
            && $user->getPasswordChangedAt() > $user->getLastLogin()
        ) {
            $this->flashBag->add(
                'error',
                $this->translator->trans('oro.user.security.password_changed.message')
            );

            $exception = new PasswordChangedException('Invalid password.');
            $exception->setUser($user);

            throw $exception;
        }

        if ($user->getAuthStatus() && $user->getAuthStatus()->getId() === UserManager::STATUS_EXPIRED) {
            $exception = new CredentialsResetException('Password reset.');
            $exception->setUser($user);

            throw $exception;
        }
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    protected function hasOrganization(User $user)
    {
        return $user->getOrganizations(true)->count() > 0;
    }
}
