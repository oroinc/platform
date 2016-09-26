<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Stub;

class EntityStub
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @param int $id
     * @param string $name
     */
    public function __construct($id, $name = '')
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
