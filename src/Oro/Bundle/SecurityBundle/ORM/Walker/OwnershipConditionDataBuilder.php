<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query\AST\PathExpression;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Entity\AclClass;
use Oro\Bundle\SecurityBundle\Form\Model\Share;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OwnershipConditionDataBuilder
{
    const ACL_ENTRIES_SCHEMA_NAME = 'Oro\Bundle\SecurityBundle\Entity\AclEntry';
    const ACL_ENTRIES_ALIAS = 'entries';
    const ACL_ENTRIES_SHARE_RECORD = 'recordId';
    const ACL_ENTRIES_CLASS_ID = 'class';
    const ACL_ENTRIES_SECURITY_ID = 'securityIdentity';

    protected $shareAccessLevels = [
        AccessLevel::BASIC_LEVEL,
        AccessLevel::LOCAL_LEVEL,
        AccessLevel::DEEP_LEVEL,
    ];

    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var ObjectIdAccessor */
    protected $objectIdAccessor;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var MetadataProviderInterface */
    protected $metadataProvider;

    /** @var EntitySecurityMetadataProvider */
    protected $entityMetadataProvider;

    /** @var OwnerTreeProviderInterface */
    protected $treeProvider;

    /** @var AclGroupProviderInterface */
    protected $aclGroupProvider;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /** @var null|mixed */
    protected $user = null;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /** @var SecurityIdentityRetrievalStrategyInterface */
    protected $sidStrategy;

    /** @var array|null */
    protected $sids = null;

    /**
     * @param ServiceLink                    $securityContextLink
     * @param ObjectIdAccessor               $objectIdAccessor
     * @param EntitySecurityMetadataProvider $entityMetadataProvider
     * @param MetadataProviderInterface      $metadataProvider
     * @param OwnerTreeProviderInterface     $treeProvider
     * @param ManagerRegistry                $registry
     * @param ConfigProvider                 $configProvider
     * @param SecurityIdentityRetrievalStrategyInterface $sidStrategy
     * @param AclVoter                       $aclVoter
     */
    public function __construct(
        ServiceLink $securityContextLink,
        ObjectIdAccessor $objectIdAccessor,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        MetadataProviderInterface $metadataProvider,
        OwnerTreeProviderInterface $treeProvider,
        ManagerRegistry $registry,
        ConfigProvider $configProvider,
        SecurityIdentityRetrievalStrategyInterface $sidStrategy,
        AclVoter $aclVoter = null
    ) {
        $this->securityContextLink    = $securityContextLink;
        $this->aclVoter               = $aclVoter;
        $this->objectIdAccessor       = $objectIdAccessor;
        $this->entityMetadataProvider = $entityMetadataProvider;
        $this->metadataProvider       = $metadataProvider;
        $this->treeProvider           = $treeProvider;
        $this->registry               = $registry;
        $this->configProvider         = $configProvider;
        $this->sidStrategy            = $sidStrategy;
    }

    /**
     * @param AclGroupProviderInterface $aclGroupProvider
     */
    public function setAclGroupProvider($aclGroupProvider)
    {
        $this->aclGroupProvider = $aclGroupProvider;
    }

    /**
     * Get data for query acl access level check
     * Return empty array if entity has full access, null if user does't have access to the entity
     *  and array with entity field and field values which user have access.
     *
     * @param $entityClassName
     * @param $permissions
     *
     * @return null|array
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
                $groupedEntityClassName = sprintf('%s@%s', $this->aclGroupProvider->getGroup(), $entityClassName);
            }
        }
        $isGranted = $this->getSecurityContext()->isGranted(
            $permissions,
            new ObjectIdentity('entity', $groupedEntityClassName)
        );

        if ($isGranted) {
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
                if ($this->metadataProvider->getGlobalLevelClass() === $targetEntityClassName) {
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
                if ($this->metadataProvider->getBasicLevelClass() === $targetEntityClassName) {
                    $constraint = $this->getCondition($this->getUserId(), $metadata, 'id');
                } elseif ($metadata->isBasicLevelOwned()) {
                    $constraint = $this->getCondition($this->getUserId(), $metadata);
                }
            } elseif (AccessLevel::LOCAL_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getLocalLevelClass() === $targetEntityClassName) {
                    $buIds      = $tree->getUserBusinessUnitIds($this->getUserId(), $this->getOrganizationId());
                    $constraint = $this->getCondition($buIds, $metadata, 'id');
                } elseif ($metadata->isLocalLevelOwned()) {
                    $buIds      = $tree->getUserBusinessUnitIds($this->getUserId(), $this->getOrganizationId());
                    $constraint = $this->getCondition($buIds, $metadata);
                } elseif ($metadata->isBasicLevelOwned()) {
                    $userIds = [];
                    $this->fillBusinessUnitUserIds($this->getUserId(), $this->getOrganizationId(), $userIds);
                    $constraint = $this->getCondition($userIds, $metadata);
                }
            } elseif (AccessLevel::DEEP_LEVEL === $accessLevel) {
                if ($this->metadataProvider->getLocalLevelClass() === $targetEntityClassName) {
                    $buIds = [];
                    $this->fillSubordinateBusinessUnitIds($this->getUserId(), $this->getOrganizationId(), $buIds);
                    $constraint = $this->getCondition($buIds, $metadata, 'id');
                } elseif ($metadata->isLocalLevelOwned()) {
                    $buIds = [];
                    $this->fillSubordinateBusinessUnitIds($this->getUserId(), $this->getOrganizationId(), $buIds);
                    $constraint = $this->getCondition($buIds, $metadata);
                } elseif ($metadata->isBasicLevelOwned()) {
                    $userIds = [];
                    $this->fillSubordinateBusinessUnitUserIds($this->getUserId(), $this->getOrganizationId(), $userIds);
                    $constraint = $this->getCondition($userIds, $metadata);
                }
            } elseif (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
                if ($metadata->isGlobalLevelOwned()) {
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
        $token = $this->getSecurityContext()->getToken();
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
            $userIds = $this->getTree()->getUsersAssignedToBU($buId);
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
            $userIds = $this->getTree()->getUsersAssignedToBU($buId);
            if (!empty($userIds)) {
                $result = array_unique(array_merge($result, $userIds));
            }
        }
    }

    /**
     * Adds all business unit ids within all organizations the given user is associated
     *
     * @param int|string $userId
     * @param array      $result [output]
     */
    protected function fillOrganizationBusinessUnitIds($userId, array &$result)
    {
        foreach ($this->getTree()->getUserOrganizationIds($userId) as $orgId) {
            $buIds = $this->getTree()->getOrganizationBusinessUnitIds($orgId);
            if (!empty($buIds)) {
                $result = array_merge($result, $buIds);
            }
        }
    }

    /**
     * Adds all user ids within all organizations the given user is associated
     *
     * @param int|string $userId
     * @param array      $result [output]
     */
    protected function fillOrganizationUserIds($userId, array &$result)
    {
        foreach ($this->getTree()->getUserOrganizationIds($userId) as $orgId) {
            foreach ($this->getTree()->getOrganizationBusinessUnitIds($orgId) as $buId) {
                $userIds = $this->getTree()->getBusinessUnitUserIds($buId);
                if (!empty($userIds)) {
                    $result = array_merge($result, $userIds);
                }
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
        if ($metadata->getGlobalOwnerColumnName() && $this->getOrganizationId($metadata)) {
            $organizationField = $metadata->getGlobalOwnerFieldName();
            $organizationValue = $this->getOrganizationId($metadata);
        }

        if (!$ignoreOwner && !empty($idOrIds)) {
            return [
                $this->getColumnName($metadata, $columnName),
                $idOrIds,
                $columnName == null ? PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION : PathExpression::TYPE_STATE_FIELD,
                $organizationField,
                $organizationValue,
                $ignoreOwner
            ];
        } elseif ($organizationField && $organizationValue) {
            return [
                null,
                null,
                PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
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
            PathExpression::TYPE_STATE_FIELD,
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
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
    }

    /**
     * @return OwnerTree
     */
    protected function getTree()
    {
        return $this->treeProvider->getTree();
    }

    /**
     * Get ACL sql conditions and join statements to check shared records
     *
     * @param string $entityName
     * @param string $entityAlias
     * @param mixed $permissions
     *
     * @return array
     */
    public function getAclShareData($entityName, $entityAlias, $permissions = BasicPermissionMap::PERMISSION_VIEW)
    {
        if ($permissions !== BasicPermissionMap::PERMISSION_VIEW) {
            return null;
        }

        $aclClass = $this->getObjectManager()->getRepository('OroSecurityBundle:AclClass')
            ->findOneBy(['classType' => $entityName]);

        if (!$aclClass) {
            return null;
        }

        $shareConfig = null;

        if ($this->configProvider->hasConfig($entityName)) {
            $shareConfig = $this->configProvider->getConfig($entityName)->get('share_scopes');
        }

        if (!$shareConfig) {
            return null;
        }

        $aclSIds = $this->getSecurityIdentityIds((array) $shareConfig);

        if (empty($aclSIds)) {
            return null;
        }

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $isGranted = $this->getSecurityContext()->isGranted($permissions, 'entity:' . $entityName);

        if (!$isGranted || !in_array($observer->getAccessLevel(), $this->shareAccessLevels)) {
            return null;
        }

        $shareCondition = [
            'existsSubselect' => [
                'select' => 1,
                'from'   => ['schemaName' => self::ACL_ENTRIES_SCHEMA_NAME, 'alias' => self::ACL_ENTRIES_ALIAS],
                'where'  => $this->getShareSubselectWhereConditions($entityAlias, $aclSIds, $aclClass)
            ],
            'not'             => false,
        ];

        //Add query components for OutputSqlWalker
        $queryComponents[self::ACL_ENTRIES_ALIAS] = [
            'metadata'     => $this->getObjectManager()->getClassMetadata(
                self::ACL_ENTRIES_SCHEMA_NAME
            ),
            'parent'       => null,
            'relation'     => null,
            'map'          => null,
            'nestingLevel' => null,
            'token'        => null
        ];

        return [$shareCondition, $queryComponents];
    }

    /**
     * Get all Security Identity Ids
     *
     * @param array $shareScope
     *
     * @return array|int
     */
    protected function getSecurityIdentityIds(array $shareScope)
    {
        if ($this->sids !== null) {
            $sidIds = $this->getSecurityIdentityIdsByScope($this->sids, $shareScope);
            return count($sidIds) === 1 ? $sidIds[0] : $sidIds;
        }

        $sids = $this->sidStrategy->getSecurityIdentities($this->getSecurityContext()->getToken());
        $sidByDb = [];

        foreach ($sids as $sid) {
            $entitySid = $this->getSecurityIdentityId($sid);
            if ($entitySid) {
                $sidByDb[$entitySid->getId()] = $sid;
            }
        }

        $this->sids = $sidByDb;
        $sidIds = $this->getSecurityIdentityIdsByScope($this->sids, $shareScope);

        return count($sidIds) === 1 ? $sidIds[0] : $sidIds;
    }

    /**
     * Get only Security Identity ids that can be shared by entity share scope
     *
     * @param array $sids
     * @param array $shareScope
     *
     * @return array
     */
    protected function getSecurityIdentityIdsByScope(array $sids, array $shareScope)
    {
        $sidIds = [];

        foreach ($sids as $key => $sid) {
            $sharedToScope = false;

            if ($sid instanceof UserSecurityIdentity) {
                $sharedToScope = Share::SHARE_SCOPE_USER;
            } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
                $sharedToScope = Share::SHARE_SCOPE_BUSINESS_UNIT;
            }

            if (in_array($sharedToScope, $shareScope)) {
                $sidIds[] = $key;
            }
        }

        return $sidIds;
    }

    /**
     * @param SecurityIdentityInterface $sid
     *
     * @return mixed
     */
    protected function getSecurityIdentityId(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            $identifier = $sid->getClass() . '-' . $sid->getUsername();
            $username = true;
        } elseif ($sid instanceof RoleSecurityIdentity) {
            //skip Role SID because we didn't share records for Role
            return null;
        } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
            $identifier = $sid->getClass() . '-' . $sid->getId();
            $username = false;
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity ' .
                'or BusinessUnitSecurityIdentity.'
            );
        }

        return $this->getObjectManager()->getRepository('OroSecurityBundle:AclSecurityIdentity')
            ->findOneBy([
                'identifier' => $identifier,
                'username' => $username,
            ]);
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->registry->getManager();
    }

    /**
     * Gets the logged user
     *
     * @return null|mixed
     */
    public function getUser()
    {
        if ($this->user) {
            return $this->user;
        }

        $token = $this->getSecurityContext()->getToken();
        if (!$token) {
            return null;
        }
        $user = $token->getUser();
        if (!is_object($user) || !is_a($user, $this->metadataProvider->getBasicLevelClass())) {
            return null;
        }

        $this->user = $user;

        return $this->user;
    }

    /**
     * @param string    $entityAlias
     * @param int|array $aclSIds
     * @param AclClass  $aclClass
     *
     * @return array
     */
    protected function getShareSubselectWhereConditions($entityAlias, $aclSIds, AclClass $aclClass)
    {
        return [
            [
                'left' => [
                    'expectedType' => AclWalker::EXPECTED_TYPE,
                    'entityAlias' => $entityAlias,
                    'field' => 'id',
                    'typeOperand' => PathExpression::TYPE_STATE_FIELD
                ],
                'right' => [
                    'expectedType' => AclWalker::EXPECTED_TYPE,
                    'entityAlias' => self::ACL_ENTRIES_ALIAS,
                    'field' => self::ACL_ENTRIES_SHARE_RECORD,
                    'typeOperand' => PathExpression::TYPE_STATE_FIELD
                ],
                'operation' => '='
            ],
            [
                'left' => [
                    'expectedType' => AclWalker::EXPECTED_TYPE,
                    'entityAlias' => self::ACL_ENTRIES_ALIAS,
                    'field' => self::ACL_ENTRIES_SECURITY_ID,
                    'typeOperand' => PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
                ],
                'right' => [
                    'value' => $aclSIds
                ],
                'operation' => is_array($aclSIds) ? 'IN' : '='
            ],
            [
                'left' => [
                    'expectedType' => AclWalker::EXPECTED_TYPE,
                    'entityAlias' => self::ACL_ENTRIES_ALIAS,
                    'field' => self::ACL_ENTRIES_CLASS_ID,
                    'typeOperand' => PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
                ],
                'right' => [
                    'value' => $aclClass->getId()
                ],
                'operation' => '='
            ]
        ];
    }
}
