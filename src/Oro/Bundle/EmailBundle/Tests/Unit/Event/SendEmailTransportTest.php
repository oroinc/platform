<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

class SendEmailTransportest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EmailOrigin $emailOrigin */
        $emailOrigin = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailOrigin')
            ->disableOriginalConstructor()->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Swift_Transport_EsmtpTransport $transport */
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
