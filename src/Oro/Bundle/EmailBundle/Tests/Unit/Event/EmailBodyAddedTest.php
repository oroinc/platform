<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\UserBundle\Entity\Email;

class EmailBodyAddedTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $email = $this->createMock(Email::class);
        $emailBodySyncAfter = new EmailBodyAdded($email);

        $this->assertEquals($emailBodySyncAfter->getEmail(), $email);
    }
}
