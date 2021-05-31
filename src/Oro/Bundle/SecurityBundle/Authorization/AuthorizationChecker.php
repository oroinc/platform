<?php

namespace Oro\Bundle\SecurityBundle\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Component\DependencyInjection\ServiceLink;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The main authorization point of the Security component.
 */
class AuthorizationChecker implements AuthorizationCheckerInterface
{
    /** @var ServiceLink */
    private $authorizationCheckerLink;

    /** @var ServiceLink */
    private $objectIdentityFactoryLink;

    /** @var ServiceLink */
    private $annotationProviderLink;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ServiceLink     $authorizationCheckerLink
     * @param ServiceLink     $objectIdentityFactoryLink
     * @param ServiceLink     $annotationProviderLink
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServiceLink $authorizationCheckerLink,
        ServiceLink $objectIdentityFactoryLink,
        ServiceLink $annotationProviderLink,
        LoggerInterface $logger
    ) {
        $this->authorizationCheckerLink = $authorizationCheckerLink;
        $this->objectIdentityFactoryLink = $objectIdentityFactoryLink;
        $this->annotationProviderLink = $annotationProviderLink;
        $this->logger = $logger;
    }

    /**
     * Checks if an access to a resource is granted for the current authentication token.
     *
     * @param mixed $attribute  Can be a role name, permission name, an ACL annotation id,
     *                          string in format "permission;descriptor"
     *                          (VIEW;entity:AcmeDemoBundle:AcmeEntity, EDIT;action:acme_action)
     *                          or something else, it depends on registered security voters
     * @param mixed $object     A domain object, object identity or object identity descriptor (id:type)
     *                          (entity:Acme/DemoBundle/Entity/AcmeEntity, action:some_action)
     *
     * @return bool
     */
    public function isGranted($attribute, $object = null)
    {
        if (\is_string($attribute) && !empty($attribute) && $annotation = $this->getAnnotation($attribute)) {
            if (null === $object) {
                $this->logger->debug(
                    \sprintf('Check class based an access using "%s" ACL annotation.', $annotation->getId())
                );
                $isGranted = $this->isAccessGranted(
                    $annotation->getPermission(),
                    $this->getObjectIdentity($annotation)
                );
            } else {
                $this->logger->debug(
                    \sprintf('Check object based an access using "%s" ACL annotation.', $annotation->getId())
                );
                $isGranted = $this->isAccessGranted(
                    $annotation->getPermission(),
                    $object
                );
            }
        } elseif (\is_string($object)) {
            $isGranted = $this->isAccessGranted(
                $attribute,
                $this->tryGetObjectIdentity($object) ?? $object
            );
        } else {
            if (null === $object && \is_string($attribute)) {
                $delimiter = \strpos($attribute, ';');
                if ($delimiter) {
                    $object = \substr($attribute, $delimiter + 1);
                    $attribute = \substr($attribute, 0, $delimiter);
                }
            }

            $isGranted = $this->isAccessGranted($attribute, $object);
        }

        return $isGranted;
    }

    /**
     * @param mixed $attribute
     * @param mixed $object
     *
     * @return bool
     */
    private function isAccessGranted($attribute, $object = null): bool
    {
        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->authorizationCheckerLink->getService();

        return $authorizationChecker->isGranted($attribute, $object);
    }

    /**
     * @param string $annotationId
     *
     * @return AclAnnotation|null
     */
    private function getAnnotation(string $annotationId): ?AclAnnotation
    {
        /** @var AclAnnotationProvider $annotationProvider */
        $annotationProvider = $this->annotationProviderLink->getService();

        return $annotationProvider->findAnnotationById($annotationId);
    }

    /**
     * @param mixed $val
     *
     * @return ObjectIdentity
     */
    private function getObjectIdentity($val): ObjectIdentity
    {
        /** @var ObjectIdentityFactory $objectIdentityFactory */
        $objectIdentityFactory = $this->objectIdentityFactoryLink->getService();

        return $objectIdentityFactory->get($val);
    }

    /**
     * @param mixed $val
     *
     * @return ObjectIdentity|null
     */
    private function tryGetObjectIdentity($val)
    {
        /** @var ObjectIdentityFactory $objectIdentityFactory */
        $objectIdentityFactory = $this->objectIdentityFactoryLink->getService();

        try {
            return $objectIdentityFactory->get($val);
        } catch (InvalidDomainObjectException $e) {
            $this->logger->debug('The ObjectIdentity cannot be created.', ['exception' => $e, 'object' => $val]);
        }
    }
}
