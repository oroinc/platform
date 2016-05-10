<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity;

class TestCreationEntity
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    public function __construct($id = null, $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /** @param string $name */
    public function setName($name)
    {
        $this->name = $name;
    }
}
