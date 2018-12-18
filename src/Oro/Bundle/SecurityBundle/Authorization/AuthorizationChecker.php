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
     * @param string|string[] $attributes Can be a role name(s), permission name(s), an ACL annotation id,
     *                                    string in format "permission;descriptor"
     *                                    (VIEW;entity:AcmeDemoBundle:AcmeEntity, EDIT;action:acme_action)
     *                                    or something else, it depends on registered security voters
     * @param  mixed          $object     A domain object, object identity or object identity descriptor (id:type)
     *                                    (entity:Acme/DemoBundle/Entity/AcmeEntity,  action:some_action)
     *
     * @return bool
     */
    public function isGranted($attributes, $object = null)
    {
        if (\is_string($attributes) && !empty($attributes) && $annotation = $this->getAnnotation($attributes)) {
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
                $attributes,
                $this->tryGetObjectIdentity($object) ?? $object
            );
        } else {
            if (null === $object && \is_string($attributes)) {
                $delimiter = \strpos($attributes, ';');
                if ($delimiter) {
                    $object = \substr($attributes, $delimiter + 1);
                    $attributes = \substr($attributes, 0, $delimiter);
                }
            }

            $isGranted = $this->isAccessGranted($attributes, $object);
        }

        return $isGranted;
    }

    /**
     * @param mixed $attributes
     * @param mixed $object
     *
     * @return bool
     */
    private function isAccessGranted($attributes, $object = null)
    {
        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->authorizationCheckerLink->getService();

        return $authorizationChecker->isGranted($attributes, $object);
    }

    /**
     * @param string $annotationId
     *
     * @return AclAnnotation|null
     */
    private function getAnnotation($annotationId)
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
    private function getObjectIdentity($val)
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
