<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

class TestEmailHolder implements EmailHolderInterface
{
    protected $email;

    public function __construct($email = null)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }
}
