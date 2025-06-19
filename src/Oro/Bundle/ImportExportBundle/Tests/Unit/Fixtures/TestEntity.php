<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class TestEntity
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    /** @var Organization */
    protected $organization;

    /** @var User */
    protected $userOwner;

    /** @var BusinessUnit */
    protected $businessUnitOwner;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    public function getUserOwner(): ?User
    {
        return $this->userOwner;
    }

    public function setUserOwner(User $userOwner): void
    {
        $this->userOwner = $userOwner;
    }

    public function getBusinessUnitOwner(): ?BusinessUnit
    {
        return $this->businessUnitOwner;
    }

    public function setBusinessUnitOwner(BusinessUnit $businessUnitOwner): void
    {
        $this->businessUnitOwner = $businessUnitOwner;
    }
}
