<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserChecker as BaseUserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;

class UserChecker extends BaseUserChecker
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPreAuth(UserInterface $user)
    {
        parent::checkPreAuth($user);

        if ($user instanceof User && !is_null($this->container->get('security.context')->getToken())) {
            if ($user->getPasswordChangedAt() != null
                && $user->getLastLogin() != null
                && $user->getPasswordChangedAt() > $user->getLastLogin()
            ) {
                $this->container->get('session.flash_bag')->add('error', 'oro.user.security.password_changed.message');
                $exception = new PasswordChangedException('oro.user.security.password_changed.message');
                $exception->setUser($user);
                throw $exception;
            }
        }
    }
}
