<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit as BaseBusinessUnit;

class BusinessUnit extends BaseBusinessUnit
{
    protected ?int $id;

    protected ?BaseBusinessUnit $owner;

    public function __construct($id = 0, $owner = null)
    {
        $this->id = $id;
        $this->owner = $owner;
    }

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function getOwner()
    {
        return $this->owner;
    }
}
