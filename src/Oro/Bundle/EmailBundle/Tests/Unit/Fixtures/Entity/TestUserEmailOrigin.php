<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class TestUserEmailOrigin extends UserEmailOrigin
{
    public function __construct($id = null, $user = null)
    {
        parent::__construct();
        $this->id = $id;
        $this->owner = $user;
    }
}
