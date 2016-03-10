<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

class FieldAclExtension extends EntityAclExtension
{
    const NAME = 'field';

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        EntityClassResolver $entityClassResolver,
        EntitySecurityMetadataProvider $entityMetadataProvider,
        MetadataProviderInterface $metadataProvider,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker
    ) {
        parent::__construct(
            $objectIdAccessor,
            $entityClassResolver,
            $entityMetadataProvider,
            $metadataProvider,
            $decisionMaker
        );

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
    public function getExtensionKey()
    {
        return self::EXTENSION_KEY;
    }
}
