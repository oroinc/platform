<?php

namespace Oro\Bundle\SecurityBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;

class UpdateAcl extends Event
{
    const NAME_BEFORE = 'oro_security.security.acl.dbal.provider.update_acl_before';
    const NAME_AFTER = 'oro_security.security.acl.dbal.provider.update_acl_after';

    /** @var MutableAclInterface */
    protected $acl;

    /**
     * @param MutableAclInterface $acl
     */
    public function __construct(MutableAclInterface $acl)
    {
        $this->acl = $acl;
    }

    /**
     * @return MutableAclInterface
     */
    public function getAcl()
    {
        return $this->acl;
    }
}
