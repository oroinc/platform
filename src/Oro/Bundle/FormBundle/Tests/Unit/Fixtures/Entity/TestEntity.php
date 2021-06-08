<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity;

class TestEntity
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
