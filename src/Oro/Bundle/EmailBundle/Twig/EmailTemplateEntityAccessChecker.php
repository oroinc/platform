<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

/**
 * Authorizes every object an email template render walks to against the VIEW permission of the current user.
 */
class EmailTemplateEntityAccessChecker implements ResetInterface
{
    private const string ACCESS_DENIED_MESSAGE = 'Access to the "%s" record is not allowed for the current user.';

    /**
     * @var array<string, bool> Access decisions made so far, keyed by the current user, organization and record.
     */
    private array $decisions = [];

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenAccessorInterface $tokenAccessor,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    /**
     * @throws SecurityNotAllowedPropertyError when the current user is not allowed to view the given object.
     */
    public function assertPropertyAccessGranted(mixed $object, string $propertyName): void
    {
        if ($this->isViewGranted($object)) {
            return;
        }

        $entityClass = $this->doctrineHelper->getEntityClass($object);

        throw new SecurityNotAllowedPropertyError(
            sprintf(self::ACCESS_DENIED_MESSAGE, $entityClass),
            $entityClass,
            $propertyName
        );
    }

    /**
     * @throws SecurityNotAllowedMethodError when the current user is not allowed to view the given object.
     */
    public function assertMethodAccessGranted(mixed $object, string $methodName): void
    {
        if ($this->isViewGranted($object)) {
            return;
        }

        $entityClass = $this->doctrineHelper->getEntityClass($object);

        throw new SecurityNotAllowedMethodError(
            sprintf(self::ACCESS_DENIED_MESSAGE, $entityClass),
            $entityClass,
            $methodName
        );
    }

    #[\Override]
    public function reset(): void
    {
        $this->decisions = [];
    }

    private function isViewGranted(mixed $object): bool
    {
        if (!\is_object($object)) {
            return true;
        }

        $user = $this->tokenAccessor->getUser();
        if (null === $user) {
            // A render that holds no user is performed by the application itself and is not bound by user access.
            return true;
        }

        if (!$this->doctrineHelper->isManageableEntity($object)) {
            return true;
        }

        if ($this->doctrineHelper->isNewEntity($object)) {
            // A record that was never persisted has no ACL identity yet.
            return true;
        }

        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object, false);
        if (null === $identifier) {
            // A composite identifier cannot be reduced to a memo key, so the decision is made without memoizing it.
            return $this->authorizationChecker->isGranted(BasicPermission::VIEW, $object);
        }

        $key = $user::class . ':' . $user->getId()
            . ':' . $this->tokenAccessor->getOrganizationId()
            . '|' . $this->doctrineHelper->getEntityClass($object) . ':' . $identifier;

        return $this->decisions[$key]
            ??= $this->authorizationChecker->isGranted(BasicPermission::VIEW, $object);
    }
}
