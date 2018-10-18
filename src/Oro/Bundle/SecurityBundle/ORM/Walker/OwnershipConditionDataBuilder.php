<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Default ownership condition builder
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OwnershipConditionDataBuilder extends AbstractOwnershipConditionDataBuilder
{
    /** @var ObjectIdAccessor */
    protected $objectIdAccessor;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var EntitySecurityMetadataProvider */
    protected $entityMetadataProvider;

    /** @var OwnerTreeProviderInterface */
    protected $treeProvider;

    /** @var AclGroupProviderInterface */
    protected $aclGroupProvider;

    /**
     * @param AuthorizationCheckerInterface      $authorizationChecker
     * @param TokenStorageInterface              $tokenStorage
     * @param ObjectIdAccessor                   $objectIdAccessor
     * @param EntitySecurityMetadataProvider     $entityMetadataProvider
     * @param OwnershipMetadataProviderInterface $metadataProvider
     * @param OwnerTreeProviderInterface         $treeProvider
     * @param AclVoter                           $aclVoter
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        ObjectIdAccessor $objectIdAccessor,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        OwnershipMetadataProviderInterface $metadataProvider,
        OwnerTreeProviderInterface $treeProvider,
        AclVoter $aclVoter = null
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->aclVoter = $aclVoter;
        $this->objectIdAccessor = $objectIdAccessor;
        $this->entityMetadataProvider = $entityMetadataProvider;
        $this->metadataProvider = $metadataProvider;
        $this->treeProvider = $treeProvider;
    }

    /**
     * @param AclGroupProviderInterface $aclGroupProvider
     */
    public function setAclGroupProvider($aclGroupProvider)
    {
        $this->aclGroupProvider = $aclGroupProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getAclConditionData($entityClassName, $permissions = 'VIEW')
    {
        if ($this->aclVoter === null
            || !$this->getUserId()
            || !$this->entityMetadataProvider->isProtectedEntity($entityClassName)
        ) {
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
                $this->metadataProvider->getMetadata($entityClassName)
            );
        } else {
            $condition = $this->getAccessDeniedCondition();
        }

        return $condition;
    }

    /**
     * @param  string                     $targetEntityClassName
     * @param  int                        $accessLevel
     * @param  OwnershipMetadataInterface $metadata
     *
     * @return null|array
     *
     * The cyclomatic complexity warning is suppressed by performance reasons
     * (to avoid unnecessary cloning od arrays)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function buildConstraintIfAccessIsGranted(
        $targetEntityClassName,
        $accessLevel,
        OwnershipMetadataInterface $metadata
    ) {
        $tree       = $this->getTree();
        $constraint = null;

        if (AccessLevel::SYSTEM_LEVEL === $accessLevel) {
            $constraint = [];
        } elseif (!$metadata->hasOwner()) {
            if (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getOrganizationClass() === $targetEntityClassName) {
                    $orgIds     = $tree->getUserOrganizationIds($this->getUserId());
                    $constraint = $this->getCondition($orgIds, $metadata, 'id');
                } else {
                    $constraint = [];
                }
            } else {
                $constraint = [];
            }
        } else {
            if (AccessLevel::BASIC_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getUserClass() === $targetEntityClassName) {
                    $constraint = $this->getCondition($this->getUserId(), $metadata, 'id');
                } elseif ($metadata->isUserOwned()) {
                    $constraint = $this->getCondition($this->getUserId(), $metadata);
                }
            } elseif (AccessLevel::LOCAL_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getBusinessUnitClass() === $targetEntityClassName) {
                    $buIds      = $tree->getUserBusinessUnitIds($this->getUserId(), $this->getOrganizationId());
                    $constraint = $this->getCondition($buIds, $metadata, 'id');
                } elseif ($metadata->isBusinessUnitOwned()) {
                    $buIds      = $tree->getUserBusinessUnitIds($this->getUserId(), $this->getOrganizationId());
                    $constraint = $this->getCondition($buIds, $metadata);
                } elseif ($metadata->isUserOwned()) {
                    $userIds = [];
                    $this->fillBusinessUnitUserIds($this->getUserId(), $this->getOrganizationId(), $userIds);
                    $constraint = $this->getCondition($userIds, $metadata);
                }
            } elseif (AccessLevel::DEEP_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getBusinessUnitClass() === $targetEntityClassName) {
                    $buIds = [];
                    $this->fillSubordinateBusinessUnitIds($this->getUserId(), $this->getOrganizationId(), $buIds);
                    $constraint = $this->getCondition($buIds, $metadata, 'id');
                } elseif ($metadata->isBusinessUnitOwned()) {
                    $buIds = [];
                    $this->fillSubordinateBusinessUnitIds($this->getUserId(), $this->getOrganizationId(), $buIds);
                    $constraint = $this->getCondition($buIds, $metadata);
                } elseif ($metadata->isUserOwned()) {
                    $userIds = [];
                    $this->fillSubordinateBusinessUnitUserIds($this->getUserId(), $this->getOrganizationId(), $userIds);
                    $constraint = $this->getCondition($userIds, $metadata);
                }
            } elseif (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
                if ($metadata->isOrganizationOwned()) {
                    $constraint = $this->getCondition([$this->getOrganizationId()], $metadata, null, true);
                } else {
                    $constraint = $this->getCondition(null, $metadata, null, true);
                }
            }
        }

        return $constraint;
    }

    /**
     * @param OwnershipMetadataInterface $metadata
     *
     * @return array|int|null
     */
    protected function getOrganizationId(OwnershipMetadataInterface $metadata = null)
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            return $token->getOrganizationContext()->getId();
        }

        return null;
    }

    /**
     * Gets the id of logged in user
     *
     * @return int|string|null
     */
    public function getUserId()
    {
        $user = $this->getUser();

        if ($user) {
            return $this->objectIdAccessor->getId($user);
        }

        return null;
    }

    /**
     * Adds all business unit ids within all subordinate business units the given user is associated
     *
     * @param int|string $userId
     * @param int|string $organizationId
     * @param array      $result [output]
     */
    protected function fillSubordinateBusinessUnitIds($userId, $organizationId, array &$result)
    {
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
     * Adds all user ids within all business units the given user is associated
     *
     * @param int|string $userId
     * @param int|string $organizationId
     * @param array      $result [output]
     */
    protected function fillBusinessUnitUserIds($userId, $organizationId, array &$result)
    {
        // add current user to select this user owned records
        $result[] = $userId;

        foreach ($this->getTree()->getUserBusinessUnitIds($userId, $organizationId) as $buId) {
            $userIds = $this->getTree()->getUsersAssignedToBusinessUnit($buId);
            if (!empty($userIds)) {
                $result = array_unique(array_merge($result, $userIds));
            }
        }
    }

    /**
     * Adds all user ids within all subordinate business units the given user is associated
     *
     * @param int|string $userId
     * @param int|string $organizationId
     * @param array      $result [output]
     */
    protected function fillSubordinateBusinessUnitUserIds($userId, $organizationId, array &$result)
    {
        // add current user to select this user owned records
        $result[] = $userId;

        $buIds = [];
        $this->fillSubordinateBusinessUnitIds($userId, $organizationId, $buIds);
        foreach ($buIds as $buId) {
            $userIds = $this->getTree()->getUsersAssignedToBusinessUnit($buId);
            if (!empty($userIds)) {
                $result = array_unique(array_merge($result, $userIds));
            }
        }
    }

    /**
     * Gets SQL condition for the given owner id or ids
     *
     * @param int|int[]|null idOrIds
     * @param OwnershipMetadataInterface $metadata
     * @param string|null $columnName
     * @param bool $ignoreOwner
     *
     * @return array|null
     */
    protected function getCondition(
        $idOrIds,
        OwnershipMetadataInterface $metadata,
        $columnName = null,
        $ignoreOwner = false
    ) {
        $organizationField = null;
        $organizationValue = null;
        if ($metadata->getOrganizationColumnName() && $this->getOrganizationId($metadata)) {
            $organizationField = $metadata->getOrganizationFieldName();
            $organizationValue = $this->getOrganizationId($metadata);
        }

        if (!$ignoreOwner && !empty($idOrIds)) {
            return [
                $this->getColumnName($metadata, $columnName),
                $idOrIds,
                $organizationField,
                $organizationValue,
                $ignoreOwner
            ];
        } elseif ($organizationField && $organizationValue) {
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
     * Gets SQL condition that can be used to apply restrictions for all records (e.g. in case of an access is denied)
     *
     * @return array
     */
    protected function getAccessDeniedCondition()
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
     * Gets the name of owner column
     *
     * @param OwnershipMetadataInterface $metadata
     * @param string|null $columnName
     *
     * @return string
     */
    protected function getColumnName(OwnershipMetadataInterface $metadata, $columnName = null)
    {
        if ($columnName === null) {
            $columnName = $metadata->getOwnerFieldName();
        }

        return $columnName;
    }

    /**
     * @return OwnerTree
     */
    protected function getTree()
    {
        return $this->treeProvider->getTree();
    }
}
