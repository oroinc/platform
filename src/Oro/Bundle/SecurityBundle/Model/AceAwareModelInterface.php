<?php

namespace Oro\Bundle\SecurityBundle\Model;

use Symfony\Component\Security\Acl\Model\EntryInterface;

/**
 * A contract for injection ACE object
 */
interface AceAwareModelInterface
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
