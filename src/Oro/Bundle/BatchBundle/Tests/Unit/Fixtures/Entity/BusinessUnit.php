<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class BusinessUnit
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="businessUnits")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $organization;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="businessUnits")
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="BusinessUnit")
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;
}
