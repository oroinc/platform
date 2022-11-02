<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Stub;

class StubEntity
{
    /** @var mixed */
    private $id;

    /**
     * @param mixed $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
