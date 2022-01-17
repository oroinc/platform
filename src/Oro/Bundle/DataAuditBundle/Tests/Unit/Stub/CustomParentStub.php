<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class CustomParentStub
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Collection|CustomFieldStub[]
     */
    protected $customFields;

    public function __construct()
    {
        $this->localizedFields = new ArrayCollection();
        $this->customFields = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
