<?php

namespace Oro\Bundle\SecurityBundle\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides a set of methods to simplify checking access to controller actions.
 */
class ClassAuthorizationChecker
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private ObjectIdentityFactory $objectIdentityFactory;
    private AclAttributeProvider $aclAttributeProvider;
    private LoggerInterface $logger;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ObjectIdentityFactory $objectIdentityFactory,
        AclAttributeProvider $aclAttributeProvider,
        LoggerInterface $logger
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->objectIdentityFactory = $objectIdentityFactory;
        $this->aclAttributeProvider = $aclAttributeProvider;
        $this->logger = $logger;
    }

    /**
     * Checks if an access to the given method of the given class is granted for the current authentication token.
     */
    public function isClassMethodGranted(string $class, string $method): bool
    {
        $isGranted = true;

        // check method level ACL
        $attribute = $this->aclAttributeProvider->findAttribute($class, $method);
        if (null !== $attribute) {
            $this->logger->debug(
                sprintf('Check an access using "%s" ACL attribute.', $attribute->getId())
            );
            $isGranted = $this->authorizationChecker->isGranted(
                $attribute->getPermission(),
                $this->objectIdentityFactory->get($attribute)
            );
        }

        // check class level ACL
        if ($isGranted && (null === $attribute || !$attribute->getIgnoreClassAcl())) {
            $attribute = $this->aclAttributeProvider->findAttribute($class);
            if (null !== $attribute) {
                $this->logger->debug(
                    sprintf('Check an access using "%s" ACL attribute.', $attribute->getId())
                );
                $isGranted = $this->authorizationChecker->isGranted(
                    $attribute->getPermission(),
                    $this->objectIdentityFactory->get($attribute)
                );
            }
        }

        return $isGranted;
    }

    /**
     * Gets ACL attribute that is bound to the given method of the given class.
     */
    public function getClassMethodAttribute(string $class, string $method): ?AclAttribute
    {
        return $this->aclAttributeProvider->findAttribute($class, $method);
    }
}
