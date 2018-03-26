<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailAddress as BaseEmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;

class EmailAddress extends BaseEmailAddress
{
    /** @var EmailOwnerInterface */
    protected $owner;

    /**
     * @return EmailOwnerInterface
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param EmailOwnerInterface|null $owner
     *
     * @return $this
     */
    public function setOwner(EmailOwnerInterface $owner = null)
    {
        $this->owner = $owner;
        $this->setHasOwner($owner !== null);

        return $this;
    }
}
