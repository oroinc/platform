<?php

namespace Oro\Bundle\SearchBundle\Security;

use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityProvider
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var EntitySecurityMetadataProvider */
    protected $entitySecurityMetadataProvider;

    /**
     * @param AuthorizationCheckerInterface  $authorizationChecker
     * @param EntitySecurityMetadataProvider $entitySecurityMetadataProvider
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntitySecurityMetadataProvider $entitySecurityMetadataProvider
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->entitySecurityMetadataProvider = $entitySecurityMetadataProvider;
    }

    /**
     * Checks whether an entity is protected.
     *
     * @param  string $entityClass
     * @return bool
     */
    public function isProtectedEntity($entityClass)
    {
        return $this->entitySecurityMetadataProvider->isProtectedEntity($entityClass);
    }

    /**
     * Checks if an access to a resource is granted to the caller
     *
     * @param  string $attribute
     * @param  string $objectString
     * @return bool
     */
    public function isGranted($attribute, $objectString)
    {
        return $this->authorizationChecker->isGranted($attribute, $objectString);
    }
}
