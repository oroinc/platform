<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailThread;

class TestThread extends EmailThread
{
    public function __construct($id = null)
    {
        $this->id = $id;
    }
}
