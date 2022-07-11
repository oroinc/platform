<?php

namespace Oro\Bundle\SecurityBundle\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The main authorization point of the Security component.
 */
class AuthorizationChecker implements AuthorizationCheckerInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private ObjectIdentityFactory $objectIdentityFactory;
    private AclAnnotationProvider $annotationProvider;
    private AclGroupProviderInterface $groupProvider;
    private LoggerInterface $logger;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ObjectIdentityFactory $objectIdentityFactory,
        AclAnnotationProvider $annotationProvider,
        AclGroupProviderInterface $groupProvider,
        LoggerInterface $logger
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->objectIdentityFactory = $objectIdentityFactory;
        $this->annotationProvider = $annotationProvider;
        $this->groupProvider = $groupProvider;
        $this->logger = $logger;
    }

    /**
     * Checks if an access to a resource is granted for the current authentication token.
     *
     * @param mixed $attribute  Can be a role name, permission name, an ACL annotation id,
     *                          string in format "permission;descriptor"
     *                          (VIEW;entity:Acme\DemoBundle\Entity\AcmeEntity, EXECUTE;action:acme_action)
     *                          or something else depending on registered security voters
     * @param mixed $subject    A domain object, an entity type descriptor
     *                          (e.g., entity:Acme\DemoBundle\Entity\AcmeEntity, action:some_action).
     *                          or an instance of {@see \Symfony\Component\Security\Acl\Domain\ObjectIdentity},
     *                          {@see \Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference},
     *                          {@see \Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper},
     *                          {@see \Symfony\Component\Security\Acl\Voter\FieldVote}.
     *
     * @return bool
     */
    public function isGranted($attribute, $subject = null)
    {
        if (\is_string($attribute) && !empty($attribute) && $annotation = $this->getAnnotation($attribute)) {
            if (null === $subject) {
                $this->logger->debug(
                    sprintf('Check class based an access using "%s" ACL annotation.', $annotation->getId())
                );
                $isGranted = $this->authorizationChecker->isGranted(
                    $annotation->getPermission(),
                    $this->objectIdentityFactory->get($annotation)
                );
            } else {
                $this->logger->debug(
                    sprintf('Check object based an access using "%s" ACL annotation.', $annotation->getId())
                );
                $isGranted = $this->authorizationChecker->isGranted(
                    $annotation->getPermission(),
                    $subject
                );
            }
        } elseif (\is_string($subject)) {
            $isGranted = $this->authorizationChecker->isGranted(
                $attribute,
                $this->tryGetObjectIdentity($subject) ?? $subject
            );
        } else {
            if (null === $subject && \is_string($attribute)) {
                $delimiter = strpos($attribute, ';');
                if ($delimiter) {
                    $subject = substr($attribute, $delimiter + 1);
                    $subject = $this->tryGetObjectIdentity($subject) ?? $subject;
                    $attribute = substr($attribute, 0, $delimiter);
                }
            }

            $isGranted = $this->authorizationChecker->isGranted($attribute, $subject);
        }

        return $isGranted;
    }

    private function getAnnotation(string $annotationId): ?AclAnnotation
    {
        return $this->annotationProvider->findAnnotationById($annotationId);
    }

    private function tryGetObjectIdentity(mixed $val): ?ObjectIdentity
    {
        if (\is_string($val) && !ObjectIdentityHelper::hasGroupName($val)) {
            $group = $this->groupProvider->getGroup();
            if ($group) {
                [$id, $type, $fieldName] = ObjectIdentityHelper::parseIdentityString($val);
                $val = ObjectIdentityHelper::encodeIdentityString(
                    $id,
                    ObjectIdentityHelper::buildType($type, $group),
                    $fieldName
                );
            }
        }

        try {
            return $this->objectIdentityFactory->get($val);
        } catch (InvalidDomainObjectException $e) {
            $this->logger->debug('The ObjectIdentity cannot be created.', ['exception' => $e, 'object' => $val]);

            return null;
        }
    }
}
