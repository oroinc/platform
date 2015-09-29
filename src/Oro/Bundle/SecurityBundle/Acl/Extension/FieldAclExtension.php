<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Voter\FieldVote;

class FieldAclExtension extends EntityAclExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        return FieldVote::class == $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionKey()
    {
        return 'field';
    }
}
