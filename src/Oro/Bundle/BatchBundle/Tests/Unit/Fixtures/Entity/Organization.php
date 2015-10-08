<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Organization
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
     * @ORM\OneToMany(targetEntity="BusinessUnit", mappedBy="organization", cascade={"ALL"})
     */
    protected $businessUnits;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="organizations")
     * @ORM\JoinTable(name="oro_user_organization")
     */
    protected $users;
}
