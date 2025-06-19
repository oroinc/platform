<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Event\EmailBodyLoaded;
use PHPUnit\Framework\TestCase;

class EmailBodyLoadedTest extends TestCase
{
    public function testConstruct(): void
    {
        $email = $this->createMock(Email::class);

        $emailBodySyncAfter = new EmailBodyLoaded($email);

        $this->assertEquals($emailBodySyncAfter->getEmail(), $email);
    }
}
