<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Person
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Item
     *
     * @ORM\ManyToOne(targetEntity="Item")
     */
    protected $bestItem;

    /**
     * @var Item[]
     *
     * @ORM\ManyToMany(targetEntity="Item")
     */
    protected $items;

    /**
     * @var Group
     *
     * @ORM\ManyToMany(targetEntity="Group")
     */
    protected $groups;
}
