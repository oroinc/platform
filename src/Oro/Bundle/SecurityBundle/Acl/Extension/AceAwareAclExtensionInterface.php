<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Model\EntryInterface;

// todo rename and move to other namespace the interface
/**
 * A contract for injection ACE object to ACL extension
 */
interface AceAwareAclExtensionInterface
{
    /**
     * Sets ACE object to extension. Current approach allows to decide granting permissions
     * depending on SecurityIdentity.
     *
     * @param EntryInterface $ace
     * @return mixed
     */
    public function setAce(EntryInterface $ace);
}
