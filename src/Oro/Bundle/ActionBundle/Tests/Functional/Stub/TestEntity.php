<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Stub;

class TestEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
