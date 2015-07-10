<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailAddress as OriginalEmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailAddressOwnerInterface;

class TestEmailAddressProxy extends OriginalEmailAddress
{
    /**
     * @var EmailAddressOwnerInterface
     */
    private $owner;

    public function __construct(EmailAddressOwnerInterface $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner(EmailAddressOwnerInterface $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}
