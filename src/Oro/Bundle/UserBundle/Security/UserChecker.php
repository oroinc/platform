<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\User\UserChecker as BaseUserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;

class UserChecker extends BaseUserChecker
{
    /**
     * @var ServiceLink
     */
    protected $securityContextLink;

    /**
     * @var FlashBagInterface
     */
    protected $flashBag;

    public function __construct(ServiceLink $securityContextLink, FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
        $this->securityContextLink = $securityContextLink;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPreAuth(UserInterface $user)
    {
        parent::checkPreAuth($user);

        if ($user instanceof User && !is_null($this->securityContextLink->getService()->getToken())) {
            if ($user->getPasswordChangedAt() != null
                && $user->getLastLogin() != null
                && $user->getPasswordChangedAt() > $user->getLastLogin()
            ) {
                $this->flashBag->add('error', 'oro.user.security.password_changed.message');
                $exception = new PasswordChangedException('oro.user.security.password_changed.message');
                $exception->setUser($user);
                throw $exception;
            }
        }
    }
}
