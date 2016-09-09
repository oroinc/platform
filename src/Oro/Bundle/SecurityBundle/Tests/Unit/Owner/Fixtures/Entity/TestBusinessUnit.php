<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tbl_business_unit")
 */
class TestBusinessUnit
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="TestBusinessUnit")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @ORM\ManyToMany(targetEntity="TestUser", mappedBy="businessUnits")
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="TestOrganization", inversedBy="businessUnits")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     */
    protected $organization;
}
