<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

class FieldAclExtension extends EntityAclExtension
{
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
    public function getExtensionKey()
    {
        return 'field';
    }
}
