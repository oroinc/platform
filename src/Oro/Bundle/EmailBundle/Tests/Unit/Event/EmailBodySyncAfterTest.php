<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailBodySyncAfter;

class EmailBodySyncAfterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $email = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Email')
            ->disableOriginalConstructor()->getMock();
        $emailBodySyncAfter = new EmailBodySyncAfter($email);

        $this->assertEquals($emailBodySyncAfter->getEmail(), $email);
    }
}
