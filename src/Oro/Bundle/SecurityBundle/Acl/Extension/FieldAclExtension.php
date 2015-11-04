<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

class FieldAclExtension extends EntityAclExtension
{
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
            'VIEW' => EntityMaskBuilder::IDENTITY,
            'EDIT' => EntityMaskBuilder::IDENTITY,
        ];
        $this->map                             = [
            'VIEW' => [
                EntityMaskBuilder::MASK_VIEW_BASIC,
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                EntityMaskBuilder::MASK_VIEW_DEEP,
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                EntityMaskBuilder::MASK_VIEW_SYSTEM,
            ],
            'EDIT' => [
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
        $supports = parent::supports($type, $id);

        // if entity extension supports AND
        // either id starts with 'field' (e.g. field+fieldName)
        // or id is null (checking for new entity)

        return $supports && (0 === strpos($id, 'field') || null === $id);
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
            $identity = new ObjectIdentity('field', $identity->getType());
        }

        return $identity;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionKey()
    {
        return 'field';
    }
}
