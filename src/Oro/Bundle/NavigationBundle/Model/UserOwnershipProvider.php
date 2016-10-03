<?php

namespace Oro\Bundle\NavigationBundle\Model;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;

class UserOwnershipProvider extends AbstractOwnershipProvider
{
    const TYPE = 'user';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param EntityRepository      $repository
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(EntityRepository $repository, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        parent::__construct($repository);
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
