<?php

namespace Oro\Bundle\NavigationBundle\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;

class UserOwnershipProvider implements OwnershipProviderInterface
{
    const TYPE = 'user';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof User && $user->getId()) {
                return $user->getId();
            }
        }

        return null;
    }
}
