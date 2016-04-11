<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Doctrine\ORM\Mapping\MappingException;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class FieldAclExtension extends EntityAclExtension
{
    const NAME = 'field';

    const IDENTITY = 0;

    /**
         basic  - access to own records and objects that are shared with the user
         local  - access to records in all business units are assigned to the user
         deep   - access to records in all business units are assigned to the user
                  and all business units subordinate to business units are assigned to the user.
         global - access to all records within the organization,
                  regardless of the business unit hierarchical level to which the domain object belongs
                  or the user is assigned to
         system - access to all records within the system
     */
    const MASK_VIEW_BASIC         = 1;
    const MASK_VIEW_LOCAL         = 2;
    const MASK_VIEW_DEEP          = 4;
    const MASK_VIEW_GLOBAL        = 8;
    const MASK_VIEW_SYSTEM        = 16;

    const MASK_CREATE_BASIC       = 32;
    const MASK_CREATE_LOCAL       = 64;
    const MASK_CREATE_DEEP        = 128;
    const MASK_CREATE_GLOBAL      = 256;
    const MASK_CREATE_SYSTEM      = 512;

    const MASK_EDIT_BASIC         = 1024;
    const MASK_EDIT_LOCAL         = 2048;
    const MASK_EDIT_DEEP          = 4096;
    const MASK_EDIT_GLOBAL        = 8192;
    const MASK_EDIT_SYSTEM        = 16384;

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
            'VIEW'   => self::IDENTITY,
            'CREATE' => self::IDENTITY,
            'EDIT'   => self::IDENTITY,
        ];

        $this->maskBuilderIdentityToPermissions = [
            array_keys($this->permissionToMaskBuilderIdentity)
        ];

        $this->map = [
            'VIEW'   => [
                self::MASK_VIEW_BASIC,
                self::MASK_VIEW_LOCAL,
                self::MASK_VIEW_DEEP,
                self::MASK_VIEW_GLOBAL,
                self::MASK_VIEW_SYSTEM,
            ],
            'CREATE' => [
                self::MASK_CREATE_BASIC,
                self::MASK_CREATE_LOCAL,
                self::MASK_CREATE_DEEP,
                self::MASK_CREATE_GLOBAL,
                self::MASK_CREATE_SYSTEM,
            ],
            'EDIT'   => [
                self::MASK_EDIT_BASIC,
                self::MASK_EDIT_LOCAL,
                self::MASK_EDIT_DEEP,
                self::MASK_EDIT_GLOBAL,
                self::MASK_EDIT_SYSTEM,
            ],
        ];
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
    public function getAccessLevelNames($object, $permissionName = null)
    {
        $levelNames = parent::getAccessLevelNames($object);

        if ('CREATE' == $permissionName) {
            // only system and none levels are applicable to new entities
            $levelNames = AccessLevel::getAccessLevelNames(AccessLevel::SYSTEM_LEVEL);
        }

        return $levelNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null)
    {
        $result = parent::getAllowedPermissions($oid);

        $className = $oid->getType();

        $isRoot = $className == ObjectIdentityFactory::ROOT_IDENTITY_TYPE;
        if ($isRoot || $oid->getIdentifier() != self::NAME) {
            return $result;
        }

        $isIdentifier = $fieldName == $this->doctrineHelper
                ->getSingleEntityIdentifierFieldName($className, false);

        $entityMetadata = empty($this->metadataCache[$className]) ?
            $this->doctrineHelper->getEntityMetadata($className) :
            $this->metadataCache[$oid->getType()];

        try {
            $isNullable = $entityMetadata->isNullable($fieldName);
        } catch (MappingException $e) {
            $isNullable = true;
        }

        // return only 'VIEW' permission for identifier and required fields
        if ($isIdentifier || !$isNullable) {
            $result = ['VIEW'];
        }

        return $result;
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

        return empty($identities[$permission]) ? self::IDENTITY : $identities[$permission];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionKey()
    {
        return self::NAME;
    }
}
