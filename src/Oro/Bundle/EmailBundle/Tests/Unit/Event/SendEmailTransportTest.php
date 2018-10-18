<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Event\SendEmailTransport;

class SendEmailTransportTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|EmailOrigin $emailOrigin */
        $emailOrigin = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailOrigin')
            ->disableOriginalConstructor()->getMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject|\Swift_Transport_EsmtpTransport $transport */
        $transport = $this->getMockBuilder('\Swift_Transport_EsmtpTransport')
            ->disableOriginalConstructor()->getMock();
        
        $sendEmailTransport = new SendEmailTransport($emailOrigin, $transport);

        $this->assertEquals($sendEmailTransport->getEmailOrigin(), $emailOrigin);
        $this->assertEquals($sendEmailTransport->getTransport(), $transport);

        $sendEmailTransport->setTransport(null);
        $this->assertEquals($sendEmailTransport->getTransport(), null);
        
        $sendEmailTransport->setTransport($transport);
        $this->assertEquals($sendEmailTransport->getTransport(), $transport);
    }
}
