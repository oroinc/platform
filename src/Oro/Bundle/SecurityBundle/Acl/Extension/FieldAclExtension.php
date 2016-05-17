<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Doctrine\ORM\Mapping\MappingException;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class FieldAclExtension extends EntityAclExtension
{
    const NAME = 'field';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $metadataCache = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        EntityClassResolver $entityClassResolver,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        MetadataProviderInterface $metadataProvider,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        PermissionManager $permissionManager,
        AclGroupProviderInterface $groupProvider,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct(
            $objectIdAccessor,
            $entityClassResolver,
            $entityMetadataProvider,
            $metadataProvider,
            $decisionMaker,
            $permissionManager,
            $groupProvider
        );

        $this->doctrineHelper = $doctrineHelper;

        $this->permissionToMaskBuilderIdentity = [
            'VIEW'   => FieldMaskBuilder::IDENTITY,
            'CREATE' => FieldMaskBuilder::IDENTITY,
            'EDIT'   => FieldMaskBuilder::IDENTITY,
        ];

        $this->maskBuilderIdentityToPermissions = [
            array_keys($this->permissionToMaskBuilderIdentity)
        ];

        $this->map = [
            'VIEW'   => [
                FieldMaskBuilder::MASK_VIEW_BASIC,
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                FieldMaskBuilder::MASK_VIEW_DEEP,
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                FieldMaskBuilder::MASK_VIEW_SYSTEM,
            ],
            'CREATE' => [
                FieldMaskBuilder::MASK_CREATE_SYSTEM,
            ],
            'EDIT'   => [
                FieldMaskBuilder::MASK_EDIT_BASIC,
                FieldMaskBuilder::MASK_EDIT_LOCAL,
                FieldMaskBuilder::MASK_EDIT_DEEP,
                FieldMaskBuilder::MASK_EDIT_GLOBAL,
                FieldMaskBuilder::MASK_EDIT_SYSTEM,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isRootSupported()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        $isEntity = parent::supports($type, $id);
        if (!$isEntity) {
            return false;
        }

        if ($id === $this->getExtensionKey()) {
            return true;
        }

        // either id starts with 'field' (e.g. field+fieldName)
        // or id is null (checking for new entity)

        return (0 === strpos($id, self::NAME) || null === $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames($object, $permissionName = null)
    {
        if ('CREATE' === $permissionName) {
            // only system and none levels are applicable to new entities
            return AccessLevel::getAccessLevelNames(AccessLevel::SYSTEM_LEVEL);
        }

        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            return [
                AccessLevel::NONE_LEVEL   => AccessLevel::NONE_LEVEL_NAME,
                AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
            ];
        }

        if ($metadata->isBasicLevelOwned()) {
            $minLevel = AccessLevel::BASIC_LEVEL;
        } elseif ($metadata->isLocalLevelOwned()) {
            $minLevel = AccessLevel::LOCAL_LEVEL;
        } else {
            $minLevel = AccessLevel::GLOBAL_LEVEL;
        }

        return AccessLevel::getAccessLevelNames($minLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null)
    {
        $result = parent::getAllowedPermissions($oid);

        $className = $oid->getType();

        $entityMetadata = empty($this->metadataCache[$className]) ?
            $this->doctrineHelper->getEntityMetadata($className) :
            $this->metadataCache[$oid->getType()];

        if (in_array('CREATE', $result, true)) {
            try {
                $isNullable = $entityMetadata->isNullable($fieldName);
            } catch (MappingException $e) {
                $isNullable = true;
            }

            // remove CREATE permission manipulations because this field is not nullable
            if (!$isNullable && in_array('CREATE', $result, true)) {
                foreach ($result as $index => $permission) {
                    if ($permission === 'CREATE') {
                        unset($result[$index]);
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionKey()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskPattern($mask)
    {
        return FieldMaskBuilder::getPatternFor($mask);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskBuilder($permission)
    {
        return new FieldMaskBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMaskBuilders()
    {
        return [new FieldMaskBuilder()];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevel($mask, $permission = null, $object = null)
    {
        if (0 === $this->removeServiceBits($mask)) {
            return AccessLevel::NONE_LEVEL;
        }

        $identity = $this->getServiceBits($mask);
        if ($permission !== null) {
            $permissionMask = $this->getMaskBuilderConst($identity, 'GROUP_' . $permission);
            $mask = $mask & $permissionMask;
        }

        $mask = $this->removeServiceBits($mask);

        $result = AccessLevel::NONE_LEVEL;
        foreach (AccessLevel::$allAccessLevelNames as $accessLevel) {
            if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'GROUP_' . $accessLevel))) {
                $result = AccessLevel::getConst($accessLevel . '_LEVEL');
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($mask = null, $setOnly = false, $byCurrentGroup = false)
    {
        if ($mask === null) {
            return array_keys($this->permissionToMaskBuilderIdentity);
        }
        $result = [];
        if (!$setOnly) {
            $identity = $this->getServiceBits($mask);
            foreach ($this->permissionToMaskBuilderIdentity as $permission => $id) {
                if ($id === $identity) {
                    $result[] = $permission;
                }
            }
        } elseif (0 !== $this->removeServiceBits($mask)) {
            $identity = $this->getServiceBits($mask);
            foreach ($this->permissionToMaskBuilderIdentity as $permission => $id) {
                if ($id === $identity) {
                    if (0 !== ($mask & $this->getMaskBuilderConst($identity, 'GROUP_' . $permission))) {
                        $result[] = $permission;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceBits($mask)
    {
        return $mask & FieldMaskBuilder::SERVICE_BITS;
    }

    /**
     * {@inheritdoc}
     */
    public function validateMask($mask, $object, $permission = null)
    {
        if (0 === $this->removeServiceBits($mask)) {
            // zero mask
            return;
        }

        $permissions = $permission === null
            ? $this->getPermissions($mask, true)
            : [$permission];

        foreach ($permissions as $permission) {
            $validMasks = $this->getValidMasks($permission, $object);
            if (($mask | $validMasks) === $validMasks) {
                $identity = $this->permissionToMaskBuilderIdentity[$permission];
                foreach ($this->permissionToMaskBuilderIdentity as $p => $i) {
                    if ($identity === $i) {
                        $this->validateMaskAccessLevel($p, $mask, $object);
                    }
                }

                return;
            }
        }

        throw $this->createInvalidAclMaskException($mask, $object);
    }

    /**
     * Gets all valid bitmasks for the given object
     *
     * @param string $permission
     * @param mixed  $object
     *
     * @return int
     */
    protected function getValidMasks($permission, $object)
    {
        $identity = $this->permissionToMaskBuilderIdentity[$permission];

        $metadata = $this->getMetadata($object);
        if (!$metadata->hasOwner()) {
            if ($identity === $this->permissionToMaskBuilderIdentity['CREATE']) {
                return $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM');
            } elseif ($identity === $this->permissionToMaskBuilderIdentity['ASSIGN']) {
                return $this->getMaskBuilderConst($identity, 'MASK_DELETE_SYSTEM');
            }

            return $identity;
        }

        if ($metadata->isGlobalLevelOwned()) {
            return
                $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM')
                | $this->getMaskBuilderConst($identity, 'GROUP_GLOBAL');
        } elseif ($metadata->isLocalLevelOwned()) {
            return
                $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM')
                | $this->getMaskBuilderConst($identity, 'GROUP_GLOBAL')
                | $this->getMaskBuilderConst($identity, 'GROUP_DEEP')
                | $this->getMaskBuilderConst($identity, 'GROUP_LOCAL');
        } elseif ($metadata->isBasicLevelOwned()) {
            return
                $this->getMaskBuilderConst($identity, 'GROUP_SYSTEM')
                | $this->getMaskBuilderConst($identity, 'GROUP_GLOBAL')
                | $this->getMaskBuilderConst($identity, 'GROUP_DEEP')
                | $this->getMaskBuilderConst($identity, 'GROUP_LOCAL')
                | $this->getMaskBuilderConst($identity, 'GROUP_BASIC');
        }

        return $this->permissionToMaskBuilderIdentity[$permission];
    }

    /**
     * {@inheritdoc}
     */
    protected function parseDescriptor($descriptor, &$type, &$id, &$group)
    {
        parent::parseDescriptor($descriptor, $type, $id, $group);

        if (strpos($id, '+')) {
            $id = explode('+', $id)[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentity($val)
    {
        $identity = parent::getObjectIdentity($val);

        if (null === $identity->getIdentifier()) {
            $identity = new ObjectIdentity('entity', $identity->getType());
        }

        return $identity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionsToIdentityMap($byCurrentGroup = false)
    {
        return $this->permissionToMaskBuilderIdentity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentityForPermission($permission)
    {
        $identities = $this->getPermissionsToIdentityMap();

        return empty($identities[$permission]) ? FieldMaskBuilder::IDENTITY : $identities[$permission];
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaskBuilderConst($maskBuilderIdentity, $constName)
    {
        $maskBuilder = new FieldMaskBuilder(
            $maskBuilderIdentity,
            $this->getPermissionsForIdentity($maskBuilderIdentity)
        );

        return $maskBuilder->getMask($constName);
    }
}
