<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailAddress as OriginalEmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;

class EmailAddress extends OriginalEmailAddress
{
    protected $owner;

    public function __construct($id = null, $date = null)
    {
        if ($id !== null) {
            $this->setId($id);
        }
        if ($date !== null) {
            $this->setCreatedAt($date);
            $this->setUpdatedAt($date);
        }
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner(EmailOwnerInterface $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}
