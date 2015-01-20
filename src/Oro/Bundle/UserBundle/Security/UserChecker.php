<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserChecker as BaseUserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class UserChecker extends BaseUserChecker
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof AdvancedUserInterface) {
            return;
        }

        if (!$user->isAccountNonLocked()) {
            $ex = new LockedException($this->translator->trans('oro.user.security.account_locked.message'));
            $ex->setUser($user);
            throw $ex;
        }

        if (!$user->isEnabled()) {
            $ex = new DisabledException($this->translator->trans('oro.user.security.account_disabled.message'));
            $ex->setUser($user);
            throw $ex;
        }

        if (!$user->isAccountNonExpired()) {
            $ex = new AccountExpiredException($this->translator->trans('oro.user.security.account_expired.message'));
            $ex->setUser($user);
            throw $ex;
        }

        if ($user instanceof User) {
            if ($user->getPasswordChangedAt() != null
                && $user->getLastLogin() != null
                && $user->getPasswordChangedAt() > $user->getLastLogin()
            ) {
                $ex = new PasswordChangedException(
                    $this->translator->trans('oro.user.security.password_changed.message')
                );
                $ex->setUser($user);
                throw $ex;
            }
        }
    }
}
