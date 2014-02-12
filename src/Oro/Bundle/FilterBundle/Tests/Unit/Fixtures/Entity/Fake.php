<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\Entity;

/**
 * @Entity
 * @Table(name="some_fake")
 */
class Fake
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="date", nullable=false)
     */
    public $date;

    public function getId()
    {
        return $this->id;
    }

    public function getDate()
    {
        return $this->date;
    }
}
