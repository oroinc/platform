<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder\Helper;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerAwareInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;

class EmailOwnerAwareStub implements EmailOwnerAwareInterface
{
    /** @var EmailOwnerInterface */
    private $emailOwner;

    public function __construct(EmailOwnerInterface $emailOwner)
    {
        $this->emailOwner = $emailOwner;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailOwner(): EmailOwnerInterface
    {
        return $this->emailOwner;
    }
}
