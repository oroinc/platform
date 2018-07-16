<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;

class EmailBodyAddedTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $email = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Email')
            ->disableOriginalConstructor()->getMock();
        $emailBodySyncAfter = new EmailBodyAdded($email);

        $this->assertEquals($emailBodySyncAfter->getEmail(), $email);
    }
}
