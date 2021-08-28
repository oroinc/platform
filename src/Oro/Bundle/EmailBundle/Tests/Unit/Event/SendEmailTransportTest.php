<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Event\SendEmailTransport;

class SendEmailTransportTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $emailOrigin = $this->createMock(EmailOrigin::class);
        $transport = $this->createMock(\Swift_Transport_EsmtpTransport::class);

        $sendEmailTransport = new SendEmailTransport($emailOrigin, $transport);

        $this->assertEquals($sendEmailTransport->getEmailOrigin(), $emailOrigin);
        $this->assertEquals($sendEmailTransport->getTransport(), $transport);

        $sendEmailTransport->setTransport(null);
        $this->assertEquals($sendEmailTransport->getTransport(), null);

        $sendEmailTransport->setTransport($transport);
        $this->assertEquals($sendEmailTransport->getTransport(), $transport);
    }
}
