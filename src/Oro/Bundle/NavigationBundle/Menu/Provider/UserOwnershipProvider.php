<?php

namespace Oro\Bundle\NavigationBundle\Menu\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;

class UserOwnershipProvider extends AbstractOwnershipProvider
{
    const TYPE = 'user';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param ManagerRegistry       $managerRegistry
     * @param string                $entityClass
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ManagerRegistry $managerRegistry, $entityClass, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        parent::__construct($managerRegistry, $entityClass);
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
