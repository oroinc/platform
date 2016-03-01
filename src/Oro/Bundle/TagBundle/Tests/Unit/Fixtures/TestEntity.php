<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Fixtures;

class TestEntity
{

    /** @var int */
    protected $id;

    /** @param int $id */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }
}
