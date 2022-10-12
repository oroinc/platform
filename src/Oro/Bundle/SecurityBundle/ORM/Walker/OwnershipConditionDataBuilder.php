<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Default ownership condition builder.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OwnershipConditionDataBuilder implements AclConditionDataBuilderInterface
{
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected TokenStorageInterface $tokenStorage;
    protected OwnershipMetadataProviderInterface $metadataProvider;
    protected ObjectIdAccessor $objectIdAccessor;
    protected AclVoterInterface $aclVoter;
    protected EntitySecurityMetadataProvider $entityMetadataProvider;
    protected OwnerTreeProviderInterface $treeProvider;
    protected ?AclGroupProviderInterface $aclGroupProvider = null;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        ObjectIdAccessor $objectIdAccessor,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        OwnershipMetadataProviderInterface $metadataProvider,
        OwnerTreeProviderInterface $treeProvider,
        AclVoterInterface $aclVoter
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->aclVoter = $aclVoter;
        $this->objectIdAccessor = $objectIdAccessor;
        $this->entityMetadataProvider = $entityMetadataProvider;
        $this->metadataProvider = $metadataProvider;
        $this->treeProvider = $treeProvider;
    }

    public function setAclGroupProvider(AclGroupProviderInterface $aclGroupProvider): void
    {
        $this->aclGroupProvider = $aclGroupProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getAclConditionData(string $entityClassName, string|array $permissions = 'VIEW'): ?array
    {
        if (!$this->getUserId() || !$this->entityMetadataProvider->isProtectedEntity($entityClassName)) {
            // return full access to the entity
            return [];
        }

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);

        $groupedEntityClassName = $entityClassName;
        if ($this->aclGroupProvider) {
            $group = $this->aclGroupProvider->getGroup();
            if ($group) {
                $groupedEntityClassName = ObjectIdentityHelper::buildType(
                    $entityClassName,
                    $this->aclGroupProvider->getGroup()
                );
            }
        }

        if ($this->isEntityGranted($permissions, $groupedEntityClassName)) {
            $condition = $this->buildConstraintIfAccessIsGranted(
                $entityClassName,
                $observer->getAccessLevel(),
                $this->metadataProvider->getMetadata($entityClassName),
                $permissions
            );
        } else {
            $condition = $this->getAccessDeniedCondition();
        }

        return $condition;
    }

    /**
     * The cyclomatic complexity warning is suppressed by performance reasons
     * (to avoid unnecessary cloning of arrays).
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function buildConstraintIfAccessIsGranted(
        string $targetEntityClassName,
        int $accessLevel,
        OwnershipMetadataInterface $metadata,
        array|string $permissions
    ): ?array {
        $tree       = $this->getTree();
        $constraint = null;

        if (AccessLevel::SYSTEM_LEVEL === $accessLevel) {
            $constraint = [];
        } elseif (!$metadata->hasOwner()) {
            if (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getOrganizationClass() === $targetEntityClassName) {
                    $orgIds     = $tree->getUserOrganizationIds($this->getUserId());
                    $constraint = $this->getCondition($orgIds, $metadata, $permissions, 'id');
                } else {
                    $constraint = [];
                }
            } else {
                $constraint = [];
            }
        } else {
            if (AccessLevel::BASIC_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getUserClass() === $targetEntityClassName) {
                    $constraint = $this->getCondition($this->getUserId(), $metadata, $permissions, 'id');
                } elseif ($metadata->isUserOwned()) {
                    $constraint = $this->getCondition($this->getUserId(), $metadata, $permissions);
                }
            } elseif (AccessLevel::LOCAL_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getBusinessUnitClass() === $targetEntityClassName) {
                    $buIds = $tree->getUserBusinessUnitIds($this->getUserId(), $this->getOrganizationId($permissions));
                    $constraint = $this->getCondition($buIds, $metadata, $permissions, 'id');
                } elseif ($metadata->isBusinessUnitOwned()) {
                    $buIds = $tree->getUserBusinessUnitIds($this->getUserId(), $this->getOrganizationId($permissions));
                    $constraint = $this->getCondition($buIds, $metadata, $permissions);
                } elseif ($metadata->isUserOwned()) {
                    $userIds = [];
                    $this->fillBusinessUnitUserIds(
                        $this->getUserId(),
                        $this->getOrganizationId($permissions),
                        $userIds
                    );
                    $constraint = $this->getCondition($userIds, $metadata, $permissions);
                }
            } elseif (AccessLevel::DEEP_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getBusinessUnitClass() === $targetEntityClassName) {
                    $buIds = [];
                    $this->fillSubordinateBusinessUnitIds(
                        $this->getUserId(),
                        $this->getOrganizationId($permissions),
                        $buIds
                    );
                    $constraint = $this->getCondition($buIds, $metadata, $permissions, 'id');
                } elseif ($metadata->isBusinessUnitOwned()) {
                    $buIds = [];
                    $this->fillSubordinateBusinessUnitIds(
                        $this->getUserId(),
                        $this->getOrganizationId($permissions),
                        $buIds
                    );
                    $constraint = $this->getCondition($buIds, $metadata, $permissions);
                } elseif ($metadata->isUserOwned()) {
                    $userIds = [];
                    $this->fillSubordinateBusinessUnitUserIds(
                        $this->getUserId(),
                        $this->getOrganizationId($permissions),
                        $userIds
                    );
                    $constraint = $this->getCondition($userIds, $metadata, $permissions);
                }
            } elseif (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
                if ($metadata->isOrganizationOwned()) {
                    $constraint = $this->getCondition(
                        [$this->getOrganizationId($permissions)],
                        $metadata,
                        $permissions,
                        null,
                        true
                    );
                } else {
                    $constraint = $this->getCondition(
                        null,
                        $metadata,
                        $permissions,
                        null,
                        true
                    );
                }
            }
        }

        return $constraint;
    }

    protected function getOrganizationId(
        array|string $permissions,
        OwnershipMetadataInterface $metadata = null
    ): array|int|string|null {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OrganizationAwareTokenInterface) {
            return $token->getOrganization()->getId();
        }

        return null;
    }

    protected function getUser(): ?UserInterface
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

    protected function getUserId(): int|string|null
    {
        $user = $this->getUser();
        if (null === $user) {
            return null;
        }

        return $this->objectIdAccessor->getId($user);
    }

    /**
     * Adds all business unit ids within all subordinate business units the given user is associated.
     */
    protected function fillSubordinateBusinessUnitIds(
        int|string $userId,
        int|string $organizationId,
        array &$result
    ): void {
        $buIds  = $this->getTree()->getUserBusinessUnitIds($userId, $organizationId);
        $result = array_merge($buIds, []);
        foreach ($buIds as $buId) {
            $diff = array_diff($this->getTree()->getSubordinateBusinessUnitIds($buId), $result);
            if (!empty($diff)) {
                $result = array_merge($result, $diff);
            }
        }
    }

    /**
     * Adds all user ids within all business units the given user is associated.
     */
    protected function fillBusinessUnitUserIds(
        int|string $userId,
        int|string $organizationId,
        array &$result
    ): void {
        // add current user to select this user owned records
        $result[] = $userId;

        foreach ($this->getTree()->getUserBusinessUnitIds($userId, $organizationId) as $buId) {
            $userIds = $this->getTree()->getUsersAssignedToBusinessUnit($buId);
            if (!empty($userIds)) {
                $result = array_values(array_unique(array_merge($result, $userIds)));
            }
        }
    }

    /**
     * Adds all user ids within all subordinate business units the given user is associated.
     */
    protected function fillSubordinateBusinessUnitUserIds(
        int|string $userId,
        int|string $organizationId,
        array &$result
    ): void {
        // add current user to select this user owned records
        $result[] = $userId;

        $buIds = [];
        $this->fillSubordinateBusinessUnitIds($userId, $organizationId, $buIds);
        foreach ($buIds as $buId) {
            $userIds = $this->getTree()->getUsersAssignedToBusinessUnit($buId);
            if (!empty($userIds)) {
                $result = array_values(array_unique(array_merge($result, $userIds)));
            }
        }
    }

    /**
     * Gets SQL condition for the given owner id or ids.
     */
    protected function getCondition(
        int|string|array|null $idOrIds,
        OwnershipMetadataInterface $metadata,
        array|string $permissions,
        ?string $columnName = null,
        bool $ignoreOwner = false
    ): ?array {
        $organizationField = null;
        $organizationValue = null;
        if ($metadata->getOrganizationColumnName() && $this->getOrganizationId($permissions, $metadata)) {
            $organizationField = $metadata->getOrganizationFieldName();
            $organizationValue = $this->getOrganizationId($permissions, $metadata);
        }

        if (!$ignoreOwner && !empty($idOrIds)) {
            return [
                $this->getColumnName($metadata, $columnName),
                $idOrIds,
                $organizationField,
                $organizationValue,
                $ignoreOwner
            ];
        }
        if ($organizationField && $organizationValue) {
            return [
                null,
                null,
                $organizationField,
                $organizationValue,
                $ignoreOwner
            ];
        }

        return null;
    }

    /**
     * Gets SQL condition that can be used to apply restrictions for all records (e.g. in case if access is denied).
     */
    protected function getAccessDeniedCondition(): array
    {
        return [
            null,
            null,
            null,
            null,
            false
        ];
    }

    /**
     * Gets the name of owner column.
     */
    protected function getColumnName(OwnershipMetadataInterface $metadata, ?string $columnName = null): string
    {
        if ($columnName === null) {
            $columnName = $metadata->getOwnerFieldName();
        }

        return $columnName;
    }

    protected function getTree(): OwnerTreeInterface
    {
        return $this->treeProvider->getTree();
    }

    protected function isEntityGranted(string|array $permissions, string $entityType): bool
    {
        return $this->authorizationChecker->isGranted(
            $permissions,
            new ObjectIdentity('entity', $entityType)
        );
    }
}
