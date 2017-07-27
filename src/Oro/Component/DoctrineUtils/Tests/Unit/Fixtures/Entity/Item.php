<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Item
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="Person")
     */
    protected $persons;

    /**
     * @var Person
     *
     * @ORM\OneToOne(targetEntity="Person")
     */
    protected $owner;
}
