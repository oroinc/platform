<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class TestEntity
{
    /** @var int */
    protected $id;

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

    /**
     * @return User
     */
    public function getUserOwner(): ?User
    {
        return $this->userOwner;
    }

    public function setUserOwner(User $userOwner): void
    {
        $this->userOwner = $userOwner;
    }

    /**
     * @return BusinessUnit
     */
    public function getBusinessUnitOwner(): ?BusinessUnit
    {
        return $this->businessUnitOwner;
    }

    public function setBusinessUnitOwner(BusinessUnit $businessUnitOwner): void
    {
        $this->businessUnitOwner = $businessUnitOwner;
    }
}
