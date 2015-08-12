<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

class TestEntity
{
    private $id;

    private $owner;

    private $customOwner;

    private $organization;

    public function __construct($id = 0, $owner = null, $organization = null)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->organization = $organization;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function setCustomOwner($customOwner)
    {
        $this->customOwner = $customOwner;
    }

    public function getCustomOwner()
    {
        return $this->customOwner;
    }

    public function getOrganization()
    {
        return $this->organization;
    }
}
