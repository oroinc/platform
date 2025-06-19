<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\UserBundle\Entity\Email;
use PHPUnit\Framework\TestCase;

class EmailBodyAddedTest extends TestCase
{
    public function testConstruct(): void
    {
        $email = $this->createMock(Email::class);
        $emailBodySyncAfter = new EmailBodyAdded($email);

        $this->assertEquals($emailBodySyncAfter->getEmail(), $email);
    }
}
