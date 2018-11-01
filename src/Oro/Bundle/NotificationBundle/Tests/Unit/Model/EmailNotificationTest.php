<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\NotificationBundle\Model\EmailNotification;
use Oro\Bundle\NotificationBundle\Model\SenderAwareEmailNotificationInterface;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EmailNotificationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testPropertyAccessors()
    {
        /** @var EmailTemplateInterface|\PHPUnit_Framework_MockObject_MockObject $emailTemplate */
        $emailTemplate = $this->createMock(EmailTemplateInterface::class);
        $recipientEmails = [];

        $emailNotification = new EmailNotification($emailTemplate, $recipientEmails);

        $this->assertInstanceOf(SenderAwareEmailNotificationInterface::class, $emailNotification);

        static::assertPropertyAccessors(
            $emailNotification,
            [
                ['senderName', 'Sender'],
                ['senderEmail', 'sender@example.org']
            ]
        );
    }
}
