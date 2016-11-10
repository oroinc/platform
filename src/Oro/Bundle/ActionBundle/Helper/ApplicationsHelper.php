<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;

class ApplicationsHelper implements ApplicationsHelperInterface
{
    use ApplicationsHelperTrait, RouteHelperTrait;

    const DEFAULT_APPLICATION = 'default';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentApplication()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof User ? self::DEFAULT_APPLICATION : null;
    }
}
