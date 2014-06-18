<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="test_activity")
 * @ORM\Entity
 */
class Activity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Target")
     * @ORM\JoinTable(name="test_activity_to_target")
     */
    protected $target_bcaa0d48;
}
