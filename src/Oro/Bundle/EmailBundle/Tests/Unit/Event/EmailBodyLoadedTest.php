<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailBodyLoaded;

class EmailBodyLoadedTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()->getMock();

        $emailBodySyncAfter = new EmailBodyLoaded($email);

        $this->assertEquals($emailBodySyncAfter->getEmail(), $email);
    }
}
