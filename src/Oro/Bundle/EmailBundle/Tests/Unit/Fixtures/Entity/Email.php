<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\EmailBundle\Entity\Email as BaseEmail;

class Email extends BaseEmail
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
