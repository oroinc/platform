<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

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
        $this->map = array(
            'VIEW'   => array(
                EntityMaskBuilder::MASK_VIEW_BASIC,
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                EntityMaskBuilder::MASK_VIEW_DEEP,
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                EntityMaskBuilder::MASK_VIEW_SYSTEM,
            ),
            'EDIT'   => array(
                EntityMaskBuilder::MASK_EDIT_BASIC,
                EntityMaskBuilder::MASK_EDIT_LOCAL,
                EntityMaskBuilder::MASK_EDIT_DEEP,
                EntityMaskBuilder::MASK_EDIT_GLOBAL,
                EntityMaskBuilder::MASK_EDIT_SYSTEM,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        $supports = parent::supports($type, $id);

        return $supports && strpos($id, 'field') === 0;
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
    public function getExtensionKey()
    {
        return 'field';
    }
}
