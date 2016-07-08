<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tbl_user")
 */
class TestUser
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="TestBusinessUnit")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @ORM\ManyToMany(targetEntity="TestBusinessUnit", inversedBy="users")
     * @ORM\JoinTable(name="tbl_user_to_business_unit",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="business_unit_id", referencedColumnName="id")}
     *  )
     */
    protected $businessUnits;

    /**
     * @ORM\ManyToMany(targetEntity="TestOrganization", inversedBy="users")
     * @ORM\JoinTable(name="tbl_user_to_organization",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="organization_id", referencedColumnName="id")}
     *  )
     */
    protected $organizations;
}
