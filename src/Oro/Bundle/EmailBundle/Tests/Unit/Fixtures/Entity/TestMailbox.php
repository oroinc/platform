<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\EmailBundle\Entity\Mailbox;

class TestMailbox extends Mailbox
{
    public function __construct($id = null)
    {
        parent::__construct();
        $this->id = $id;
    }
}
