<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

use Oro\Bundle\EmailBundle\Entity\EmailBody;

class EmailBodyStub extends EmailBody
{
    public function __construct(?int $id = null)
    {
        parent::__construct();

        if ($id !== null) {
            $this->id = $id;
        }
    }
}
