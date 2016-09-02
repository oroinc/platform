<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Stub;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class TestUserEmailOrigin extends UserEmailOrigin
{
    public function __construct($id = null)
    {
        parent::__construct();
        $this->id = $id;
    }
}
