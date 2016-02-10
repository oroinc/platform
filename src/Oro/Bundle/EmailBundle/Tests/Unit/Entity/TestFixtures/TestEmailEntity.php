<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\Email;

class TestEmailEntity extends Email
{
    public function __construct($id = null)
    {
        $this->id = $id;
    }
}
