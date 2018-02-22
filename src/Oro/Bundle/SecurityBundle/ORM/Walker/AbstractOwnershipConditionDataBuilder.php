<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Abstract class for ownership condition data builders
 */
abstract class AbstractOwnershipConditionDataBuilder implements AclConditionDataBuilderInterface
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var OwnershipMetadataProviderInterface */
    protected $metadataProvider;

    /**
     * @param string $permissions
     * @param string $entityType
     *
     * @return bool
     */
    protected function isEntityGranted($permissions, $entityType)
    {
        return $this->authorizationChecker->isGranted(
            $permissions,
            new ObjectIdentity('entity', $entityType)
        );
    }

    /**
     * Gets the logged user
     *
     * @return null|mixed
     */
    public function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        if (!is_object($user) || !is_a($user, $this->metadataProvider->getUserClass())) {
            return null;
        }

        return $user;
    }
}
