<?php

namespace Oro\Bundle\SecurityBundle\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides a set of methods to simplify checking access to controller actions.
 */
class ClassAuthorizationChecker
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private ObjectIdentityFactory $objectIdentityFactory;
    private AclAnnotationProvider $aclAnnotationProvider;
    private LoggerInterface $logger;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ObjectIdentityFactory $objectIdentityFactory,
        AclAnnotationProvider $aclAnnotationProvider,
        LoggerInterface $logger
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->objectIdentityFactory = $objectIdentityFactory;
        $this->aclAnnotationProvider = $aclAnnotationProvider;
        $this->logger = $logger;
    }

    /**
     * Checks if an access to the given method of the given class is granted for the current authentication token.
     */
    public function isClassMethodGranted(string $class, string $method): bool
    {
        $isGranted = true;

        // check method level ACL
        $annotation = $this->aclAnnotationProvider->findAnnotation($class, $method);
        if (null !== $annotation) {
            $this->logger->debug(
                sprintf('Check an access using "%s" ACL annotation.', $annotation->getId())
            );
            $isGranted = $this->authorizationChecker->isGranted(
                $annotation->getPermission(),
                $this->objectIdentityFactory->get($annotation)
            );
        }

        // check class level ACL
        if ($isGranted && (null === $annotation || !$annotation->getIgnoreClassAcl())) {
            $annotation = $this->aclAnnotationProvider->findAnnotation($class);
            if (null !== $annotation) {
                $this->logger->debug(
                    sprintf('Check an access using "%s" ACL annotation.', $annotation->getId())
                );
                $isGranted = $this->authorizationChecker->isGranted(
                    $annotation->getPermission(),
                    $this->objectIdentityFactory->get($annotation)
                );
            }
        }

        return $isGranted;
    }

    /**
     * Gets ACL annotation that is bound to the given method of the given class.
     */
    public function getClassMethodAnnotation(string $class, string $method): ?AclAnnotation
    {
        return $this->aclAnnotationProvider->findAnnotation($class, $method);
    }
}
