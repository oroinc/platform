<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Doctrine\ORM\Mapping\MappingException;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;

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
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct(
            $objectIdAccessor,
            $entityClassResolver,
            $entityMetadataProvider,
            $metadataProvider,
            $decisionMaker
        );

        $this->doctrineHelper = $doctrineHelper;

        // override permission map for fields
        $this->permissionToMaskBuilderIdentity = [
            'VIEW'   => EntityMaskBuilder::IDENTITY,
            'CREATE' => EntityMaskBuilder::IDENTITY,
            'EDIT'   => EntityMaskBuilder::IDENTITY,
        ];

        $this->map = [
            'VIEW'   => [
                EntityMaskBuilder::MASK_VIEW_BASIC,
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                EntityMaskBuilder::MASK_VIEW_DEEP,
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                EntityMaskBuilder::MASK_VIEW_SYSTEM,
            ],
            'CREATE' => [
                EntityMaskBuilder::MASK_CREATE_BASIC,
                EntityMaskBuilder::MASK_CREATE_LOCAL,
                EntityMaskBuilder::MASK_CREATE_DEEP,
                EntityMaskBuilder::MASK_CREATE_GLOBAL,
                EntityMaskBuilder::MASK_CREATE_SYSTEM,
            ],
            'EDIT'   => [
                EntityMaskBuilder::MASK_EDIT_BASIC,
                EntityMaskBuilder::MASK_EDIT_LOCAL,
                EntityMaskBuilder::MASK_EDIT_DEEP,
                EntityMaskBuilder::MASK_EDIT_GLOBAL,
                EntityMaskBuilder::MASK_EDIT_SYSTEM,
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

        return (0 === strpos($id, self::EXTENSION_KEY) || null === $id);
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
        if ($isRoot || $oid->getIdentifier() != self::EXTENSION_KEY) {
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
    public function getExtensionKey()
    {
        return self::EXTENSION_KEY;
    }
}
