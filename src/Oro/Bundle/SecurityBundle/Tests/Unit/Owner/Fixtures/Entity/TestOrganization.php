<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tbl_organization")
 */
class TestOrganization
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="TestBusinessUnit", mappedBy="organization")
     */
    protected $businessUnits;

    /**
     * @ORM\ManyToMany(targetEntity="TestUser", mappedBy="organizations")
     */
    protected $users;
}
