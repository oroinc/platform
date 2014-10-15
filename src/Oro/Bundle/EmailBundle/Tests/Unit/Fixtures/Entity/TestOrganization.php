<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

class TestOrganization
{
    protected $id;

    public function __construct($id)
    {
        return $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
